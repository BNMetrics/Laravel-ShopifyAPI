<?php

namespace BNMetrics\Shopify;

use Illuminate\Support\ServiceProvider;


class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     * publish the config file
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/shopify.php' => config_path('shopify.php')
        ], 'shopify');
    }

    /**
     * Register Shopify service.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton(
            Shopify::class, function($app) {

             return new Shopify($app['request'], config( 'shopify.key' ),
                                config( 'shopify.secret' ), config( 'shopify.redirectURL' ));

        });
    }
}
