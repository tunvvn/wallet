<?php
namespace Tunv\Wallet\Interfaces;

interface Wallet
{
    // public function deposit(int|float $amount): int|float;
    public function deposit($transaction);
    public function withdraw($transaction);

    // public function withdraw(int|float $amount): int|float;


    public function canWithdraw(int|float $amount): bool;

    public function throwExceptionIfAmountIsInvalid(int|float $amount): void;

    public function throwExceptionIfFundIsInsufficient(int|float $amount): void;
}
