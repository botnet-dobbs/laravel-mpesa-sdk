<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Botnetdobbs\Mpesa\Exceptions\MpesaException;

interface Client
{
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
     *     BusinessShortCode: numeric-string,
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
    public function stkQuery(array $data): object;

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
    public function b2b(array $data): object;

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
    public function c2bRegister(array $data): object;

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
    public function c2bSimulate(array $data): object;

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
     * @return object{
     *     OriginatorConversationID: string,
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
     * @return object{
     *     OriginatorConversationID: string,
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
     * @return object{
     *     OriginatorConversationID: string,
     *     ConversationID: string,
     *     ResponseCode: string,
     *     ResponseDescription: string,
     * } Reversal response
     */
    public function reversal(array $data): object;
}
