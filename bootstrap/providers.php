<?php

use App\Providers\AppServiceProvider;
use App\Providers\AstraOsServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PulseServiceProvider;
use App\Providers\QueryOptimizationServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    AstraOsServiceProvider::class,
    HorizonServiceProvider::class,
    PulseServiceProvider::class,
    QueryOptimizationServiceProvider::class,
    TelescopeServiceProvider::class,
];
