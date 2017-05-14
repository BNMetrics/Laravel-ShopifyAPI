<?php

namespace BNMetrics\Shopify;

use Illuminate\Http\Request;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class ShopifyAuth extends AbstractProvider
{

    protected $shopURL;

    protected $adminPath = "/admin/";

    protected $userUrl;

    protected $requestPath;

    /**
     * Set the shop URL for the API request
     *
     * @param Request $shopURL
     * @return $this
     */
    public function setShopURL($shopURL)
    {
        $this->shopURL = $shopURL;

        return $this;
    }

    /**
     * Get the API request path
     *
     * @return string
     */
    public function requestPath()
    {
        if($this->shopURL != null)
            $this->requestPath = 'https://' . $this->shopURL . $this->adminPath;

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
        return 'https://' . $this->shopURL . $this->adminPath . "oauth/access_token";
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://' . $this->shopURL . $this->adminPath . "shop.json";


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
            'name' => $user['myshopify_domain'],
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

    /**
     * this method is for when you need to make an embedded shopify app
     *
     * @return string
     */
    public function fetchAuthUrl()
    {
        $state = $this->getState();

        $authUrl = $this->getAuthUrl($state);

        return $authUrl;
    }

}