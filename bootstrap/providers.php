<?php

use App\Providers\AppServiceProvider;
use App\Providers\AstraOsServiceProvider;
use App\Providers\DomainServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    AstraOsServiceProvider::class,
];
