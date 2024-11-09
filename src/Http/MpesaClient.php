<?php

namespace Botnetdobbs\Mpesa\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Response;

class MpesaClient
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $environment;
    private CacheManager $cacheManager;

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $environment
     */
    public function __construct(string $consumerKey, string $consumerSecret, string $environment = 'sandbox')
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->environment = $environment;
        $this->cacheManager = app('cache');
    }

    /**
     * @return string
     */
    private function getBaseUrl(): string
    {
        return config("mpesa.endpoints.{$this->environment}.base_url");
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getEndpoint(string $name): string
    {
        return config("mpesa.endpoints.{$this->environment}.{$name}");
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
            $response->json('access_token'),
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
            ->timeout(config('mpesa.defaults.timeout'))
            ->connectTimeout(config('mpesa.defaults.connect_timeout'))
            ->baseUrl($this->getBaseUrl());
    }

    /**
     * Initiates an STK Push request (Lipa Na M-Pesa Online)
     *
     * @param array{
     *     BusinessShortCode: numeric-string,   # The organization's shortcode (Paybill or Buygoods)
     *     Passkey: string,                     # The passkey provided by Safaricom
     *     TransactionType: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline",  # Type of transaction
     *     Amount: positive-int,                # The amount to be charged
     *     PartyA: numeric-string,              # The phone number sending money
     *     PartyB: numeric-string,              # Organization's shortcode receiving funds
     *     PhoneNumber: numeric-string,         # The phone number to prompt for payment
     *     CallBackURL: string,                 # URL to receive payment notification
     *     AccountReference: string,            # Account number for the transaction
     *     TransactionDesc: string              # Description of the transaction
     * } $data
     * 
     * @throws MpesaException When the API request fails or validation errors occur
     * 
     * @return object{
     *     MerchantRequestID: string,
     *     CheckoutRequestID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string,
     *     CustomerMessage: string
     * } Safaricom API response
     */
    public function stkPush(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('stk_push'), array_merge([
            'BusinessShortCode' => $data['BusinessShortCode'],
            'Password' => $this->generatePassword($data['BusinessShortCode'], $data['Passkey']),
            'Timestamp' => now()->format('YmdHis'),
            'TransactionType' => 'CustomerPayBillOnline',
            'PartyB' => $data['BusinessShortCode'],
        ], $data));

        return $this->handleResponse($response);
    }

    /**
     * Query the status of an STK Push transaction
     * 
     * @param array{
     *     BusinessShortCode: numeric-string,   # The organization's shortcode
     *     Passkey: string,                     # The passkey provided by Safaricom
     *     CheckoutRequestID: string            # The CheckoutRequestID from STK push response
     * } $data Query parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     ResponseCode: string,
     *     ResponseDescription: string,
     *     MerchantRequestID: string,
     *     CheckoutRequestID: string,
     *     ResultCode: string,
     *     ResultDesc: string
     * } Transaction status response
     */
    public function stkQuery(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('stk_query'), array_merge([
            'Password' => $this->generatePassword($data['BusinessShortCode'], $data['Passkey']),
            'Timestamp' => now()->format('YmdHis'),
        ], $data));
        return $this->handleResponse($response);
    }

    /**
     * Initiates a Business to Customer (B2C) payment
     *
     * @param array{
     *     OriginatorConversationID: string,    # Unique identifier for the transaction
     *     InitiatorName: string,               # The name of the initiator
     *     SecurityCredential: string,          # Base64 encoded security credential
     *     CommandID: "SalaryPayment"|"BusinessPayment"|"PromotionPayment",  # Type of B2C payment
     *     Amount: positive-int,                # Amount to be sent to customer
     *     PartyA: numeric-string,              # Organization's shortcode
     *     PartyB: numeric-string,              # Customer's phone number
     *     Remarks: string,                     # Comments about the transaction
     *     QueueTimeOutURL: string,             # Timeout notification URL
     *     ResultURL: string,                   # Success notification URL
     *     Occasion: string                     # Optional occasion description
     * } $data Transaction parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     ConversationID: string,
     *     OriginatorConversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string
     * } Safaricom API response
     */
    public function b2c(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('b2c_payment'), $data);
        return $this->handleResponse($response);
    }

    /**
     * Initiates a Business to Business (B2B) payment
     *
     * @param array{
     *     primaryShortCode: numeric-string,    # Organization sending funds
     *     receiverShortCode: numeric-string,   # Organization receiving funds
     *     amount: positive-int,                # Amount to transfer
     *     paymentRef: string,                  # Your reference for the transaction
     *     callbackUrl: string,                 # URL for payment notification
     *     partnerName: string,                 # Name of receiving organization
     *     RequestRefID: string                 # Unique identifier for this request
     * } $data Transaction parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     code: string,
     *     status: string
     * } Safaricom API response
     */
    public function b2b(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('b2b_payment'), $data);
        return $this->handleResponse($response);
    }

    /**
     *Register URLs for C2B (Customer to Business) payments
     *
     * @param array{
     *     ShortCode: numeric-string,               # Your organization's shortcode
     *     ResponseType: "Completed"|"Canceled",    # Response type for validation
     *     ConfirmationURL: string,                 # URL to receive payment confirmations
     *     ValidationURL: string                    # URL to validate payments
     * } $data URL registration parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     OriginatorCoversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string
     * } Registration response
     */
    public function c2bRegister(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('c2b_register'), $data);
        return $this->handleResponse($response);
    }

    /**
     * Simulate a C2B (Customer to Business) payment (Test environment only)
     *
     * @param array{
     *     ShortCode: numeric-string,                                       # Organization receiving payment
     *     CommandID: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline",     # Type of C2B transaction
     *     Amount: positive-int,                                            # Amount to be paid
     *     Msisdn: numeric-string,                                          # Phone number making payment
     *     BillRefNumber: string                                            # Account reference number
     * } $data Simulation parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     OriginatorConversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string
     * } Simulation response
     */
    public function c2bSimulate(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('c2b_simulate'), $data);
        return $this->handleResponse($response);
    }

    /**
     * Query account balance
     *
     * @param array{
     *     Initiator: string,                   # Name of the initiator
     *     SecurityCredential: string,          # Base64 encoded security credential
     *     CommandID: "AccountBalance",         # Command ID for balance query
     *     PartyA: numeric-string,              # Organization checking balance
     *     IdentifierType: "1"|"2"|"4",         # Type of organization
     *     Remarks: string,                     # Comments about the query
     *     QueueTimeOutURL: string,             # Timeout notification URL
     *     ResultURL: string                    # Success notification URL
     * } $data Query parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     OriginatorConversationID: string
     *     ConversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string,
     * } Balance query response
     */
    public function accountBalance(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('account_balance'), $data);
        return $this->handleResponse($response);
    }

    /**
     * Check the status of a transaction
     *
     * @param array{
     *     Initiator: string,                       # Name of the initiator
     *     SecurityCredential: string,              # Base64 encoded security credential
     *     CommandID: "TransactionStatusQuery",     # Command ID for status check
     *     TransactionID: string,                   # M-Pesa transaction ID
     *     PartyA: numeric-string,                  # Organization's shortcode
     *     IdentifierType: "1"|"2"|"4",             # Type of organization
     *     ResultURL: string,                       # Success notification URL
     *     QueueTimeOutURL: string,                 # Timeout notification URL
     *     Remarks: string,                         # Comments about the query
     *     Occasion: string                         # Optional description
     * } $data Query parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     OriginatorConversationID: string
     *     ConversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string,
     * } Transaction status response
     */
    public function transactionStatus(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('transaction_status'), $data);
        return $this->handleResponse($response);
    }

    /**
     * Reverse a completed M-Pesa transaction
     *
     * @param array{
     *     Initiator: string,                # Name of the initiator
     *     SecurityCredential: string,        # Base64 encoded security credential
     *     CommandID: "TransactionReversal",  # Command ID for reversal
     *     TransactionID: string,            # M-Pesa transaction ID to reverse
     *     Amount: positive-int,             # Amount to reverse
     *     ReceiverParty: numeric-string,    # Organization receiving the reversal
     *     ReceiverIdentifierType: "1"|"2"|"4",  # Type of receiving organization
     *     ResultURL: string,                # Success notification URL
     *     QueueTimeOutURL: string,          # Timeout notification URL
     *     Remarks: string,                  # Comments about the reversal
     *     Occasion: string                  # Optional description
     * } $data Reversal parameters
     * 
     * @throws MpesaException When the API request fails
     * 
     * @return object{
     *     OriginatorConversationID: string
     *     ConversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string,
     * } Reversal response
     */
    public function reversal(array $data): object
    {
        $response = $this->client()->post($this->getEndpoint('reversal'), $data);
        return $this->handleResponse($response);
    }

    /**
     * @param string $shortcode
     * @param string $passkey
     * @return string
     */
    private function generatePassword(string $shortcode, string $passkey): string
    {
        $timestamp = now()->format('YmdHis');
        return base64_encode($shortcode . $passkey . $timestamp);
    }

    /**
     * @param Response $response
     * @return object
     * @throws MpesaException
     */
    private function handleResponse(Response $response): object
    {
        if (!$response->successful()) {
            throw new MpesaException('Mpesa API request failed: ' . $response->body());
        }

        return $response->object();
    }
}
