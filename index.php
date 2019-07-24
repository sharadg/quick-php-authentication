<?php
require 'vendor/autoload.php';
session_start();

$log = new Monolog\Logger('my-log');

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => 'ff684851-98e9-4c1a-b1f7-5182e49d1faf',    // The client ID assigned to you by the provider
    'clientSecret'            => 'a566bed5-0314-40da-9ecf-b6e1b093e070',   // The client password assigned to you by the provider
    'redirectUri'             => 'https://cf-php-auth.apps.home.pcfdot.com/index.php',
    'scopes'                  => 'openid todo.read',
    'urlAuthorize'            => 'https://sso-auth-domain.login.sys.home.pcfdot.com/oauth/authorize',
    'urlAccessToken'          => 'https://sso-auth-domain.login.sys.home.pcfdot.com/oauth/token',
    'urlResourceOwnerDetails' => 'https://cf-php-auth.apps.home.pcfdot.com/todo.php', // 'https://resource-server-sample.apps.home.pcfdot.com/todo'
    'verify'                  => false
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    try {

        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo $accessToken->getToken() . "\n";
        echo $accessToken->getRefreshToken() . "\n";
        echo $accessToken->getExpires() . "\n";
        echo ($accessToken->hasExpired() ? 'expired' : 'not expired') . "\n";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);

        var_export($resourceOwner->toArray());

        // The provider provides a way to get an authenticated API request for
        // the service, using the access token; it returns an object conforming
        // to Psr\Http\Message\RequestInterface.
        $request = $provider->getAuthenticatedRequest(
            'GET',
            'http://localhost:8080/todo.php',
            $accessToken
        );

//        var_dump($provider->getResponse($request));

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        exit($e->getMessage());

    }

}

// Token Refresh logic

// $existingAccessToken = getAccessTokenFromYourDataStore();

// if ($existingAccessToken->hasExpired()) {
//     $newAccessToken = $provider->getAccessToken('refresh_token', [
//         'refresh_token' => $existingAccessToken->getRefreshToken()
//     ]);

//     // Purge old access token and store new access token to your data store.
// }
?>