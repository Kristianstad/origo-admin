<?php

// Tar värdnamnet från requesten men godtar bara den egna domänen, så att anroparen inte kan styra vart karttjänstanropen skickas.
function exportBaseUrl(): string
{
    require './constants/trustedDomain.php';
    $host = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '')[0]));
    if (!preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*(:[0-9]{1,5})?$/D', $host)) {
        return '';
    }
    if ($trustedDomain !== '' && !isHostInDomain(explode(':', $host)[0], $trustedDomain)) {
        return '';
    }
    return 'https://'.$host;
}
