<?php

function commonCurlSetopt(CurlHandle $curl): void
{
    curl_setopt_array($curl, array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_COOKIE => 'PHPSESSID='.session_id(),
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_DNS_CACHE_TIMEOUT => 3600,
    ));
}
