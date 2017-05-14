<?php

namespace BNMetrics\Shopify\Contracts;

interface BillingFactory
{

    /**
     * Get the specific method for the shopify billing
     *
     * @param String $driver
     * @return \BNMetrics\Shopify\Contracts\Billing
     */
    public function driver($driver = null);
}