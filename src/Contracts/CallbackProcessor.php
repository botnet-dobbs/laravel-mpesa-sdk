<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Illuminate\Http\Request;

interface CallbackProcessor
{
    public function handle(Request $request): TransactionResult;
}
