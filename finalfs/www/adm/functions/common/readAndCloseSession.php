<?php
function readAndCloseSession()
{
    require './constants/cookieConfig.php';

    session_start([
        'read_and_close'  => true,
        'cookie_domain'   => $cookieConfig['cookieDomain'],
        'cookie_path'     => $cookieConfig['cookiePath'],
        'cookie_secure'   => $cookieConfig['cookieSecure'],
        'cookie_httponly' => $cookieConfig['cookieHttpOnly'],
        'cookie_samesite' => $cookieConfig['cookieSameSite']
    ]);
}