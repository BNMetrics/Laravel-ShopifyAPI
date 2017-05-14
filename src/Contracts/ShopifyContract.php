<?php

namespace BNMetrics\Shopify\Contracts;

interface ShopifyContract
{
    /**
     * @param $shopURL
     * @param array $scope
     * @return mixed
     */
    public function make($shopURL, array $scope);

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect();


    /**
     * @return $this with validated user info
     *
     */
    public function auth();
}