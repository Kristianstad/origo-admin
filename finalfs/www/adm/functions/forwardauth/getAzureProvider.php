<?php

require_once('../../composer/oauth2-azure/autoload.php');
require_once('../../composer/oauth2-client/autoload.php');

/**
 * Returnerar en konfigurerad Azure Provider
 */
function getAzureProvider()
{
    require('./constants/azureConfig.php');
    $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'                => $azureConfig['clientId'],
        'clientSecret'            => $azureConfig['clientSecret'],
        'redirectUri'             => $azureConfig['redirectUri'],
        'tenant'                  => $azureConfig['tenant'] ?? 'common',
        'scopes'                  => $azureConfig['scopes'],
        
        // Tvinga Microsoft Graph (viktigt!)
        'urlAPI'                  => 'https://graph.microsoft.com/',
        'defaultEndPointVersion'  => \TheNetworg\OAuth2\Client\Provider\Azure::ENDPOINT_VERSION_2_0,
    ]);

    return $provider;
}