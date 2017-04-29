# Laravel Shopify API Wrapper
This Package provides a easy way for you to building Shopify Apps with Laravel 5. The OAuth authentication is extended upon Laravel's Socialite.

## Installation

You can install this package via composer with:

```bash
composer require bnmetrics/laravel-shopify-api

```
Or add to your Laravel project composer.json file:

```json
"require": {
    ...
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
   ...
   BNMetrics\Shopify\ShopifyServiceProvider::class,
]
```
Also add the `Shopify` facade in your `aliases` array in `app/config/app.php`:

```php
"aliases" => [
   ...
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
  protected $subdomain = "example";
  protected $foo;
  protected $scopes = ['read_products','read_themes'];
  
  public function getPermission()
  {
    $this->foo = Shopify::make($this->subdomain, $this->scope);
    return $this->foo->redirect();
  }
  
  public function getResponse(Request $request)
  {
    $this->getPermission();
    
    //Note: Set the state the same as the session state to avoid InvalidStateException after redirection!
    $state = $request->get('state');
    $request->session()->put('state',$state);
    
    return $this->foo->auth()->response('products.json', ['fields'=>'id,images,title']);
  }
}
```
Note: As I have been encountering the InvalidStateException from Laravel Socialite, I have not yet found a way around except for setting the state the same as the session in the controller.

Next you will need to make two routes:
```php
Route::get('/oauth/authorize', 'myshopify@getResponse');
Route::get('/shopify', 'myShopify@getPermission');
```
Have fun!




