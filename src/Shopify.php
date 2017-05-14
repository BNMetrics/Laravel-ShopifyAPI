<?php

namespace BNMetrics\Shopify;

use Exception;
use GuzzleHttp\Client;
use BNMetrics\Shopify\Contracts\ShopifyContract;


class Shopify implements ShopifyContract
{
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
     * Set the shop Url and request Path
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
     * @param str $endpoint
     * @param optional $params
     * @return API response in JSON
     *
     */
    public function response($endpoint, $params = null)
    {

        if($this->user == null)
            throw new Exception("Please authenticate user first!");

        $responsePath = $this->requestPath . $endpoint . $this->getParams( $params );

        $response = $this->getHttpClient()->get( $responsePath,
                                            $this->shopifyAuth->getResponseOptions($this->user->token));

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

}