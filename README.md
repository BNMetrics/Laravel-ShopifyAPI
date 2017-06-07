# Laravel Shopify API Wrapper
[![Build Status](https://travis-ci.org/BNMetrics/Laravel-ShopifyAPI.svg?branch=master)](https://travis-ci.org/BNMetrics/Laravel-ShopifyAPI)
[![Latest Stable Version](https://poser.pugx.org/bnmetrics/laravel-shopify-api/v/stable)](https://packagist.org/packages/bnmetrics/laravel-shopify-api)

This Package provides a easy way for you to building Shopify Apps with Laravel 5. The OAuth authentication is extended upon Laravel's Socialite.

### This package supports both public and private apps, including billing.
 
## Installation

You can install this package via composer with:

```bash
composer require bnmetrics/laravel-shopify-api

```
Or add to your Laravel project composer.json file:

```json
"require": {

    "bnmetrics/laravel-shopify-api" : "~1.0",
}
```
To publish the shopify.php configuration file to `app/config` run:
```bash
php artisan vendor:publish --tag=shopify
```

## Configuration

Set shopify environment variables your .env file:
```env
SHOPIFY_KEY=YOUR_API_KEY_HERE
SHOPIFY_SECRET=YOUR_API_SECRET_HERE
SHOPIFY_REDIRECT=https://app.example.com/oauth/authorize
```

Register the service provider in your `app/config/app.php`:

```php
"providers" => [
   // other providers...
   BNMetrics\Shopify\ShopifyServiceProvider::class,
]
```
Also add the `Shopify` facade in your `aliases` array in `app/config/app.php`:

```php
"aliases" => [
   // other facades...
   'Shopify' => BNMetrics\Shopify\Facade\ShopifyFacade::class,
] 
```

## Basic Usage
Now, you are ready to make a shopify app! here is an example of how it might be used in a controller to retrive information from a shop:
```php
<?php

namespace App\Http\Controllers;

use Shopify;

Class myShopify extends Controller
{
  protected $shop = "example.myshopify.com";
  protected $foo;
  protected $scopes = ['read_products','read_themes'];
  
  public function getPermission()
  {
    $this->foo = Shopify::make($this->shop, $this->scope);
    return $this->foo->redirect();
  }
  
  public function getResponse(Request $request)
  {
    $this->getPermission();
    
    // Get user data, you can store it in the data base
    $user = $this->foo->auth()->getUser();
    
    //GET request to products.json
    return $this->foo->auth()->get('products.json', ['fields'=>'id,images,title']);
  }
}
```

Alternatively, if you already have a token to a specific shopify domain.
You can retrieve the API response by using the retrieve() method like so:
```php
$this->foo = Shopify::retrieve($this->shop, $access_token);

//Get the user information
$user = $this->foo->getUser();
```


Next you will need to make two routes:
```php
Route::get('/oauth/authorize', 'myshopify@getResponse');
Route::get('/shopify', 'myShopify@getPermission');
```

## Billing
As of version 1.0.2, billing support has been added to this package. It supports all three of the billing options shopify provides:
RecurringCharge, ApplicationCharge and UsageCharge.


If you are to use Billing for your Shopify app, Add the following onto your ``config/add.php`` file:

Register the service provider in your `app/config/app.php`:

```php
"providers" => [
   // other providers...
   BNMetrics\Shopify\BillingServiceProvider::class,
]
```
Also add the `ShopifyBilling` facade in your `aliases` array in `app/config/app.php`:

```php
"aliases" => [
   // other facades...
   'ShopifyBilling' => BNMetrics\Shopify\Facade\BillingFacade::class,
] 
```

## Billing Usage
As all shopify charges will need to be activated after creation, we will add a route called activate:
```php
Route::get('activate', 'myShopify@activate');
```

For our previous example controller, you can change the ``getResponse`` method like so:
```php
  public function getResponse(Request $request)
  {
    $this->getPermission();
    
    // Get user data, you can store it in the data base
    $user = $this->foo->auth()->getUser();
    
    $return_url = url('activate');
    $options = ['name'=>'my awesome App', 'price' => '10',
                'return_url' => $return_url,
                ];
    
    // Redirect the user to the myshopify page to approve the charge
    //Saving user into the session for the activate method
    return \ShopifyBilling::driver('RecurringBilling')
                            ->create($this->user, $options)->redirect()->with('user', $this->user);      
  }
```
create() method accepts an validated Shopify object or a User Object, and the options for the charge.
Refer to the Property section for different options you can pass in:

[RecurringBilling](https://help.shopify.com/api/reference/recurringapplicationcharge),
[ApplicationCharge](https://help.shopify.com/api/reference/applicationcharge),
[UsageCharge](https://help.shopify.com/api/reference/usagecharge),

Then we would need to add the activate method():
```php
public function activated(Request $request)
{
    $user = $request->session()->get('user');
    $activated = \ShopifyBilling::driver('RecurringBilling')
            ->activate($user->name, $user->token, $request->get('charge_id'));
    
    return redirect('/myapp-homepage');
}
```
activate method handles all "status", not only 'accepted', but also when user declines the charge;

### Usage Charge
For usage charges, as it is based uppon an existing recurring charge, an activated recurring charge instance must be passed before creating a usage charge.

The recurring charge also require to have 'capped_amount' and 'terms'.

We can modify our activate() method in our controller like so:
```php
public function activated(Request $request)
{
    $user = $request->session()->get('user');
    $activated = \ShopifyBilling::driver('RecurringBilling')
            ->activate($user->name, $user->token, $request->get('charge_id'));
    
    $usageOption = [ 'price' => '10', 'description' => 'my awesome app description'];
    
    \ShopifyBilling::driver('UsageCharge')->setBase($activated)->create($user, $usageOption);
    
    return redirect('/myapp-homepage');

}
```




