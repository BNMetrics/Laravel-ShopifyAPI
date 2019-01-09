# Laravel Shopify API Wrapper
[![Build Status](https://travis-ci.org/BNMetrics/Laravel-ShopifyAPI.svg?branch=master)](https://travis-ci.org/BNMetrics/Laravel-ShopifyAPI)
[![Latest Stable Version](https://poser.pugx.org/bnmetrics/laravel-shopify-api/v/stable)](https://packagist.org/packages/bnmetrics/laravel-shopify-api)

This Package provides a easy way for you to building [Shopify](https://www.shopify.com/?ref=developer-886210bf83bd9c41) Apps with Laravel 5. The OAuth authentication is extended upon Laravel's Socialite.

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
php artisan vendor:publish --provider='BNMetrics\Shopify\ShopifyServiceProvider'
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
Now, you are ready to make a shopify app! here is an example of how it might be used in a controller to retrieve information from a shop via GET request to the API endpoint:
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
    $this->foo = Shopify::make($this->shop, $this->scopes);
    return $this->foo->redirect();
  }
  
  public function getResponse(Request $request)
  {
    $this->getPermission();
    
    // Get user data, you can store it in the data base
    $user = $this->foo->auth()->getUser();
    
    //GET request to products.json
    return $this->foo->auth()->get('products', ['fields'=>'id,images,title']);
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

## CRUD requests to the API endpoints
As of version 1.0.3, GET, PUT, POST, DELETE requests are supported for this package. To make requests, simply call the respective methods following the URL structure of of each endpoints, adding suffix such as "All", "Count", "ById".

API endpoints are referred to as "tiers", for example, 
- products endpoints('admin/products.json', 'admin/products/{id}.json') are tier 1;

- productsImages endpoints('admin/products/{product_id}/images.json', 'admin/products/{product_id}/images/{image_id}.json') are tier 2;

- Currently, only ordersFulfillmentsEvents have tier 3 ('admin/orders/{order_id}/fulfilments/{fulfillment_id}/events.json', 'admin/orders/{order_id}/fulfilments/{fulfillment_id}/events/{event_id}.json');

Below is an example of how to make requests to endpoints:

```php
//assuming $shop is already oAuth verified Shopify.php object

//GET request
$shop->getProductsAll();
$shop->getProductsById(2324564);


//POST Request
//Options to be passed as request body are manditory for some API endpoints
$options = [
'product' => [
    'title' => 'my cool products',
    'body_html' => '<p> my cool product! </p>',
    'vendor' => 'My Shopify Shop',
    'images' => [
         'src' => 'https://example.com/my_product.jpg'
    ],
  ]
];
$shop->createProducts($options);

//PUT (upload/update an image source)
$modifyOptions = [
   'asset' => [
       'key' => 'assets/example.jpg',
       'src' => 'https://www.example.com/example.jpg'
   
   ]
];
$shop->modifyThemesAssets($modifyOptions);



//DELETE
$productID = 121421;
$imageID = 323546;
$shop->deleteProductsImages($productID, $imageID);
```

### Config file
More endpoints can also be added to ``shopify.php`` config file $endpoints array following the same pattern. 
- First Tier names should be the array key of the first dimension, and the array values are tier 2;
- if the endpoint has a tier 3 endpoint, add a key ``'tier 3'``;
- if the tier 2 endpoint node does not need a tier 1 ID to be accessed, the endpoint can be indicated in the  ``'tierTwoWithoutId'`` array.


## Billing
As of version 1.0.2, billing support has been added to this package. It supports all three of the billing options shopify provides:
RecurringCharge, ApplicationCharge and UsageCharge.


If you are to use Billing for your Shopify app, Add the following onto your ``config/app.php`` file:

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




