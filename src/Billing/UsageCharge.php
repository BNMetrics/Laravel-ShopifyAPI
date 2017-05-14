<?php
namespace BNMetrics\Shopify\Billing;


use BNMetrics\Shopify\Contracts\Billing;

class UsageCharge extends AbstractBilling implements Billing
{

    protected $requiredProperties = ['description', 'price'];

    protected $chargeType = 'usage_charge';

    protected $baseId;


    /**
     * set the recurring base charge
     *
     * @param object RecurringBilling $recurringBase
     * @return $this
     * @throws \Exception
     */
    public function setBase(RecurringBilling $recurringBase)
    {
        $baseCharge = $recurringBase->activated['recurring_application_charge'];

        if(!in_array('capped_amount', array_keys($baseCharge)))
            throw new \Exception("'capped_amount' and 'terms' must be included in the base recurring charge as an option");

        $this->baseId = $baseCharge['id'];

        return $this;
    }

    /**
     * get the charge endpoint, based on the type of billing method
     *
     * @return string
     */
    protected function getChargeEndpoint ()
    {
        return $this->requestPath . 'recurring_application_charges/' . $this->baseId . '/'. 'usage_charges';
    }


}