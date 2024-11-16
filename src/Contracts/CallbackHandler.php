<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Illuminate\Http\Request;

interface CallbackHandler
{
    public function handleStkCallback(Request $request): StkCallback;

    public function handleB2CCallback(Request $request): B2CCallback;

    public function handleTransactionStatusCallback(Request $request): TransactionStatusCallback;

    public function handleAccountBalanceCallback(Request $request): AccountBalanceCallback;

    public function handleReversalCallback(Request $request): ReversalCallback;
}
