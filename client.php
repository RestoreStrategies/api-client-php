<?php

class HawkHeader {

	private static $headerVersion = 1;

	/**
	* Calculate the request HMAC
	*
	* @param string $type           'header', 'bewit', or 'respnse'
	* @param array  $credentials    [id, key, algorithm]
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
	* @param string $method     The HTTP verb
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

/**
 *
 */
class RestoreStrategiesClient {

	private $token;
	private $secret;
	private $host = 'https://api.forthecity.org';
	private $port;
	private $algorithm = 'sha256';
	private $credentials;
	//private const $cachingDisabled = true;
	private const $restfulUrisDisabled = true;

	/**
	* Constructor
	*
	* @param string $token      A valid API user token
	* @param string $secret     A valid API user secret
	* @param string $host       (optional) Scheme + host (e.g. http://example.com).
	*                           defaults to https://api.forthecity.org
	* @param integer $port      (optional) TCP port, defaults to 80 or 443
	*                           depending on the scheme used on the host
	*/
	public function __construct($token, $secret, $host = null, $port = null) {

		$this->token = $token;
		$this->secret = $secret;

		if ($host) {
			$this->host = $host;
		}

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
	* Make an API request
	*
	* @param string $path   A valid URL path
	* @param string $verb   An HTTP verb
	* @param string $data   (optional) Data to be sent to the server, e.g. in a
	*                       POST request
	* @return object        An objectified version of the server's JSON response
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

		$curlOptions[CURLOPT_URL] = $uri;
		$curlOptions[CURLOPT_RETURNTRANSFER] = true;

		curl_setopt_array($curlSession, $curlOptions);
		curl_setopt($curlSession, CURLOPT_HTTPHEADER, array(
				'Content-type: application/vnd.collection+json',
				'api-version: 1',
				'Authorization: ' . $header['field']
		));

		$response = json_decode(curl_exec($curlSession));

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
	* @return object        An objectified version of the server's JSON
	*                       response. Normally, this will either be an
	*                       opportunity or a 404 message.
	*/
	public function getOpportunity($id) {

		$response = $this->search(array(
			'id'=> "" . $id
		));

		return $response;
	}

	/**
	* Get a list of all opportunities
	*
	* @return object	An objectified version of the server's JSON response
	*/
	public function listOpportunities() {

		if ($this->restfulUrisDisabled) {
			$href = '/api/opportunities';
		}
		else {
			$entryPoint = $this->getEntryPoint();
			$href = $this->getRelationHref($entryPoint->collection->links, 'opportunities');
		}

		if ($href !== null) {
			$response = $this->apiRequest($href, 'GET');
		}

		return $response;
	}

	public function search($params) {

		if ($this->restfulUrisDisabled) {
			$href = '/api/search';
		}
		else {
			$entryPoint = $this->getEntryPoint();
			$href = $this->getRelationHref($entryPoint->collection->links, 'search');
		}

		if ($href !== null) {
			$href .= "?" . $this->paramsToString($params);
			$response = $this->apiRequest($href, 'GET');
		}

		return $response;
	}

	public function getSignup($id) {

		if ($this->restfulUrisDisabled) {
			$href = '/api/opportunities/' . $id . '/signup';
		}
		else {
			$opp = $this->getOpportunity($id);
			$href = $this->getRelationHref($opp->collection->items[0]->links, 'signup');
		}

		if ($href !== null) {
			$response = $this->apiRequest($href, 'GET');
		}

		return $response;
	}

	public function submitSignup($id, $template) {

		if ($this->restfulUrisDisabled) {
			$href = '/api/opportunities/' . $id . '/signup';
		}
		else {
			$opp = $this->getOpportunity($id);
			$href = $this->getRelationHref($opp->collection->items[0]->links, 'signup');
		}

		if ($href !== null) {
			$response = $this->apiRequest($href, 'POST', $template);
		}

		return $response;
	}

	private function getEntryPoint() {

		$response = $this->apiRequest('/api', 'GET');
		return $response;
	}

	private function getRelationHref($data, $relation) {

		if (is_array($data)) {
			foreach ($data as $datum) {
				if ($datum->rel === $relation) {
					return $datum->href;
				}
			}
		}
		else {
			if ($data->rel === $relation) {
				return $data->href;
			}
		}
	}

	/*private function getFromCache($path) {
		//TODO: add caching for appropriate stateful clients
		return null;
	}*/
}
