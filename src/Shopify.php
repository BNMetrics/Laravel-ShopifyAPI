<?php

namespace BNMetrics\Shopify;

use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use BNMetrics\Shopify\Traits\ResponseOptions;
use BNMetrics\Shopify\Contracts\ShopifyContract;

class Shopify implements ShopifyContract
{

    use ResponseOptions;

    protected $user;

    protected $requestPath;

    protected $apiCall;

    protected $shopifyAuth;

    protected $httpClient;


    /**
     * Shopify constructor.
     * @param Object ShopifyAuth $shopifyAuth
     */
    public function __construct(ShopifyAuth $shopifyAuth)
    {
        $this->shopifyAuth = $shopifyAuth;
    }

    /**
     * Set the shop Url and request Path, for the first time installation
     *
     * @param String $shopURL
     * @param Array $scope
     *
     * @return $this
     */
    public function make($shopURL, array $scope)
    {

        $allScope = $this->getAllScopes();

        if (!array_intersect( $allScope, $scope ) == $scope) {
            throw New InvalidArgumentException( 'invalid Scope' );
        }

        $this->apiCall = $this->shopifyAuth->stateless()->setShopURL( $shopURL )->scopes( $scope );

        $this->requestPath = $this->shopifyAuth->requestPath();

        return $this;
    }


    /**
     * Alternative to make()
     * To retrive a shop information by the ShopURL and the Access Token
     *
     * Method chain starts either as Shopify::make() or Shopify::retrieve()
     *
     * @param $shopURL
     * @param $token
     * @return $this
     */
    public function retrieve($shopURL, $token)
    {
        $this->apiCall = $this->shopifyAuth->stateless()->setShopURL( $shopURL );

        $this->requestPath = $this->shopifyAuth->requestPath();

        $this->user = $this->apiCall->userFromToken($token);

        return $this;
    }

    /**
     * @return $this with validated user info
     */
    public function auth()
    {
        $this->user = $this->apiCall->user();

        return $this;
    }

    /**
     * get user object, auth() has to be called first
     *
     * @return Object
     */
    public function getUser()
    {
        if($this->user != null) return $this->user;
        else throw new Exception("Must authenticate first!");
    }


    /**
     * Get the response from Shopify API Call
     *
     * @param string $endpoint
     * @param optional $params
     * @return API response in JSON
     *
     */
    public function get($endpoint, $params = null)
    {

        return $this->APICallWithoutOptions('get', $endpoint, $params);
    }


    /**
     * Remove a specific item from the database.
     *
     * @param $endpoint
     * @return mixed, API response in JSON
     */
    public function delete($endpoint, $params = null)
    {
        return $this->APICallWithoutOptions('delete', $endpoint, $params);
    }


    /**
     *
     *
     * @param $endpoint
     * @param $options
     * @return mixed, API response in JSON
     */
    public function modify($endpoint, $options, $params = null)
    {
        return $this->APICallWithOptions('put',$endpoint, $options, $params);
    }


    /**
     * Create an item
     *
     * @param $endpoint
     * @param $options
     * @return, API response in JSON
     */
    public function create($endpoint, $options, $params = null)
    {
        return $this->APICallWithOptions('post',$endpoint, $options, $params);
    }


    /**
     *
     * API call function for endpoints requires no request body to be passed
     * $params is optional for specific GET request
     *
     * @param $requestType
     * @param $endpoint
     * @param null $params
     * @return mixed
     */
    protected function APICallWithoutOptions($requestType, $endpoint, $params = null)
    {
        $response = $this->getHttpClient()->{$requestType}($this->buildRequestUrl($endpoint, $params),
            [
                'headers' => $this->getResponseHeaders($this->user->token)
            ]);

        $return = json_decode($response->getBody(), true);

        return $return;

    }

    /**
     * API call function for endpoints that require request body
     *
     *
     * @param $requestType
     * @param $endpoint
     * @param $options
     * @return mixed
     */
    protected function APICallWithOptions($requestType, $endpoint, $options,  $params = null)
    {
        $postkey = $this->httpClientVersionCheck();


        $response = $this->getHttpClient()->{$requestType}($this->buildRequestUrl($endpoint, $params), [

            'headers' => $this->getResponseHeaders($this->user->token),
            $postkey =>  $options

        ]);

        $return = json_decode($response->getBody(), true);

        return $return;

    }

    /**
     * Get the request url parameters
     *
     * @param array|null $params
     * @return null|string
     */
    protected function getParams(array $params = null)
    {
        if ($params == null) return null;
        return '?' . http_build_query( $params, '', '&' );
    }


    /**
     *  Build the URL for the specific API request
     *
     * @param string $endpoint
     * @param optional $params
     * @return string
     * @throws Exception
     */
    protected function buildRequestUrl($endpoint, $params = null)
    {
        if($this->user == null)
            throw new Exception("Please authenticate user first!");

        $requestPath = $this->requestPath . $endpoint . ".json". $this->getParams( $params );

        return $requestPath;
    }

    /**
     * Get all the scopes from shopify API
     *
     * @return mixed
     */
    protected function getAllScopes()
    {
        return config( 'shopify.scopes' );
    }

    /**
     * this method is for when you need to make an embedded shopify app
     *
     * @return string
     */
    public function getAuthUrl()
    {
        $this->shopifyAuth->fetchAuthUrl();
    }

    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect()
    {
        return $this->shopifyAuth->redirect();
    }

    /**
     * get the request path of the requested shop
     * 'example.myshopify.com/admin/'
     *
     * @return string
     */
    public function getRequestPath()
    {
        return $this->requestPath;
    }


    /**
     * get the API result for the specific endpoints
     *
     * @param $name Method name. eg. "getProductAll"
     * @param null $parseArgs. optional; ids, options, filter params.
     * @return mixed
     *
     */
    public function __call($name, $args = null)
    {
        $endpoints = new Endpoints;

        $currAction = $endpoints->callbackAction($name);

        $parseArrs = array_values(
            array_filter($args, function($e) {
                return is_array($e) ? $e : null;
            })
        );

        /*
         * retrieve other params such as tier1 id and tier2 id, and also
         * the additional uri pieces that are not under generic
         * /count, /{id}
         *
         */
        $parseArgsSerialized = array_diff(array_map('serialize', $args),
            array_map('serialize',$parseArrs));
        $unserialized = array_map('unserialize', $parseArgsSerialized);


        $parseArgs = [];
        foreach($unserialized as $items) {
            if(is_integer($items)) $parseArgs[] = $items;
            else $additionalUri = '/' . $items;
        }

        if(isset($additionalUri)) $endpoint = $endpoints->$name(...$parseArgs) . $additionalUri;
        else $endpoint = $endpoints->$name(...$parseArgs);

        /*
         * set the options and params to be passed
         */
        $options = null;
        $params = null;
        foreach($parseArrs as $arr)
        {
            if(array_keys($arr)[0] == $endpoints->categoryKey) $options = $arr;
            else $params = $arr;
        }


        // call get(), create(), modify() and delete() methods
        if($currAction == 'create' && $currAction == 'modify')
        {
            if($options == null) throw new Exception('invalid option passed');

            return $this->{$currAction}($endpoint, $options);
        }
        else return $this->{$currAction}($endpoint, $params);
    }

}