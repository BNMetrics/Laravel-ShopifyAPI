<?php

namespace BNMetrics\Shopify\Facade;


use BNMetrics\Shopify\Contracts\BillingFactory;
use Illuminate\Support\Facades\Facade;

class BillingFacade extends Facade
{

    protected static function getFacadeAccessor ()
    {
        return BillingFactory::class;
    }

}