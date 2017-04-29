<?php

namespace BNMetrics\Shopify;

use Exception;

class Shopify extends ShopifyAuth
{
    protected $user;

    protected $requestPath;

    protected $apiCall;


    /**
     * Set the subdomain and request Path
     *
     * @param String $subdomain
     * @param Array $scope
     *
     * @return $this
     */
    public function make($subdomain, array $scope)
    {

        $allScope = $this->getAllScopes();

        if (!array_intersect( $allScope, $scope ) == $scope) {
            throw New InvalidArgumentException( 'invalid Scope' );
        }

        $this->apiCall = $this->subdomain( $subdomain )->scopes( $scope );
        $this->requestPath = $this->requestPath();

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
                                            $this->getResponseOptions($this->user->token));

        $return = json_decode($response->getBody(), true);

        return $return;
    }


    /**
     *
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

}