<?php

namespace BNMetrics\Shopify\Billing;

use BNMetrics\Shopify\Contracts\Billing;

class RecurringBilling extends AbstractBilling implements Billing
{


    protected $requiredProperties = ['name', 'price'];

    protected $chargeType = 'recurring_application_charge';

    /**
     * Get the charge endpoint for recurring charge option
     *
     * @return string
     */
    protected function getChargeEndpoint()
    {

        return $this->requestPath . 'recurring_application_charges';
    }

    /**
     * delete a specific charge
     *
     * @param string $myshopify myshopify domain
     * @param string $token access_token
     * @param string $id chargeID
     * @return void
     *
     */
    public function delete($myshopify, $token, $id)
    {
        $this->setRequestPath($myshopify);

        $url = $this->getChargeEndpoint(). '/' . $id . '.json';

        $this->getHttpClient()->delete($url,
            [
                'headers' => $this->getResponseHeaders($token)
            ]);
    }

}