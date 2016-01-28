<?php

class HawkHeader {

	/**
	* Calculate the request HMAC
	*
	* @param string $type			'header', 'bewit', or 'respnse'
	* @param array  $credentials	[id, key, algorithm]
	* @param array  $options		[ts, nonce, method, resource, host, port, hash, ext]
	*
	* @return string				Base64 encoded MAC
	*/
	function calculateMac($type, $credentials, $options) {

	    $normalized = HawkHeader::generateNormalizedString($type, $options);
	    $hmac = hash_hmac('sha256', $normalized, $credentials['key'], true);
	    $digest = base64_encode($hmac);

	    return $digest;
	}

	/**
	* Normalize string for generating MAC
	*
	* @param string $type			'header', 'bewit', or 'respnse'
	* @param array  $options		[ts, nonce, method, resource, host, port, hash, ext]
	*
	* @return string				Normalized string
	*/
	function generateNormalizedString($type, $options) {

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
	* @param string $uri		The reqest URI
	* @param string $method		The HTTP verb
	* @param array  $options	[credentials, ext, ts, nonce, localtimeOffsetMsec, playload, contentType, hash]
	*
	* @return array 			[field, artifacts]
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

	    $mac = HawkHeader::calculateMac('header', $options['credentials'], $result['artifacts']);

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
/*
$type = 'header';
$credentials = [ 'id' => 'dev_token', 'key' => 'dev_secret' ];

$hawkOptions = [
    'credentials' => $credentials,
    'method' => 'GET',
];

$uri = 'http://api.local:3000/api/opportunities';
$header = HawkHeader::generate($uri, 'get', $hawkOptions);

$cs = curl_init();

$options = array(
	CURLOPT_HTTPHEADER => array('Content-type: Application+JSON',
								'api-version: 1',
								'Authorization: ' . $header['field']),
	CURLOPT_URL => $uri
);

curl_setopt_array($cs, $options);
$data  = curl_exec($cs);
var_dump($data);
*/
