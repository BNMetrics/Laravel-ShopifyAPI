<?php


namespace BNMetrics\Shopify\Billing;

use InvalidArgumentException;
use Illuminate\Support\Manager;
use BNMetrics\Shopify\Contracts\BillingFactory;

class ShopifyBillingManager extends Manager implements BillingFactory
{

    /**
     * Get a driver instance for the shopify billing type
     *
     * @param string $driver
     * @return mixed
     */
    public function with($driver)
    {
        return $this->driver($driver);
    }

    /**
     *  Create an instance of RecurringBilling
     *
     * @return RecurringBilling
     */
    public function createRecurringBillingDriver()
    {
        return new RecurringBilling;
    }

    /**
     * Create an Instance of ApplicationCharge
     *
     * @return ApplicationCharge
     */
    public function createAppilicationChargeDriver()
    {
        return new ApplicationCharge;
    }

    /**
     * create an instance of UsageCharge
     *
     * @return UsageCharge
     */
    public function createUsageChargeDriver()
    {
        return new UsageCharge;
    }


    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver ()
    {
        throw new InvalidArgumentException('No billing driver was specified.');
    }
}