# Add OIDC/OAuth2 Authentication flow to your PHP App to work alongside PCF SSO/UAA

This is a fork of [aaronpk/quick-php-authentication](https://github.com/aaronpk/quick-php-authentication) with few changes to make it work with PCF SSO/UAA.

The file [index.php](index.php) implements an oauth2-client whereas [todo.php](todo.php) serves the purpose of an oauth-resource-server.

I have added couple of dependencies that you need to pull in:
```shell script
composer require monolog/monolog
composer require firebase/php-jwt
composer require league/oauth2-client
```
