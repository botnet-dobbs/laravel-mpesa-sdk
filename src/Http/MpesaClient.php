<?php

namespace Botnetdobbs\Mpesa\Http;

use Botnetdobbs\Mpesa\Contracts\Response;
use Botnetdobbs\Mpesa\Contracts\Client;
use Botnetdobbs\Mpesa\Data\Api\MpesaResponse;
use Botnetdobbs\Mpesa\Enums\MpesaRequestType;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Botnetdobbs\Mpesa\Services\InitiatorCredentialGenerator;
use Botnetdobbs\Mpesa\Validation\RequestValidator;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Response as HttpResponse;

class MpesaClient implements Client
{
    use RequestValidator;

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $environment
     * @param CacheManager $cacheManager
     * @param InitiatorCredentialGenerator $credentialGenerator
     */
    public function __construct(
        private string $consumerKey,
        private string $consumerSecret,
        private string $environment,
        private CacheManager $cacheManager,
        private InitiatorCredentialGenerator $credentialGenerator
    ) {
        if (empty($this->consumerKey)) {
            throw new \InvalidArgumentException('Mpesa consumer key not configured');
        }

        if (empty($this->consumerSecret)) {
            throw new \InvalidArgumentException('Mpesa consumer secret not configured');
        }

        if (empty($this->environment)) {
            throw new \InvalidArgumentException('Mpesa environment not configured');
        }

        if (!in_array($this->environment, ['sandbox', 'production'])) {
            throw new \InvalidArgumentException('Invalid Mpesa environment. Must be either sandbox or production');
        }
    }

    /**
     * @return string
     */
    private function getBaseUrl(): string
    {
        return (string) config("mpesa.endpoints.{$this->environment}.base_url");
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getEndpoint(string $name): string
    {
        return (string) config("mpesa.endpoints.{$this->environment}.{$name}");
    }

    /**
     * @throws MpesaException
     *
     * @return string
     */
    private function getAccessToken(): string
    {
        /** @var MpesaAuthToken $currentToken */
        $currentToken = $this->cacheManager->getStore()->get('mpesa_access_token');

        if (filled($currentToken) && $currentToken->isActive()) {
            return $currentToken->getToken();
        }

        $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
        ])->get($this->getBaseUrl() . $this->getEndpoint('oauth_token'), [
            'grant_type' => 'client_credentials'
        ]);

        if (!$response->successful()) {
            throw new MpesaException('Failed to get access token: ' . $response->body());
        }

        $accessToken = new MpesaAuthToken(
            (string) $response->json('access_token'),
            (int) $response->json('expires_in')
        );

        $this->setAccessToken($accessToken);

        return $accessToken->getToken();
    }

    /**
     * @param MpesaAuthToken $token
     *
     * @return bool
     */
    private function setAccessToken(MpesaAuthToken $token): bool
    {
        return $this->cacheManager->put('mpesa_access_token', $token);
    }

    /**
     * @return PendingRequest
     */
    private function client(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->timeout((int) config('mpesa.defaults.timeout'))
            ->connectTimeout((int) config('mpesa.defaults.connect_timeout'))
            ->baseUrl($this->getBaseUrl())
            ->throw(function (HttpResponse $response) {
                throw new MpesaException('Mpesa API request failed: ' . $response->body());
            });
    }

    /**
     * Initiates an STK Push request (Lipa Na M-Pesa Online)
     *
     * @param array{
     *     BusinessShortCode: numeric-string,
     *     TransactionType: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline",
     *     Amount: positive-int,
     *     PhoneNumber: numeric-string,
     *     CallBackURL: string,
     *     AccountReference: string,
     *     TransactionDesc: string
     * } $data
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function stkPush(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::STK_PUSH, $data);

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('stk_push'), array_merge([
                'BusinessShortCode' => $data['BusinessShortCode'],
                'Password' => $this->generatePassword($data['BusinessShortCode']),
                'Timestamp' => now()->format('YmdHis'),
                'TransactionType' => 'CustomerPayBillOnline',
                'PartyA' => $data['PhoneNumber'],
                'PartyB' => $data['BusinessShortCode'],
            ], $data))
        );
    }

    /**
     * Query the status of an STK Push transaction
     *
     * @param array{
     *     BusinessShortCode: numeric-string,
     *     CheckoutRequestID: string
     * } $data Query parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function stkQuery(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::STK_QUERY, $data);

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('stk_query'), array_merge([
                'Password' => $this->generatePassword($data['BusinessShortCode']),
                'Timestamp' => now()->format('YmdHis'),
            ], $data))
        );
    }

    /**
     * Initiates a Business to Customer (B2C) payment
     *
     * @param array{
     *     OriginatorConversationID: string,
     *     InitiatorName: string,
     *     CommandID: "SalaryPayment"|"BusinessPayment"|"PromotionPayment",
     *     Amount: positive-int,
     *     PartyA: numeric-string,
     *     PartyB: numeric-string,
     *     Remarks: string,
     *     QueueTimeOutURL: string,
     *     ResultURL: string,
     *     Occasion: string
     * } $data Transaction parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function b2c(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::B2C, $data);

        $data['SecurityCredential'] = $this->credentialGenerator->generate();

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('b2c_payment'), $data)
        );
    }

    /**
     * Initiates a Business to Business (B2B) payment
     *
     * @param array{
     *     primaryShortCode: numeric-string,
     *     receiverShortCode: numeric-string,
     *     amount: positive-int,
     *     paymentRef: string,
     *     callbackUrl: string,
     *     partnerName: string,
     *     RequestRefID: string
     * } $data Transaction parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function b2b(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::B2B, $data);

        $data['SecurityCredential'] = $this->credentialGenerator->generate();

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('b2b_payment'), $data)
        );
    }

    /**
     *Register URLs for C2B (Customer to Business) payments
     *
     * @param array{
     *     ShortCode: numeric-string,
     *     ResponseType: "Completed"|"Canceled",
     *     ConfirmationURL: string,
     *     ValidationURL: string
     * } $data URL registration parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function c2bRegister(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::C2B_REGISTER, $data);

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('c2b_register'), $data)
        );
    }

    /**
     * Simulate a C2B (Customer to Business) payment (Test environment only)
     *
     * @param array{
     *     ShortCode: numeric-string,
     *     CommandID: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline",
     *     Amount: positive-int,
     *     Msisdn: numeric-string,
     *     BillRefNumber: string
     * } $data Simulation parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function c2bSimulate(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::C2B_SIMULATE, $data);

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('c2b_simulate'), $data)
        );
    }

    /**
     * Query account balance
     *
     * @param array{
     *     Initiator: string,
     *     CommandID: "AccountBalance",
     *     PartyA: numeric-string,
     *     IdentifierType: "1"|"2"|"4",
     *     Remarks: string,
     *     QueueTimeOutURL: string,
     *     ResultURL: string
     * } $data Query parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function accountBalance(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::ACCOUNT_BALANCE, $data);

        $data['SecurityCredential'] = $this->credentialGenerator->generate();

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('account_balance'), $data)
        );
    }

    /**
     * Check the status of a transaction
     *
     * @param array{
     *     Initiator: string,
     *     CommandID: "TransactionStatusQuery",
     *     TransactionID: string,
     *     PartyA: numeric-string,
     *     IdentifierType: "1"|"2"|"4",
     *     ResultURL: string,
     *     QueueTimeOutURL: string,
     *     Remarks: string,
     *     Occasion: string
     * } $data Query parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function transactionStatus(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::TRANSACTION_STATUS, $data);

        $data['SecurityCredential'] = $this->credentialGenerator->generate();

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('transaction_status'), $data)
        );
    }

    /**
     * Reverse a completed M-Pesa transaction
     *
     * @param array{
     *     Initiator: string,
     *     CommandID: "TransactionReversal",
     *     TransactionID: string,
     *     Amount: positive-int,
     *     ReceiverParty: numeric-string,
     *     ReceiverIdentifierType: "1"|"2"|"4",
     *     ResultURL: string,
     *     QueueTimeOutURL: string,
     *     Remarks: string,
     *     Occasion: string
     * } $data Reversal parameters
     *
     * @throws MpesaException
     *
     * @return Response
     */
    public function reversal(array $data): Response
    {
        $this->validateRequestType(MpesaRequestType::REVERSAL, $data);

        $data['SecurityCredential'] = $this->credentialGenerator->generate();

        return new MpesaResponse(
            $this->client()->post($this->getEndpoint('reversal'), $data)
        );
    }

    /**
     * @param string $shortcode
     *
     * @return string
     */
    private function generatePassword(string $shortcode): string
    {
        $passkey = config('mpesa.lipa_na_mpesa_passkey');
        $timestamp = now()->format('YmdHis');
        return base64_encode($shortcode . $passkey . $timestamp);
    }
}
