<?php

namespace Botnetdobbs\Mpesa\Facades;

use Illuminate\Support\Facades\Facade;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;

/**
 * @package Botnetdobbs\Mpesa\Facades
 *
 * @method static object{MerchantRequestID: string, CheckoutRequestID: string, ResponseCode: string, ResponseDescription: string, CustomerMessage: string} stkPush(array{BusinessShortCode: numeric-string, Passkey: string, TransactionType: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline", Amount: positive-int, PartyA: numeric-string, PartyB: numeric-string, PhoneNumber: numeric-string, CallBackURL: string, AccountReference: string, TransactionDesc: string} $data) Initiates an STK Push request (Lipa Na M-Pesa Online)
 * 
 * @method static object{ResponseCode: string, ResponseDescription: string, MerchantRequestID: string, CheckoutRequestID: string, ResultCode: string, ResultDesc: string} stkQuery(array{BusinessShortCode: numeric-string, Passkey: string, CheckoutRequestID: string} $data) Query the status of an STK Push transaction
 * 
 * @method static object{ConversationID: string, OriginatorConversationID: string, ResponseCode: string, ResponseDescription: string} b2c(array{OriginatorConversationID: string, InitiatorName: string, SecurityCredential: string, CommandID: "SalaryPayment"|"BusinessPayment"|"PromotionPayment", Amount: positive-int, PartyA: numeric-string, PartyB: numeric-string, Remarks: string, QueueTimeOutURL: string, ResultURL: string, Occasion: string} $data) Initiates a Business to Customer (B2C) payment
 * 
 * @method static object{code: string, status: string} b2b(array{primaryShortCode: numeric-string, receiverShortCode: numeric-string, amount: positive-int, paymentRef: string, callbackUrl: string, partnerName: string, RequestRefID: string} $data) Initiates a Business to Business (B2B) payment
 * 
 * @method static object{OriginatorConversationID: string, ResponseCode: string, ResponseDescription: string} c2bRegister(array{ShortCode: numeric-string, ResponseType: "Completed"|"Canceled", ConfirmationURL: string, ValidationURL: string} $data) Register URLs for C2B (Customer to Business) payments
 * 
 * @method static object{OriginatorConversationID: string, ResponseCode: string, ResponseDescription: string} c2bSimulate(array{ShortCode: numeric-string, CommandID: "CustomerPayBillOnline"|"CustomerBuyGoodsOnline", Amount: positive-int, Msisdn: numeric-string, BillRefNumber: string} $data) Simulate a C2B (Customer to Business) payment (Test environment only)
 * 
 * @method static object{OriginatorConversationID: string, ConversationID: string, ResponseCode: string, ResponseDescription: string} accountBalance(array{Initiator: string, SecurityCredential: string, CommandID: "AccountBalance", PartyA: numeric-string, IdentifierType: "1"|"2"|"4", Remarks: string, QueueTimeOutURL: string, ResultURL: string} $data) Query account balance
 * 
 * @method static object{OriginatorConversationID: string, ConversationID: string, ResponseCode: string, ResponseDescription: string} transactionStatus(array{Initiator: string, SecurityCredential: string, CommandID: "TransactionStatusQuery", TransactionID: string, PartyA: numeric-string, IdentifierType: "1"|"2"|"4", ResultURL: string, QueueTimeOutURL: string, Remarks: string, Occasion: string} $data) Check the status of a transaction
 * 
 * @method static object{OriginatorConversationID: string, ConversationID: string, ResponseCode: string, ResponseDescription: string} reversal(array{Initiator: string, SecurityCredential: string, CommandID: "TransactionReversal", TransactionID: string, Amount: positive-int, ReceiverParty: numeric-string, ReceiverIdentifierType: "1"|"2"|"4", ResultURL: string, QueueTimeOutURL: string, Remarks: string, Occasion: string} $data) Reverse a completed M-Pesa transaction
 * 
 * @throws MpesaException When API request fails or validation errors occur
 * 
 * @see \Botnetdobbs\Mpesa\Http\MpesaClient
 */
class Mpesa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mpesa';
    }
}
