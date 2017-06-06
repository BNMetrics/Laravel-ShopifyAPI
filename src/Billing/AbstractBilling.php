<?php

Namespace BNMetrics\Shopify\Billing;

use BNMetrics\Shopify\Traits\ResponseOptions;
use Exception;
use GuzzleHttp\Client;
use Laravel\Socialite\Two\User;
use BNMetrics\Shopify\Contracts\Billing;
use BNMetrics\Shopify\Contracts\ShopifyContract as Shopify;

abstract class AbstractBilling implements Billing
{
    use ResponseOptions;

    protected $options = [];

    protected $requiredProperties = [];

    protected $requestPath;

    protected $charge;

    protected $chargeType;

    protected $httpClient;

    protected $activated;


    /**
     * get the charge endpoint, based on the type of billing method
     *
     * @return string
     */
    abstract protected function getChargeEndpoint();


    /**
     * Create a Charge
     *
     * @param Object $authorized instanceof Shopify || User
     * @param array $options
     * @return $this
     * @throws Exception
     */
    public function create($authorized, array $options)
    {

        if($authorized instanceof Shopify)
        {
            if(!$authorized->getUser())
                throw new Exception('Cannot charge user without getting their installation permissions!');

            $this->setRequestPath( $authorized );
            $token = $authorized->getUser()->token;
        }
        elseif($authorized instanceof User)
        {
            $this->setRequestPath($authorized->name);
            $token = $authorized->token;
        }
        else throw new \InvalidArgumentException('Argumant must be an instance of Shopify or Laravel\Socialite\Two\User');

        $this->options = $options;


        $createURL = $this->getChargeEndpoint() . '.json';


        $this->charge = $this->getCreateChargeResponse($createURL, $token);

        return $this;
    }

    /**
     * get the Create Charge response for the given url and access token
     *
     * @param string $url
     * @param string $token
     * @return mixed
     */
    protected function getCreateChargeResponse($url, $token)
    {
        $postKey = $this->httpClientVersionCheck();

        $response = $this->getHttpClient()->post($url,
        [
            'headers'=> $this->getResponseHeaders($token),
            $postKey => $this->getOptions()
        ]);

        return json_decode($response->getBody(), true);
    }


    /**
     * Asking the user to confirm the charge
     * redirect the user to the confirmation URL on "myshopify.com" domain
     *
     */
    public function redirect()
    {
        return redirect($this->getCharge()[$this->chargeType]['confirmation_url']);
    }


    /**
     * retrieve a specific charge by id, myshopify domain and access token
     *
     * @param string $myshopify myshopify domain
     * @param string $token access_token
     * @param string $id chargeID
     * @return mixed
     *
     */
    public function getChargeById($myshopify, $token, $id)
    {
        $this->setRequestPath($myshopify);

        $url = $this->getChargeEndpoint(). '/' . $id . '.json';

        $response = $this->getHttpClient()->get($url,
        [
            'headers' => $this->getResponseHeaders($token)
        ]);

        return json_decode($response->getBody(), true);
    }


    /**
     * Get all the charges from a specific shop
     *
     * @param string $myshopify myshopify domain, eg. 'example.myshopify.com'
     * @param string $token, access_token
     * @return mixed
     */
    public function getAllCharges($myshopify, $token, $sinceId = null)
    {
        $this->setRequestPath($myshopify);

        if($sinceId != null && $this->chargeType == 'application_charge')
        {
            $url = $this->getChargeEndpoint(). '.json?since_id=' . $sinceId;
        }
        else $url = $this->getChargeEndpoint(). '.json';


        $response = $this->getHttpClient()->get($url,
        [
            'headers' => $this->getResponseHeaders($token)
        ]);

        return json_decode($response->getBody(), true);

    }


    /**
     * Activate a specific charge
     *
     * @param string $myshopify myshopify domain
     * @param string $token access token
     * @param string $id  charge id
     * @return mixed
     */
    public function activate($myshopify, $token, $id)
    {
        $this->setRequestPath($myshopify);

        $url = $this->getChargeEndpoint(). '/' . $id . '/activate.json';

        $postkey = $this->httpClientVersionCheck();

        $response = $this->getHttpClient()->post($url, [
            'headers' => $this->getResponseHeaders($token),
            $postkey => $this->getChargeById($myshopify, $token, $id)
        ]);

        $this->activated = json_decode($response->getBody(), true);

        return $this;

    }

    /**
     *  Get the property options value
     *
     * @return array
     * @throws Exception
     */
    protected function getOptions()
    {
        if(!empty(array_diff_key(array_flip($this->requiredProperties), $this->options)))
        {
            throw New Exception ('required properties cannot be null!');
        }

        return [$this->chargeType => $this->options ];
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
     * Get the created charge of this object
     *
     * @return mixed
     * @throws Exception
     */
    public function getCharge()
    {
        if($this->charge != null) return $this->charge;
        else throw new Exception('Charge must be created first');

    }


    /**
     * get the charge type of the created charge
     *
     * @return string
     */
    public function getChargeType()
    {
        return $this->chargeType;
    }

    /**
     * Get the information for the activated charge
     *
     * @return array
     */
    public function getActivated()
    {
        if($this->activated != null) return $this->activated;
        else throw new Exception('Charge must be activated');
    }

    /**
     *  Set the request path of the API call
     *
     *
     * @param mixed, Shopify object, string myshopify domain
     * @return $this
     * @throws Exception
     */
    protected function setRequestPath($param)
    {
        if($param instanceof Shopify)
        {
            $this->requestPath = $param->getRequestPath();
        }
        elseif(is_string($param) && strpos($param, 'myshopify') != false)
        {
            $this->requestPath = 'https://'. $param . '/admin/';
        }
        else throw new Exception('invalid request path');

        return $this;
    }


}
