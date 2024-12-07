<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Botnetdobbs\Mpesa\Contracts\Callbacks\{
    StkCallback,
    B2CCallback,
    TransactionStatusCallback,
    AccountBalanceCallback,
    ReversalCallback
};
use Illuminate\Http\Request;

interface CallbackProcessor
{
    public function handleStkCallback(Request $request): StkCallback;

    public function handleB2CCallback(Request $request): B2CCallback;

    public function handleTransactionStatusCallback(Request $request): TransactionStatusCallback;

    public function handleAccountBalanceCallback(Request $request): AccountBalanceCallback;

    public function handleReversalCallback(Request $request): ReversalCallback;
}
