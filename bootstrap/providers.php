<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    Laravel\Fortify\FortifyServiceProvider::class,
    Laravel\Cashier\CashierServiceProvider::class,
];
