<?php

namespace BNMetrics\Shopify;

use Illuminate\Http\Request;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class ShopifyAuth extends AbstractProvider
{

    protected $subdomain;

    protected $adminPath = ".myshopify.com/admin/";

    protected $userUrl;

    protected $requestPath;

    /**
     * Set the subdomain for the API request
     *
     * @param Request $subdomain
     * @return $this
     */
    public function subdomain($subdomain)
    {
        $this->subdomain = $subdomain;

        return $this;
    }

    /**
     * Get the API request path
     *
     * @return string
     */
    public function requestPath()
    {
        if($this->subdomain != null)
            $this->requestPath = 'https://' . $this->subdomain . $this->adminPath;

        return $this->requestPath;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        $url =  $this->requestPath()."oauth/authorize";

        return $this->buildAuthUrlFromBase( $url, $state );
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        // 'https://example.myshopify.com/admin/oauth/access_token'
        return 'https://' . $this->subdomain . $this->adminPath . "oauth/access_token";
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://' . $this->subdomain . $this->adminPath . "shop.json";


        $response = $this->getHttpClient()->get(
            $userUrl, $this->getResponseOptions($token));

        $user = json_decode($response->getBody(), true);

        return $user['shop'];
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array $user
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['name'],
            'name' => $user['domain'],
            'email' => $user['email'],
            'avatar' => null
        ]);
    }


    /**
     * Get the request header with the access_token
     *
     * @param $token
     * @return array
     */
    public function getResponseOptions($token)
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
                'X-Shopify-Access-Token' => $token,
            ]

        ];
    }
}