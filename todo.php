<?php
header("Content-Type: application/json;charset=UTF-8");

require 'vendor/autoload.php';
use \Firebase\JWT\JWT;

$log = new Monolog\Logger('my-log');
//$log->pushHandler(new Monolog\Handler\StreamHandler('app.log', Monolog\Logger::WARNING));

// read a JWT from the command line or from Authorization header
$jwt = "";
$header = "";
$scope_to_check = "todo.read";


if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $header = $_SERVER['HTTP_AUTHORIZATION'];
    $jwt = trim((string)preg_replace('/^(:?\s+)?Bearer\s/', '', $header));
} elseif (isset($argv[1])) {
    $jwt = $argv[1];
} else {
    exit("Missing Authorization header or provide a token on stdin to verify");
}

list($header, $payload, $signature) = explode(".", $jwt);

$plainHeader = base64_decode($header);
$log->addInfo("Header: $plainHeader");

$plainPayload = base64_decode($payload);
$log->addInfo("Payload: $plainPayload");

// Fetch public key
$client = new GuzzleHttp\Client();

// Read token_keys endpoint from JWT header
$token_keys_url = json_decode($plainHeader, true);
// Read the Auth Server's public keys from token_keys endpoint
$res = $client->request('GET', $token_keys_url["jku"], ['verify' => false]);

// If header doesn't include the token_keys then you specify it explicitly
//$res = $client->request('GET', 'https://sso-auth-domain.uaa.sys.home.pcfdot.com/token_keys', ['verify' => false]);

//echo $res->getStatusCode();
//echo $res->getHeader('content-type')[0];
//echo $res->getBody();

// Fetch the token_keys
$keys = json_decode($res->getBody()->getContents(), true);
//var_dump($keys["keys"][0]["value"]);

// Extract the public key value or read it from a file
if ($keys["keys"][0]["value"] != "") {
    $publicKey = $keys["keys"][0]["value"];
} else {
    $publicKey = file_get_contents('public.key');
}

// Decode & Verify the JWT token
$decoded = JWT::decode($jwt, $publicKey, ['RS256']);

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/
//print_r((array) $decoded);

// Confirm that the scopes include the permission set that this resource needs to check for
if (in_array($scope_to_check, $decoded->scope)) {
//    echo json_encode(array("msg" => "you have my permission!"));
    echo "[
    {
        \"created\": 1563253372044,
        \"id\": \"cf3daaa4-b74c-44ed-bcfb-82a991caa035\",
        \"todo\": \"read books\",
        \"updated\": 1563253372044
    },
    {
        \"created\": 1563253400395,
        \"id\": \"8a02db6c-a025-4b7a-b6f8-b0dfcfa9ed7f\",
        \"todo\": \"read more books\",
        \"updated\": 1563253400395
    }]";
} else {
    echo json_encode(array("msg" => "you are not supposed to be reading my lists!"));
}

?>
