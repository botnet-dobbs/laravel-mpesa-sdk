<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Botnetdobbs\Mpesa\Contracts\Response;

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
     * @return Response
     */
    public function stkPush(array $data): Response;

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
    public function stkQuery(array $data): Response;

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
    public function b2c(array $data): Response;

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
    public function b2b(array $data): Response;

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
    public function c2bRegister(array $data): Response;

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
    public function c2bSimulate(array $data): Response;

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
    public function accountBalance(array $data): Response;

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
    public function transactionStatus(array $data): Response;

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
    public function reversal(array $data): Response;
}
