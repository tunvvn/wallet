<?php

namespace Tunv\Wallet\Traits;
use Stephenjude\Wallet\Contracts\WalletTransaction;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Base\Wallet\Exceptions\InsufficientFundException;
use App\Base\Wallet\Exceptions\InvalidAmountException;
use Exception;
use Illuminate\Support\Facades\Session;
use DB;
use Illuminate\Support\Facades\Auth;
use App\DataTypes\Price;
use App\Hub\Http\Livewire\Traits\CreateUUID;

trait HasWallet
{

    public function deposit($transaction)
    {
        //dd($transaction);
        if (!$transaction instanceof Transaction) {
            throw new Exception(
                "Increment balance expects parameter to be a float or a Transaction object."
            );
        }
        // dd($transaction->actual_amount);
        $actual_amount = (float)$transaction->actual_amount;

        $this->throwExceptionIfAmountIsInvalid($actual_amount);
        // $this->throwExceptionIfFundIsInsufficient($actual_amount);

        $balance = $this->wallet_balance ?? 0;
        $balance += $transaction->actual_amount;
        $this->forceFill(["wallet_balance" => $balance])->save();

        $this->createTransactions($transaction, "deposit");

        return $balance;
    }

    public function withdraw($transaction)
    {
        if (!$transaction instanceof Transaction) {
            throw new Exception(
                "Increment balance expects parameter to be a float or a Transaction object."
            );
        }
        $actual_amount = $transaction->actual_amount;

        $this->throwExceptionIfAmountIsInvalid($actual_amount);

        $this->throwExceptionIfFundIsInsufficient($actual_amount);

        $balance = $this->wallet_balance - $actual_amount;

        $this->forceFill(["wallet_balance" => $balance])->save();

        $this->createTransactions($transaction, "withdraw");

        return $balance;
    }

    public function canWithdraw(int|float $amount): bool
    {
        $this->throwExceptionIfAmountIsInvalid($amount);

        $balance = $this->wallet_balance ?? 0;

        return $balance >= $amount;
    }

    public function balance(): Attribute
    {
        return Attribute::get(fn() => $this->wallet_balance ?? 0);
    }

    public function throwExceptionIfAmountIsInvalid(int|float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidAmountException();
        }
    }

    public function throwExceptionIfFundIsInsufficient(int|float $amount): void
    {
        if (!$this->canWithdraw($amount)) {
            throw new InsufficientFundException();
            //            Session::flash('error', 'Unable to process request.Error:');
        }
    }

    private function createTransactions($transaction, $type)
    {
        $typeOfLedger = "withdraw";
        $amountInWalletUser = new Price(0, $transaction->amount->currency, 1);
        $amountInWalletUser->value = $transaction->amount->value;
        $actualAmountUser = $transaction->actual_amount;
        if ($type === "withdraw") {
            $amountInWalletUser->value = -$transaction->actual_amount;
            $actualAmountUser = -$transaction->actual_amount;
            $typeOfLedger = "deposit";
        }
        $queryWalletLedger = DB::table("getcandy_staff")->where(
            "email",
            env("WALLET_LEDGER_EMAIL", "wallet-ledger@mail.com")
        );
        if(count($queryWalletLedger->get())!=1){
            throw new Exception("Hadn't wallet ledger wallet-ledger@mail.com ");
        }
        $newBalance =
            $queryWalletLedger->first()->wallet_balance -
            $transaction->actual_amount;
        $queryWalletLedger->update([
            "wallet_balance" => $newBalance,
        ]);

        $comment = new Transaction();
        $comment->order_id = $transaction->order->id;
        $comment->parent_transaction_id = null;
        $comment->amount = $amountInWalletUser;
        $comment->meta = $transaction->meta;
        $comment->type = $type;
        $comment->success = true;
        $comment->uuid = $transaction->uuid;
        $comment->driver = $transaction->driver;
        $comment->isLedger = false;
        $comment->actual_amount = $actualAmountUser;
        $comment->required_amount = $actualAmountUser;

        $comment->type_exchange_rate = $transaction->type_exchange_rate;
        $comment->exchange_rate_in_usd = $transaction->exchange_rate_in_usd;
        $comment->save();

        $amountInWalletLedger = new Price(0, $transaction->amount->currency, 1);
        $amountInWalletLedger->value = -$amountInWalletUser->value;
        $actualAmountInWalletLedger = -$actualAmountUser;

        $comment2 = new Transaction();
        $comment2->order_id = $transaction->order->id;
        $comment2->parent_transaction_id = null;
        $comment2->amount = $amountInWalletLedger;
        $comment2->type_exchange_rate = $transaction->type_exchange_rate;
        $comment2->exchange_rate_in_usd = $transaction->exchange_rate_in_usd;
        $comment2->meta = [
            "description" => "Transaction belongs to the ledger",
        ];
        $comment2->type = $typeOfLedger;
        $comment2->success = true;
        $comment2->uuid = $transaction->uuid . "_AD";
        $comment2->driver = $transaction->driver;
        $comment2->actual_amount = $actualAmountInWalletLedger;
        $comment2->required_amount = $actualAmountInWalletLedger;
        $comment2->isLedger = true;
        $comment2->save();

        // return  Transaction::query()->create([

        // 'order_id' =>  $transaction->order->id,
        // 'parent_transaction_id' => null,
        // 'amount' => $amountInWalletLedger,
        // 'type' => $typeOfLedger,
        // 'success' => true,
        // 'driver' => 'wallet',
        // 'reference' => null,
        // 'status' => null,
        // 'meta' => $transaction->meta,
        // 'uuid' => $transaction->uuid,
        // 'isLedger' => true,
        // 'type_exchange_rate' =>$transaction->type_exchange_rate,
        // 'exchange_rate_in_usd' =>$transaction->exchange_rate_in_usd,

        // ]);
    }
}
