<?php

namespace BNMetrics\Shopify;


use BNMetrics\Shopify\Billing\ShopifyBillingManager;
use BNMetrics\Shopify\Contracts\BillingFactory;
use Illuminate\Support\ServiceProvider;


class BillingServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     * publish the config file
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register Shopify service.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton(
            BillingFactory::class, function($app) {
                return new ShopifyBillingManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [BillingFactory::class];
    }
}