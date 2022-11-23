<?php

namespace Tunv\Wallet\Providers;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Illuminate\Support\ServiceProvider;
class WalletServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('wallet')
            ->hasConfigFile()
            ->hasMigration('add_wallet_balance_column_to_model_table');
    }
}

// class WalletServiceProvider extends ServiceProvider
// {


//     public function boot()
//     {
//     }

//     public function register() 
//     {

//     }
// }
