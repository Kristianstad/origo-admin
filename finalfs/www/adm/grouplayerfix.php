<?php
/*
Proxy för QGIS Server med stöd för describeFeatureType och filter på valfria grupplager (WMS+WFS)

grouplayerfix.php
 ├─ includeDirectory("./functions/grouplayerfix")   [OBS: common INTE inkluderad, se nedan]
 ├─ läser $_GET['qgis_url'] – vilken QGIS-server som ska anropas
 ├─ validerar qgis_url mot regex (http/https eller relativ path)
 └─ tre grenar beroende på anropstyp:
     │
     ├─ [1] WMS GetMap med FILTER på ett grupplager:
     │    ├─ getCachedProjectSettings($url)      → hämtar/cachar XML-projektstruktur
     │    ├─ getLayerNamesInGroup($xml, $lager)  → rekursivt: är detta en grupp? om ja, vilka underlager?
     │    ├─ expanderar LAYERS-parametern till kommaseparerad lista av underlager
     │    ├─ justerar FILTER-parametern på samma sätt
     │    └─ forwardToQgisServer($url, $params)  → skickar vidare det modifierade anropet
     │
     ├─ [2] WFS describeFeatureType på ett grupplager:
     │    ├─ getCachedProjectSettings($url)
     │    ├─ getLayerNamesInGroup($xml, $typeName)
     │    ├─ OM inte grupp → getCachedDescribeFeatureType() för det enskilda lagret
     │    └─ OM grupp → parallella curl-anrop (curl_multi) mot describeFeatureType
     │         för VARJE underlager samtidigt, med individuell retry per anrop,
     │         slår sedan ihop alla featureTypes till ett enda svar
     │
     └─ [3] Alla andra anrop → forwardToQgisServer($url, $params) oförändrat
*/

// Expose specific functions
require_once("./functions/includeDirectory.php");
	
// Expose all functions in given folders
//includeDirectory("./functions/common");
includeDirectory("./functions/grouplayerfix");

if (!function_exists('apcu_fetch')) {
    error_log("APCu inte tillgängligt - använder filcache för GetProjectSettings");
}

// Konfiguration - default fallback-URL om qgis_url saknas i query
//$DEFAULT_QGIS_SERVER_URL = 'https://10.3.1.143/qgisserver-internt/ows/kommunagd_mark';
$DEFAULT_QGIS_SERVER_URL = '';

// Läs qgis_url från query
$qgis_url = $_GET['qgis_url'] ?? $DEFAULT_QGIS_SERVER_PATH;

// Om relativ path → bygg full URL från klientens request
if (strpos($qgis_url, 'http') !== 0 && strpos($qgis_url, '/') === 0) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $qgis_url = $protocol . $host . $qgis_url;
}

// GROK: Validering – måste vara giltig https/http-URL
if (!preg_match('#^https?://[a-z0-9\.-]+(:[0-9]+)?(/.*)?$#i', $qgis_url)) {
    http_response_code(400);
    header('Content-Type: application/json;charset=utf-8');
    echo json_encode([
        'error' => 'Invalid qgis_url. Must be a valid HTTP/HTTPS URL or relative path starting with /.',
        'example' => '?qgis_url=https://example.com/ows/project or ?qgis_url=/internal/path'
    ]);
    exit;
}

$QGIS_SERVER_URL = $qgis_url;

$params = $_GET;

// ------------------------------------------------------------------------
// Specialfall 1: WMS GetMap med FILTER på ett grupplager → expandera LAYERS + FILTER
// ------------------------------------------------------------------------
$isGetMapWithFilterOnGroup = 
    isset($params['REQUEST']) && strtoupper($params['REQUEST']) === 'GETMAP' &&
    isset($params['SERVICE']) && strtoupper($params['SERVICE']) === 'WMS' &&
    isset($params['FILTER']) && trim($params['FILTER']) !== '' &&
    (isset($params['LAYERS']) || isset($params['layers']));

if ($isGetMapWithFilterOnGroup) {
    $layerParamKey = isset($params['LAYERS']) ? 'LAYERS' : 'layers';
    $requestedLayer = $params[$layerParamKey];

    // Hämta cachat eller färskt projektstruktur
    $projectXmlRaw = getCachedProjectSettings($QGIS_SERVER_URL);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($projectXmlRaw);
    if ($xml === false) {
        // GROK: Vid parsefel → vidarebefordra oförändrat istället för att krascha
        $response = forwardToQgisServer($QGIS_SERVER_URL, $params);
        echo $response['body'];
        exit;
    }

    $layerNames = getLayerNamesInGroup($xml, $requestedLayer);

    if (!empty($layerNames)) {
        // Det är en grupp → modifiera LAYERS och FILTER

        $expandedLayers = implode(',', $layerNames);

        // Modifiera FILTER: byt ut grupp-namnet mot kommaseparerad lista
        // Exempel: aktuella_evenemang:"start" = '2025-10-09'
        // → aktuella_evenemang_punkt,aktuella_evenemang_linje,aktuella_evenemang_yta:"start" = '2025-10-09'
        $filterValue = $params['FILTER'];
        if (strpos($filterValue, $requestedLayer . ':') === 0) {
            $filterValue = $expandedLayers . substr($filterValue, strlen($requestedLayer));
        }
        // GROK: Om filtret inte börjar med grupp:namn: → lämna oförändrat (ovanligt men möjligt)

        $params[$layerParamKey] = $expandedLayers;
        $params['FILTER'] = $filterValue;

        // GROK: Uppdatera också layers om det finns dubbla parametrar (QGIS hanterar ibland båda)
        if (isset($params['layers'])) {
            $params['layers'] = $expandedLayers;
        }
    }
    // Om inte grupp → $params lämnas oförändrade
}

// ------------------------------------------------------------------------
// Specialfall 2: describeFeatureType på grupp (tidigare kod, oförändrad)
// ------------------------------------------------------------------------
$isDescribeFeatureType = isset($params['request']) &&
                         strtolower($params['request']) === 'describefeaturetype' &&
                         isset($params['service']) &&
                         strtoupper($params['service']) === 'WFS' &&
                         isset($params['typeName']);
						 
if ($isDescribeFeatureType) {
    $requestedTypeName = $params['typeName'];

    // Steg 1: Hämta cachad projektstruktur
    $projectXmlRaw = getCachedProjectSettings($QGIS_SERVER_URL);

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($projectXmlRaw);
    if ($xml === false) {
        http_response_code(500);
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode(['error' => 'Failed to parse GetProjectSettings XML']);
        exit;
    }

    // Steg 2: Kolla om det är grupp
    $layerNames = getLayerNamesInGroup($xml, $requestedTypeName);

    if (empty($layerNames)) {
        // Enskilt lager → cachad eller färsk describe
        $jsonRaw = getCachedDescribeFeatureType($QGIS_SERVER_URL, $requestedTypeName);
        $json = json_decode($jsonRaw, true);

        header('Content-Type: ' . ($json && isset($json['featureTypes']) ? 'application/vnd.geo+json; charset=utf-8' : 'application/json;charset=utf-8'));
        echo $jsonRaw;
        exit;
    }

    // Grupp → parallell hämtning av describe för alla lager
    $combinedFeatureTypes = [];
    $baseResponse = null;
    $contentTypeFromBackend = 'application/json; charset=utf-8';

    // Dynamisk TTL för cache i parallella anrop
    $ttl = 600;
    if (isset($_GET['ttl']) && is_numeric($_GET['ttl']) && $_GET['ttl'] > 0) {
        $ttl = (int)$_GET['ttl'];
    }

    // Grupp → parallell hämtning av describe för alla lager med retry per handle
    $combinedFeatureTypes = [];
    $baseResponse = null;
    $contentTypeFromBackend = 'application/json; charset=utf-8';

    // Dynamisk TTL för cache
    $ttl = 600;
    if (isset($_GET['ttl']) && is_numeric($_GET['ttl']) && $_GET['ttl'] > 0) {
        $ttl = (int)$_GET['ttl'];
    }

    $mh = curl_multi_init();
    $handles = [];          // $layerName => $ch
    $retryCounts = [];      // $layerName => antal retries gjorda
    $maxRetriesPerHandle = 4;

    // Initiera alla handles
    foreach ($layerNames as $layerName) {
        $descParams = [
            'request' => 'describeFeatureType',
            'outputFormat' => 'application/json',
            'service' => 'WFS',
            'typeName' => $layerName,
        ];

        $ch = curl_init($QGIS_SERVER_URL . '?' . http_build_query($descParams));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 25,
            CURLOPT_DNS_CACHE_TIMEOUT => 3600,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,   // GROK: Tvinga IPv4 – undvik IPv6-timeout
            //CURLOPT_STDERR => fopen('/tmp/curl_verbose.log', 'a'),
            //CURLOPT_VERBOSE => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        curl_multi_add_handle($mh, $ch);
        $handles[$layerName] = $ch;
        $retryCounts[$layerName] = 0;
    }

    // Huvudloop: Kör tills alla handles är klara
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    // Processa alla handles – retry vid behov
    foreach ($handles as $layerName => $ch) {
        $success = false;
        $attempt = 0;

        while ($attempt < $maxRetriesPerHandle && !$success) {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);  // GROK: Måste köras innan curl_close
            $raw = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            if ($raw !== false && $httpCode >= 200 && $httpCode < 300) {
                $success = true;
            } else {
                $attempt++;
                $retryCounts[$layerName] = $attempt;

                if ($attempt < $maxRetriesPerHandle) {
                    // Ta bort och återinitiera handle för retry
                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);

                    // Återskapa handle
                    $descParams = [
                        'request' => 'describeFeatureType',
                        'outputFormat' => 'application/json',
                        'service' => 'WFS',
                        'typeName' => $layerName,
                    ];

                    $ch = curl_init($QGIS_SERVER_URL . '?' . http_build_query($descParams));
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HEADER => true,
                        CURLOPT_TIMEOUT => 60,
                        CURLOPT_CONNECTTIMEOUT => 25,
                        CURLOPT_DNS_CACHE_TIMEOUT => 3600,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,   // GROK: Tvinga IPv4 – undvik IPv6-timeout
                        //CURLOPT_STDERR => fopen('/tmp/curl_verbose.log', 'a'),
                        //CURLOPT_VERBOSE => true,
                        CURLOPT_HTTPHEADER => ['Accept: application/json'],
                    ]);

                    curl_multi_add_handle($mh, $ch);
                    $handles[$layerName] = $ch;

                    // Vänta lite innan retry
                    usleep(400000 * (1 << ($attempt - 1)));

                    // Kör om multi för denna handle
                    $running = 1;
                    do {
                        curl_multi_exec($mh, $running);
                        curl_multi_select($mh);
                    } while ($running > 0);
                }
            }
        }

        // Slutlig parsing av sista försöket
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($raw, $headerSize);

        // Cache svaret (även om det misslyckades – men bara vid framgång)
        if ($success) {
            $cacheKey = 'qgis_describe_' . md5($QGIS_SERVER_URL . '|' . $layerName . '|json');
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $body, $ttl);
            }
            $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $cacheKey . '.json.cache';
            file_put_contents($cacheFile, $body, LOCK_EX);
        }

        $json = json_decode($body, true);

        if (!$success || json_last_error() !== JSON_ERROR_NONE || !isset($json['featureTypes']) || empty($json['featureTypes'])) {
            // error_log("Lager $layerName misslyckades efter $attempt retries");
            continue;
        }

        // Parse Content-Type (robust variant)
        $headerString = substr($raw, 0, $headerSize);
        $headerLines = preg_split('/\r\n|\r|\n/', $headerString);
        foreach ($headerLines as $line) {
            $lineLower = strtolower(trim($line));
            if (strpos($lineLower, 'content-type:') === 0) {
                $contentTypeFromBackend = trim(substr($line, strpos($line, ':') + 1));
                break;
            }
        }

        if ($baseResponse === null) {
            $baseResponse = $json;
            $combinedFeatureTypes = $json['featureTypes'];
        } else {
            $combinedFeatureTypes = array_merge($combinedFeatureTypes, $json['featureTypes']);
        }
    }

    curl_multi_close($mh);

    if ($baseResponse === null) {
        $baseResponse = [
            'elementFormDefault' => 'qualified',
            'featureTypes' => [],
            'targetNamespace' => 'http://www.qgis.org/gml',
            'targetPrefix' => 'qgs'
        ];
    } else {
        $baseResponse['featureTypes'] = $combinedFeatureTypes;
    }

    header('Content-Type: ' . $contentTypeFromBackend);
    $jsonOutput = json_encode($baseResponse);
    header('Content-Length: ' . strlen($jsonOutput));
    echo $jsonOutput;
    exit;
} else {
    // Alla andra anrop → vidarebefordra direkt
    $response = forwardToQgisServer($QGIS_SERVER_URL, $params);
    echo $response['body'];
    exit;
}