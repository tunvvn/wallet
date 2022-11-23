<?php

namespace Tunv\Wallet\Traits;

use App\Base\Wallet\WalletTransaction;
use Illuminate\Database\Eloquent\Model;

class PurchaseTransaction extends Model implements WalletTransaction
{
    public function getAmount() 
    {
        return $this->amount;
    }
}