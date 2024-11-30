<?php

namespace Botnetdobbs\Mpesa\Enums;

enum MpesaRequestType: string
{
    case STK_PUSH = 'stk_push';
    case STK_QUERY = 'stk_query';
    case B2C = 'b2c';
    case B2B = 'b2b';
    case C2B_REGISTER = 'c2b_register';
    case C2B_SIMULATE = 'c2b_simulate';
    case ACCOUNT_BALANCE = 'account_balance';
    case TRANSACTION_STATUS = 'transaction_status';
    case REVERSAL = 'reversal';
}
