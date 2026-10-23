<?php

function isSafeReturnTo(string $url): bool {
    require './constants/trustedDomain.php';
    $host = parse_url($url, PHP_URL_HOST);
    if (empty($host)) {
        return false;
    }
    return $trustedDomain === '' || isHostInDomain($host, $trustedDomain);
}
