<?php

namespace Botnetdobbs\Mpesa\Contracts;

interface Client
{
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
    public function stkPush(array $data): object;

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
    public function stkQuery(array $data): object;

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
    public function b2c(array $data): object;

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
    public function b2b(array $data): object;

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
    public function c2bRegister(array $data): object;

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
    public function c2bSimulate(array $data): object;

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
    public function accountBalance(array $data): object;

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
    public function transactionStatus(array $data): object;

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
    public function reversal(array $data): object;
}