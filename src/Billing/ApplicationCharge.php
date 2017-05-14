<?php
namespace BNMetrics\Shopify\Billing;

use BNMetrics\Shopify\Contracts\Billing;

class ApplicationCharge extends AbstractBilling implements Billing
{
    protected $requiredProperties = ['name', 'price'];

    protected $chargeType = 'application_charge';

    /**
     * get the charge endpoint for application charge option
     *
     * @return string
     */
    protected function getChargeEndpoint ()
    {
        return $this->requestPath . 'application_charges';
    }
}