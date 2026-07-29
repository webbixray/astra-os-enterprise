<?php

use App\Providers\AppServiceProvider;
use App\Providers\AstraOsServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    AstraOsServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
];
