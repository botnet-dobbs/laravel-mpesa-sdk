<?php

namespace Botnetdobbs\Mpesa\Data\Callbacks\Traits;

trait MpesaCallbackHelper
{
     /**
     * @param string $balanceString
     *
     * @return array<int, array{Account: string, Currency: string, Amount: float}>
     */
    protected function parseBalanceString(string $balanceString): array
    {
        if (blank($balanceString)) {
            return []; // @codeCoverageIgnore
        }

        $accounts = explode('&', $balanceString);
        $balances = [];

        foreach ($accounts as $account) {
            [$account, $currency, $amount] = explode('|', $account);
            $balances[] = [
                'Account' => $account,
                'Currency' => $currency,
                'Amount' => (float) $amount
            ];
        }

        return $balances;
    }

    /**
     * @param array<int, array{Account: string, Currency: string, Amount: float}> $balances
     * @param string $accountName
     *
     * @return array{Account: string, Currency: string, Amount: float}|null
     */
    protected function getBalanceForAccountName(array $balances, string $accountName): ?array
    {
        return collect($balances)->where('Account', $accountName)->first();
    }
}
