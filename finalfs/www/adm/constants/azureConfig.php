<?php
/**
 * Azure AD (Microsoft Entra ID) konfiguration
 */

$azureConfig = [
    'clientId'       => 'xxx',                    												// Obligatorisk
    'clientSecret'   => 'xxx',                													// Obligatorisk
    'redirectUri'    => 'https://your.domain.com/php/forwardauth/azure-callback-loader.php',	// Ändra!
    'tenant'         => 'common',          														// 'common' = alla konton, eller ange din Tenant ID
	'scopes'         => [
        'openid',
        'profile',
        'email',
        'offline_access',
        'User.Read',
        'GroupMember.Read.All'
    ]
];