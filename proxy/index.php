<?php

/**
* Inspired by:
* https://stackoverflow.com/questions/7052643/php-proxy-server-and-calling-json
**/

$node_url = "http://localhost:7076";
$whiteList = array("block_count", "delegators", "delegators_count", "chain", "peers", "account_balance");//, "account_representative");

$options = array
(
    CURLOPT_HEADER         => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 0,
    CURLOPT_HTTPGET        => 1
);

$service = $_GET["service"];

$request_headers = Array();
foreach($_SERVER as $i=>$val) {
        if (strpos($i, 'HTTP_') === 0) {
                $name = str_replace(array('HTTP_', '_'), array('', '-'), $i);
                if ($name != 'HOST')
                {
                    $request_headers[] = "{$name}: {$val}";
                }
        }
}

$options[CURLOPT_HTTPHEADER] = $request_headers;

if (strtolower($_SERVER["REQUEST_METHOD"]) === "post") {
        $options[CURLOPT_POST] = true;
        $url = "{$node_url}/services/".$service;

        $options[CURLOPT_POSTFIELDS] = file_get_contents("php://input");
	$json = json_decode(file_get_contents("php://input"));

	if (in_array($json->{'action'}, $whiteList)) {
		// Send request to Node
		$options[CURLOPT_URL] = $url;
		$curl_handle = curl_init();
		curl_setopt_array($curl_handle,$options);
		$server_output = curl_exec($curl_handle);
		curl_close($curl_handle);
		// Prepare headers for response
		$response = explode("\r\n\r\n",$server_output);
		$headers = explode("\r\n",$response[0]);
		foreach ($headers as $header) {
			if (!preg_match(';^transfer-encoding:;ui', Trim($header))) {
				header($header);
			}
		}
		// Send response
		echo $response[1];
	} else {
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json');
		$data = [ 'error' => 'Unknown or unsupported command'];
		echo json_encode($data, JSON_PRETTY_PRINT);
	}
} else {
	// negative response
}


