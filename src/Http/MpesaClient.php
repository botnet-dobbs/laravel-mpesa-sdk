<?php

namespace Botnetdobbs\Mpesa\Http;

use Botnetdobbs\Mpesa\Contracts\Client;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Client\Response;

class MpesaClient implements Client
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
        $this->cacheManager = app('cache'); // @phpstan-ignore-line
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
            ->baseUrl($this->getBaseUrl());
    }

    /**
     * Initiates an STK Push request (Lipa Na M-Pesa Online)
     *
     * @param array{
     *     BusinessShortCode: numeric-string,   
     *     Passkey: string,                     
     *     TransactionType: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline",  
     *     Amount: positive-int,                
     *     PartyA: numeric-string,              
     *     PartyB: numeric-string,              
     *     PhoneNumber: numeric-string,         
     *     CallBackURL: string,                 
     *     AccountReference: string,            
     *     TransactionDesc: string              
     * } $data
     * 
     * @throws MpesaException
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
     *     BusinessShortCode: numeric-string,   
     *     Passkey: string,                     
     *     CheckoutRequestID: string            
     * } $data Query parameters
     * 
     * @throws MpesaException
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
     *     OriginatorConversationID: string,    
     *     InitiatorName: string,               
     *     SecurityCredential: string,          
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
     *     ShortCode: numeric-string,               
     *     ResponseType: "Completed"|"Canceled",    
     *     ConfirmationURL: string,                 
     *     ValidationURL: string                    
     * } $data URL registration parameters
     * 
     * @throws MpesaException
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
     *     ShortCode: numeric-string,                                       
     *     CommandID: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline",     
     *     Amount: positive-int,                                            
     *     Msisdn: numeric-string,                                          
     *     BillRefNumber: string                                            
     * } $data Simulation parameters
     * 
     * @throws MpesaException
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
     *     Initiator: string,                   
     *     SecurityCredential: string,          
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
     * @return object{
     *     OriginatorConversationID: string,
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
     *     Initiator: string,                       
     *     SecurityCredential: string,              
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
     * @return object{
     *     OriginatorConversationID: string,
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
     *     Initiator: string,                
     *     SecurityCredential: string,        
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
     * @return object{
     *     OriginatorConversationID: string,
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
     * 
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
     * 
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
