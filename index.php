<?php
session_start();

function http($url, $params=false, $headers=false) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYSTATUS, false);
  
  if($params)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

  if($headers)
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
  return json_decode(curl_exec($ch));
}

if(isset($_GET['logout'])) {
  unset($_SESSION['username']);
  header('Location: /');
  die();
}

if(isset($_SESSION['username'])) {
  echo '<p>Logged in as</p>';
  echo '<p>' . $_SESSION['username'] . '</p>';
  echo '<p><a href="/?logout">Log Out</a></p>';
  die();
}

$client_id = 'ff684851-98e9-4c1a-b1f7-5182e49d1faf';
$client_secret = 'a566bed5-0314-40da-9ecf-b6e1b093e070';
$redirect_uri = 'http://localhost:8080/';
$metadata_url = 'https://sso-auth-domain.login.sys.home.pcfdot.com/.well-known/openid-configuration';
$metadata = http($metadata_url);

#var_dump($_SESSION);
#var_dump($metadata);

if(isset($_GET['code'])) {

  if($_SESSION['state'] != $_GET['state']) {
    die('Authorization server returned an invalid state parameter');
  }

  if(isset($_GET['error'])) {
    die('Authorization server returned an error: '.htmlspecialchars($_GET['error']));
  }

  $response = http($metadata->token_endpoint, [
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
  ]);

#  print_r($response);

  if(!isset($response->access_token)) {
    die('Error fetching access token');
  }

  $token = http($metadata->userinfo_endpoint, false, [
    "Authorization: Bearer ".$response->access_token
  ]);

#  print_r($token);

  if($token) {
    $_SESSION['username'] = $token->email;
    header('Location: /');
    die();
  }
}

if(!isset($_SESSION['username'])) {
  $_SESSION['state'] = bin2hex(random_bytes(5));

  $authorize_url = $metadata->authorization_endpoint.'?'.http_build_query([
    'response_type' => 'code',
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'state' => $_SESSION['state'],
    'scope' => 'openid',
  ]);
  #$authorize_url = 'TODO';

  echo '<p>Not logged in</p>';
  echo '<p><a href="'.$authorize_url.'">Log In</a></p>';
}

