<?php

class HawkHeader {

	/**
	* Calculate the request HMAC
	*
	* @param string $type           'header', 'bewit', or 'respnse'
    *
	* @param array  $credentials    [id, key, algorithm]
    *
	* @param array  $options        [ts, nonce, method, resource, host, port,
	*                                hash, ext]
	*
	* @return string                Base64 encoded MAC
	*/
	private static function calculateMac($type, $credentials, $options) {

	    $normalized = HawkHeader::generateNormalizedString($type, $options);
	    $hmac = hash_hmac('sha256', $normalized, $credentials['key'], true);
	    $digest = base64_encode($hmac);

	    return $digest;
	}


	/**
	* Normalize string for generating MAC
	*
	* @param string $type           'header', 'bewit', or 'respnse'
    *
	* @param array  $options        [ts, nonce, method, resource, host, port,
	*                                hash, ext]
	*
	* @return string                Normalized string
	*/
	private static function generateNormalizedString($type, $options) {

	    $resource = $options['resource'];

	    $normalized = 'hawk.1.' . $type . "\n" .
	                    $options['ts'] . "\n" .
	                    $options['nonce'] . "\n" .
	                    strtoupper($options['method']) . "\n" .
	                    $resource . "\n" .
	                    strtolower($options['host']) . "\n" .
	                    $options['port'] . "\n" .
						$options['hash'] . "\n";


	    if ($options['ext']) {
	         $ext = str_replace("\\", "\\\\", $options['ext']);
	         $ext = str_replace("\n", "\\n", $ext);
	         $normalized = $normalized . $ext;
	    }

	    $normalized = $normalized . "\n";

	    return $normalized;
	}


	/**
	* Generate Hawk header
	*
	* @param string $uri        The reqest URI
    *
	* @param string $method     The HTTP verb
    *
	* @param array  $options    [credentials, ext, ts, nonce,
	*                            localtimeOffsetMsec, playload, contentType,
	*                            hash]
	*
	* @return array             [field, artifacts]
	*/
	public static function generate($options) {

	    $result = [
	        'field' => '',
	        'artifacts' => []
	    ];

		$uri = $options['uri'];
		$method = $options['method'];

	    $host = parse_url($uri, PHP_URL_HOST);
	    $port = parse_url($uri, PHP_URL_PORT);
	    $resource = parse_url($uri, PHP_URL_PATH);
	    $query = parse_url($uri, PHP_URL_QUERY);

	    if ($query) {
	        $resource = $resource . "?" . $query;
	    }
        else if (preg_match('/\?$/', $uri) == 1) { // case: /api/search?
            $resource = $resource . "?";
        }

	    $result['artifacts'] = [
	        'ts' => time(),
	        'nonce' => substr(uniqid(''), -6),
	        'method' => strtoupper($method),
	        'resource' => $resource,
	        'host' => $host,
	        'port' => $port,
			'hash' => $options['hash'],
			'ext' => $options['ext']
	    ];

	    foreach (array_keys($result['artifacts']) as $key) {

	        if (isset($options[$key])) {

	            $result['artifacts'][$key] = $options[$key];
	        }
	    }

	    $mac = HawkHeader::calculateMac('header',
                                        $options['credentials'],
                                        $result['artifacts']);

	    $header = 'Hawk id="' . $options['credentials']['id'] .
	                '", ts="' . $result['artifacts']['ts'] .
	                '", nonce="' . $result['artifacts']['nonce'];

	    if ($result['artifacts']['ext']) {
	        $header = $header . '", ext="' . $result['artifacts']['ext'];
	    }

	    $header = $header . '", mac="' . $mac . '"';

	    $result['field'] = $header;

	    return $result;
	}
}


class RestoreStrategiesClient {

    const VERSION = '1.3.0';

	private $token;
	private $secret;
	private $host = 'http://api.restorestrategies.org';
	private $port;
	private $algorithm = 'sha256';
	private $credentials;
    private $user_agent = 'Restore Strategies PHP client';

	/**
	* Constructor
	*
	* @param string $token      A valid API user token
    *
	* @param string $secret     A valid API user secret
    *
	* @param string $host       (optional) Scheme + host (e.g. http://example.com).
	*                           defaults to http://api.restorestrategies.org
    *
	* @param integer $port      (optional) TCP port, defaults to 80 or 443
	*                           depending on the scheme used on the host
    *
    * @param string $agent      (optional) User agent. Defaults to
    *                           'Restore Strategies PHP client'
	*/
	public function __construct($token, $secret, $host = null, $port = null, $agent = null) {

		$this->token = $token;
		$this->secret = $secret;

		if ($host) {
			$this->host = $host;
		}

        if ($agent) {
            $this->user_agent = $agent;
        }

        $this->user_agent .= " (PHP Client/" . $this->version() . ")";

		if ($port) {
			$this->port = $port;
		}
		else {
			$scheme = parse_url($this->host, PHP_URL_SCHEME);

			if ($scheme == 'http') {
				$this->port = 80;
			}
			else {
				$this->port = 443;
			}
		}

		$this->host = $this->host . ':' . $this->port;

		$this->credentials = [
			'id' => $this->token,
			'key' => $this->secret,
			'algorithm' => $this->algorithm
		];
	}

    /**
     * Get client version
     */
    public function version() {
        return self::VERSION;
    }

	/**
	* Make an API request
	*
	* @param string $path   A valid URL path
    *
	* @param string $verb   An HTTP verb
    *
	* @param string $data   (optional) Data to be sent to the server, e.g. in a
	*                       POST request
    *
	* @return object        A Response object
	*/
	private function apiRequest($path, $verb, $data = null) {

		$uri = $this->host . $path;

		$opts = array(
			'method'=>"" . $verb,
			'header'=>array('Content-type'=> "application/vnd.collection+json",
					'api-version'=> "1"),
			'uri'=> $uri,
			'ext'=> '',
			'credentials' => $this->credentials
		);

		$curlSession = curl_init();

		$verb = strtolower($verb);

		if ($verb === 'post') {
			$curlOptions[CURLOPT_POSTFIELDS] = $data;
		}

		$header = HawkHeader::generate($opts);
        $agent = $_SERVER['HTTP_USER_AGENT'] . " " . $this->user_agent;

		$curlOptions[CURLOPT_URL] = $uri;
		$curlOptions[CURLOPT_RETURNTRANSFER] = true;
        $curlOptions[CURLOPT_USERAGENT] = $agent;

		curl_setopt_array($curlSession, $curlOptions);
		curl_setopt($curlSession, CURLOPT_HTTPHEADER, array(
				'Content-type: application/vnd.collection+json',
				'api-version: 1',
				'Authorization: ' . $header['field']
		));

		$jsonObj = json_decode(curl_exec($curlSession));

        $response = new Response($jsonObj, $this);

		return $response;
	}


	/**
	* Turn an array of data into a URL query string
	*
	* @param array $params  A array with keys & values
	*
	* @return string        A URL query string
	*/
	private function paramsToString($params) {

		$queryArray = [];

		if (!is_array($params)) {
			return null;
		}

		foreach ($params as $key => $value) {

			if(is_array($value)) {
				foreach ($value as $subkey => $subvalue) {
					array_push($queryArray, $key . '[]=' . urlencode($subvalue));
				}
			}
			else {
				array_push($queryArray, $key . '=' . urlencode($value));
			}
		}

		$queryString = implode('&', $queryArray);

		return $queryString;
	}


	/**
	* GET a specific opportunity
	*
	* @param integer $id    The id of an opportunity
    *
    * @param string $city   (optional) The franchise city the opportunity
    *                       belongs to. This parameter is only useful if the
    *                       opportunity is outside of your franchise city.
	*
    * @return object        A Response object
    */
	public function getOpportunity($id, $city = NULL) {

        $href = '/api/opportunities/' . $id;

        if($city) {
            $href .= '?city=' . $city;
        }

        $result = $this->apiRequest($href, 'GET');

        return $result;
	}


	/**
	* Get a list of all opportunities
	*
    * @param string $city   (optional) The franchise city. This parameter is
    *                       only useful if the opportunities you wish to list are
    *                       outside of your franchise city.
    *
	* @return object	A Response object
	*/
	public function listOpportunities($city = NULL) {

		$href = '/api/opportunities';

        if($city) {
            $href .= '?city=' . $city;
        }

		$response = $this->apiRequest($href, 'GET');

		return $response;
	}


    /**
     * Get a list of featured opportunities
     *
     * @return object	A Response object
     */
    public function featuredOpportunities($city = NULL) {

		$href = '/api/opportunities/featured';

        if($city) {
            $href .= '?city=' . $city;
        }

		$response = $this->apiRequest($href, 'GET');

		return $response;
    }


    /**
     * Search opportunities
     *
     * @param array $params     An array of search parameters. Acceptable 
     * options are given by the below Collection+JSON query template:
     *
     * {
     *     href: '/api/search',
     *     rel: 'search',
     *     prompt: 'Search for opportunities',
     *     data: [
     *         {
     *             name: 'q',
     *             prompt: '(optional) Enter search string',
     *             value: ''
     *         },
     *         {
     *             name: 'issues',
     *             prompt: '(optional) Select 0 or more issues',
     *             array: [
     *                 'Children/Youth',
     *                 'Elderly',
     *                 'Family/Community',
     *                 'Foster Care/Adoption',
     *                 'Healthcare',
     *                 'Homelessness',
     *                 'Housing',
     *                 'Human Trafficking',
     *                 'International/Refugee',
     *                 'Job Training',
     *                 'Sanctity of Life',
     *                 'Sports',
     *                 'Incarceration'
     *           ]
     *       },
     *       {
     *           name: 'regions',
     *           prompt: '(optional) Select 0 or more geographical regions',
     *           array: [
     *               'North',
     *               'Central',
     *               'East',
     *               'West',
     *               'Other'
     *           ]
     *       },
     *       {
     *           name: 'times',
     *           prompt: '(optional) Select 0 or more times of day',
     *           array: [
     *               'Morning',
     *               'Mid-Day',
     *               'Afternoon',
     *               'Evening'
     *           ]
     *       },
     *       {
     *           name: 'days',
     *           prompt: '(optional) Select 0 or more days of the week',
     *           array: [
     *               'Monday',
     *               'Tuesday',
     *               'Wednesday',
     *               'Thursday',
     *               'Friday',
     *               'Saturday',
     *               'Sunday'
     *           ]
     *        },
     *        {
     *            name: 'type',
     *            prompt: '(optional) Select 0 or more opportunity types',
     *            array: [
     *                'Gift',
     *                'Service',
     *                'Specific Gift',
     *                'Training'
     *            ]
     *        },
     *        {
     *            name: 'group_types',
     *            prompt: '(optional) Select 0 or more volunteer group types',
     *            array: [
     *                'Individual',
     *                'Group',
     *                'Family'
     *            ]
     *        }
     *   ]
     * }
     *
     * Example: $params = [
     *              'q' => 'foster care',
     *              'issues' => ['Education', 'Children/Youth'],
     *              'region' => ['South', 'Central']
     *          ];
     *
     * @param string $city  (optional) The franchise city. This parameter is
     *                      only useful if the opportunities you wish to search
     *                      are outside of your franchise city.
     *
     * @return object           A Response object
     */
	public function search($params, $city = NULL) {

		$href = '/api/search';
		$href .= "?" . $this->paramsToString($params);

        if($city) {
            $href .= '&city=' . $city;
        }

		$response = $this->apiRequest($href, 'GET');

		return $response;
	}


    /**
     * Get a signup template
     *
     * @param integer $id   The id of an opportunity
     *
     * @return object       A Response object
     */ 
	public function getSignup($id) {

		$href = '/api/opportunities/' . $id . '/signup';
		$response = $this->apiRequest($href, 'GET');

		return $response;
	}


    /*
     * Signup for an opportunity
     *
     * @param integer $id         The id of the opportunity to signup for
     *
     * @param array   $template   An array of of template data
     * Ex. array(
     *          "givenName" => "Jon",
     *          "familyName" => "Doe",
     *          "telephone" => "404555555",
     *          "email" => "jon.doe@example.com",
     *          "comment" => "I'm excited!",
     *          "numOfItemsCommitted" => 1,
     *          "lead" => "other"
     *     )
     *
     * @return object             A Response object
     */ 
	public function submitSignup($id, $template, $city = NULL) {

        $data = [];
        $jsonStr = '{ "template": { "data": [';

        foreach ($template as $name => $value) {
            $ele = '{ "name": "' . $name . '", "value": ' . json_encode($value) . ' }';
            array_push($data, $ele);
        }

        $jsonStr = $jsonStr . join(', ', $data) . '] } }';
		$href = '/api/opportunities/' . $id . '/signup';

        if($city) {
            $href .= '?city=' . $city;
        }

		$response = $this->apiRequest($href, 'POST', $jsonStr);

		return $response;
	}
}


class Response {

    private $raw;
    private $items = [];
    private $error = NULL;
    private $client;

    /**
     * Constructor
     *
     * @param object $response  An objectified version of the server's JSON
     * response.
     */ 
    public function __construct($response, $client) {
        $this->raw = $response;
        $this->client = $client;

        if ($this->raw->collection->error) {
            $this->error = $this->raw->collection->error;
        }

        if ($this->raw->collection->items) {
            $this->createData($this->raw->collection->items);
        }
    }

    private function createData($items) {

        if (!is_array($items)) {
            return -1;
        }

        foreach ($items as $item) {
            array_push($this->items, new Opportunity($this->client, $item));
        }
    }

    public function raw() {
        return $this->raw;
    }

    public function items() {
        return $this->items;
    }

    public function error() {
        return $this->error;
    }
}


class Opportunity {

    private $client;
    public $href;
    private $links;

    /**
    * Constructor
    * 
    * @param ForTheCityClient $client   An instance of the ForTheCityClient
    * class
    *
    * @param object $item   An objectification of the JSON of a Collection+JSON
    * item
    */
    public function __construct($client, $item) {

        $this->client = $client;
        $this->href = $item->href;
        $this->links = $item->links;

        if ($item->data) {
            foreach ($item->data as $elem) {
                $this->{$elem->name} = $this->getValue($elem);
            }
        }
    }

    private function getValue($element) {

        if ($element->value) {
            return $element->value;
        }
        else if ($element->array) {
            return $element->array;
        }
        else if ($element->object) {
            return $element->object;
        }

        return NULL;
    }
}
