<?php

namespace BNMetrics\Shopify;


class Endpoints
{

    public $get;
    public $create;
    public $modify;
    public $delete;

    public $endpoints;

    public $categoryKey;

    protected $isTier3;

    protected $actions = ['get', 'create', 'modify', 'delete'];

    protected $suffix = ['All', 'ById', 'Count', 'Search'];


    public function __construct()
    {

        $this->get    = new \stdClass();
        $this->create = new \stdClass();
        $this->modify = new \stdClass();
        $this->delete = new \stdClass();

        $this->endpoints = config('shopify.endpoints');

    }


    /**
     * Set the endpoints uris for the first level endpoints,
     * eg. /products.json, /products/{id}
     *
     * @param $name String. eg. 'product'
     * @param $id
     *
     * @return $this
     */
    public function setTierOneEndpoints($name, $id = null)
    {
        if (!array_key_exists($name, $this->endpoints)) {
            throw new \Exception('invalid tier 1 Endpoints');
        }

        $this->setEndpoints($name, $this->getUriFromKey($name), $id);

        return $this;
    }


    /**
     * Set the endpoints uri for the second level endpoints,
     * eg. product/{id}/images, product/{id}/images/count,
     *     product/{id}/images/{id}
     *
     * @param      $name string, eg. 'productImages'
     * @param null $id
     *
     * @return $this
     * @throws \Exception
     */
    public function setTierTwoEndpoints($name, $id = null, $tier3Id = null)
    {

        $endpointArr = $this->findEndpoint($name);

        //check validation of input endpoint
        if (empty($endpointArr)) {
            throw new \Exception('Invalid endpoint or this endpoint does not have tier 2.');
        }

        $tierOneKey = array_keys($endpointArr)[0];


        //Get the tier1 Uri of the current endpoint
        try {
            $tierOneUri = $this->get->{$tierOneKey}[$tierOneKey . 'ById'];
        } catch (\Exception $e) {
            throw new \Exception(" Must define tier one endpoint!");
        }

        //Get the tier 2 key
        $tierTwoKey = lcfirst(str_replace($tierOneKey, "", $name));

        if (!in_array($tierTwoKey, $this->endpoints[$tierOneKey])) {
            if (array_key_exists('tier3', $this->endpoints[$tierOneKey])) {
                $keyArr       = $this->getTierKeys($tierTwoKey, $this->endpoints[$tierOneKey]);
                $tierTwoKey   = $keyArr[0];
                $tierThreeKey = $keyArr[1];
            } else {
                throw new \Exception('invalid tier 2 endpoint');
            }
        }

        $tierTwoEndpoint = $this->getUriFromKey($tierTwoKey);

        //if tierthreekey isset, $name = str_replace() get rid of t3key
        $tierTwoUri = $tierOneUri . '/' . $tierTwoEndpoint;

        if (isset($tierThreeKey) && isset($id)) {
            $tierTwoName = str_replace(ucfirst($tierThreeKey), "", $name);
            //Set Tier2 endpoints
            $this->setEndpoints($tierTwoName, $tierTwoUri, $id);

            $tierTwoEnd = $this->get->{$tierTwoName}[$tierTwoName . 'ById'];

            $tierThreeUri = $tierTwoEnd . '/' . $tierThreeKey;

            $this->setEndpoints($name, $tierThreeUri, $tier3Id);

            $this->isTier3 = true;
        } else {
            $this->setEndpoints($name, $tierTwoUri, $id);
        }

        return $this;

    }

    /**
     * This method is specifically for endpoints with tier 3 available. 'getOrdersFullfillmentsEvents'
     * eg. fullfilmentEvents to ['fulfillments', 'events']
     *
     * @param $combinedKey
     * @param $endpointArr
     *
     * @return array
     */
    protected function getTierKeys($combinedKey, $endpointArr)
    {

        $keys = preg_split('/(?=[A-Z])/', $combinedKey);

        $keyArr = array_map(function ($e) {
            return strtolower($e);
        }, $keys);

        if (!in_array($keyArr[0], $endpointArr)) {
            throw new \Exception('invalid tier 2 endpoint');
        }

        if (!in_array($keyArr[1], $endpointArr['tier3'])) {
            throw new \Exception('invalid tier 3 endpoint');
        }

        return $keyArr;
    }

    /**
     * Get the request uri piece from the name,
     * eg. $name = 'smartCollections',return 'smart_collections'
     *
     * @param $name
     *
     * @return string
     */
    protected function getUriFromKey($name)
    {
        if (preg_match('/(?=[A-Z])/', $name) !== false) {
            $pieces = preg_split('/(?=[A-Z])/', $name);
            $uri    = strtolower(join('_', $pieces));
        } else {
            $uri = $name;
        }

        return $uri;
    }


    /**
     *
     * Set the API endpoints given name, endpoint uri, and id
     *
     * @param      $name
     * @param      $uri
     * @param null $id optional
     */
    protected function setEndpoints($name, $uri, $id = null)
    {

        $this->get->{$name} = [
            $name . 'All'    => $uri,
            $name . 'Count'  => $uri . '/count',
            $name . 'Search' => $uri . '/search',
        ];

        $this->create->{$name} = [
            $name => $uri,
        ];

        if (isset($id) && $id != null) {
            $byId = $uri . '/' . $id;

            $this->get->{$name}[$name . 'ById'] = $byId;

            $this->modify->{$name} = [
                $name => $byId,
            ];

            $this->delete->{$name} = [
                $name => $byId,
            ];
        } elseif (in_array($name, config('shopify.tierTwoWithoutId'))) {
            $this->modify->{$name} = [$name => $uri];

            $this->delete->{$name} = [$name => $uri];
        }

    }

    /**
     * Find the associated endpoint array from the config file,
     * given string consist of the first level key.
     *
     * eg. $name = 'productsAll', would return [ 'products' => ['images', 'variant'] ]
     *
     * @param $name
     *
     * @return array|null
     */
    public function findEndpoint($name)
    {
        $endpointArr = null;

        foreach ($this->endpoints as $key => $value) {
            if (strpos($name, $key) !== false) {
                $endpointArr = [$key => $value];
                break;
            }
        }

        return $endpointArr;
    }


    /**
     * get the current action of the APIcall
     *
     * @param $name string, eg. 'getProductAll'
     *
     * @return mixed string, eg. 'get'
     */
    public function callbackAction($name)
    {
        return $this->getString($name, $this->actions);
    }


    /**
     * Remove the suffix of each std class properties
     *
     * eg. productImagesById to productImages
     *
     * @param $name
     *
     * @return mixed
     */
    protected function removeSuffix($name)
    {

        $currSuffix = $this->getString($name, $this->suffix);

        return str_replace($currSuffix, '', $name);
    }


    /**
     * Remove part of the given string that's the same to an element in the given array.
     *
     * @param $name
     * @param $inputArr
     *
     * @return mixed
     */
    protected function getString($name, $inputArr)
    {
        foreach ($inputArr as $input) {
            if (strpos($name, $input) !== false) {
                return $currAction = $input;
            }
        }
    }


    /**
     * Get the uri endpoint to be passed to the Shopify object
     *
     * @param      $name      Method name. eg. "getProductAll"
     * @param null $parseArgs . optional, eg. product id, image id...
     *
     * @return string
     *
     */
    public function __call($name, $parseArgs = null)
    {
        /*
         * get the endpoint key from the method name
         * eg. $name = 'getProductAll', $endpointKey = 'productAll'
         */
        $currAction  = $this->callbackAction($name);
        $endpointKey = lcfirst(str_replace($currAction, '', $name));

        /*
         * Get the category key of the called endpoint
         * eg. products;
         */
        $tierOne = $this->findEndpoint($endpointKey);

        $this->categoryKey = array_keys($tierOne)[0];

        /*
         * Set the tier1 endpoints properties
         * eg. $this->get->product;
         */
        if (count($parseArgs) != 0) {
            $this->setTierOneEndpoints($this->categoryKey, $parseArgs[0]);
        } else {
            $this->setTierOneEndpoints($this->categoryKey);
        }

        $tierOneEndpoints = $this->{$currAction}->{$this->categoryKey};
        /*
         * Get the tier 2 endpoint uri, if tier2 is requested.
         *
         */
        if (!array_key_exists($endpointKey, $tierOneEndpoints)) {
            $tierTwoKey = array_values(
                array_filter($tierOne[$this->categoryKey], function ($e) use ($endpointKey) {
                    if (!is_array($e)) {
                        return strpos($endpointKey, ucfirst($e)) ? $e : null;
                    }
                })
            );

            if (empty($tierTwoKey)) {
                throw new \Exception('invalid tier two key or tier two key is not defined in config/shopify.php');
            }

            $tierTwoArg = $this->removeSuffix($endpointKey);


            if (count($parseArgs) > 1) {
                $this->setTierTwoEndpoints($tierTwoArg, $parseArgs[1]);
            } elseif (count($parseArgs) > 2 && $this->isTier3 == true) {
                $this->setTierTwoEndpoints($tierTwoArg, $parseArgs[1], $parseArgs[2]);
            } else {
                $this->setTierTwoEndpoints($tierTwoArg);
            }

            $tierTwoEndpoint = $this->{$currAction}->{$tierTwoArg};


            $endpoint = $tierTwoEndpoint[$endpointKey];
        } else {
            $endpoint = $tierOneEndpoints[$endpointKey];
        }

        return $endpoint;

    }
}
