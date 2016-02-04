<?php

class HawkHeader {

	/**
	* Calculate the request HMAC
	*
	* @param string $type           'header', 'bewit', or 'respnse'
	* @param array  $credentials    [id, key, algorithm]
	* @param array  $options        [ts, nonce, method, resource, host, port, hash, ext]
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
	* @param array  $options        [ts, nonce, method, resource, host, port, hash, ext]
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
	* @param array  $options    [credentials, ext, ts, nonce, localtimeOffsetMsec, playload, contentType, hash]
	*
	* @return array             [field, artifacts]
	*/
	public static function generate($uri, $method, $options) {

	    $result = [
	        'field' => '',
	        'artifacts' => []
	    ];

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
class ForTheCityClient {

	private $token;
	private $secret;
	private $host = 'https://api.forthecity.org';
	private $port;
	private $algorithm = 'sha256';
	private $credentials;

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

	private function apiRequest($path, $verb, $data = null) {

		$uri = $this->host . $path;

		$hawkOptions = [
			'credentials' => $this->credentials,
		];

		if ($data) {
			$hawkOptions['ext'] = $data;
		}

		$header = HawkHeader::generate($uri, $verb, $hawkOptions);

		$curlSession = curl_init();
		$curlOptions = [
			CURLOPT_HTTPHEADER => [
				'Content-type: Application+JSON',
				'api-version: 1',
				'Authorization: ' . $header['field']
			],
			CURLOPT_URL => $uri,
			CURLOPT_RETURNTRANSFER => true
		];

		curl_setopt_array($curlSession, $curlOptions);

		return json_decode(curl_exec($curlSession));
	}

	private function paramsToString() {}

	public function getOpportunity($id) {

		$path = '/api/opportunities/' . $id;
		return ForTheCityClient::apiRequest($path, 'GET');
	}

	public function listOpportunities() {

		$path = '/api/opportunities';

		$result = ForTheCityClient::apiRequest($path, 'GET')->collection;
		return $result;
	}

	public function search() {}
}
