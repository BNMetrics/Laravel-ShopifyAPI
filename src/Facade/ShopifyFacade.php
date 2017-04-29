<?php

namespace BNMetrics\Shopify\Facade;


use BNMetrics\Shopify\Shopify;
use Illuminate\Support\Facades\Facade;

class ShopifyFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Shopify::class;
    }
}