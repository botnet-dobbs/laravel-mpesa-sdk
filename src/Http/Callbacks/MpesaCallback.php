<?php

namespace Botnetdobbs\Mpesa\Http\Callbacks;

use Botnetdobbs\Mpesa\Contracts\CallbackProcessor;
use Botnetdobbs\Mpesa\Contracts\TransactionResult;
use Botnetdobbs\Mpesa\Data\Callbacks\MpesaTransactionResult;
use Illuminate\Http\Request;

class MpesaCallback implements CallbackProcessor
{
    public function handle(Request $request): TransactionResult
    {
        return new MpesaTransactionResult($request->all());
    }
}
