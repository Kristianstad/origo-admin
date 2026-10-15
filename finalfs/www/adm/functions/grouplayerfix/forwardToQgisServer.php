<?php

// Hjälpfunktion: Skicka vidare anrop till QGIS Server med retry vid transienta fel
function forwardToQgisServer($url, $params, $maxRetries = 4) {
    $queryString = http_build_query($params);
    $fullUrl = $url . ($queryString ? '?' . $queryString : '');

    $retryCount = 0;
    $lastError = '';

    while ($retryCount < $maxRetries) {
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);                // GROK: Total timeout 60s – ger tid för långsamma svar
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);         // GROK: Ökad från 10 → 25s för att ge DNS mer tid
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 3600);    // GROK: Cache DNS-svar i 1 timme inom processen
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'QGIS-Proxy/1.0 (via cURL)');
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);// GROK: Tvinga IPv4 – undvik IPv6-timeout
        //curl_setopt($ch, CURLOPT_STDERR, fopen('/tmp/curl_verbose.log', 'a'));
        //curl_setopt($ch, CURLOPT_VERBOSE, true);

        $requestHeaders = ['Accept: application/json, text/xml, */*'];
        if (isset($_SERVER['HTTP_REFERER'])) {
            $requestHeaders[] = 'Referer: ' . $_SERVER['HTTP_REFERER'];
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        // Lyckades (200–399) eller permanent fel (400–499) → bryt loopen
        if ($rawResponse !== false && $httpCode > 0) {
            break;
        }

        $retryCount++;
        $lastError = $error;

        if ($retryCount < $maxRetries) {
            // Backoff: 400ms → 800ms → 1600ms → 3200ms
            usleep(400000 * (1 << ($retryCount - 1)));
            // error_log("Retry $retryCount för URL $fullUrl - fel: $error");
            continue;
        }

        // Alla retries misslyckades
        http_response_code(502);
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode([
            'error' => 'QGIS Server request failed after retries',
            'http_code' => $httpCode ?: 0,
            'curl_error' => $lastError,
            'url' => $fullUrl,
            'retries_attempted' => $retryCount
        ]);
        exit;
    }

    // Parsing av headers och body
    $headerString = substr($rawResponse, 0, $headerSize);
    $body = substr($rawResponse, $headerSize);

    $responseHeaders = [];
    $headerLines = preg_split('/\r\n|\r|\n/', $headerString);
    foreach ($headerLines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key !== '') {
                $responseHeaders[strtolower($key)] = $value;
            }
        }
    }

    // Vidarebefordra headers (utan Content-Length)
    $forwardKeys = ['content-type', 'cache-control', 'expires', 'pragma', 'date', 'last-modified'];
    foreach ($forwardKeys as $key) {
        if (isset($responseHeaders[$key])) {
            header(ucwords($key, '-') . ': ' . $responseHeaders[$key]);
        }
    }

    http_response_code($httpCode);

    return [
        'body'    => $body,
        'headers' => $responseHeaders,
    ];
}