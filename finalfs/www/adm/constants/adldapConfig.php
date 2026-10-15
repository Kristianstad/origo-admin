<?php

    // An associative array containing the configuration used by Adldap2 to connect to an active directory/LDAP.
    $adldapConfig = [
        'hosts'				=> ['example.se'],
        'base_dn'			=> 'dc=example,dc=se',
        'username'			=> 'adminuser@example.se',
        'password'			=> 'secret',
        'port'				=> 636,
        'use_ssl'			=> true,
        'use_tls'			=> false,
        'version'			=> 3,
        'timeout'			=> 5,
        'follow_referrals'	=> false,
    ];