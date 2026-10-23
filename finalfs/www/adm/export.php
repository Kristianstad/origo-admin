<?php
/*
export.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/export")
 ├─ readAndCloseSession(), dbh(), initUserLdap()   [common]
 ├─ ej inloggad, FME-token saknas eller ogiltigt värdnamn → exitWithExportMessage()
 ├─ printExportPage() → "du får ett mejl"; fastcgi_finish_request() skickar normalt svaret (inte vid felsökning)
 └─ runExportJob()                                 [export] → resten av exporten, efter att svaret skickats:
      ├─ exportRequest()           → validerar parametrarna och räknar ut bounding box, bredd och höjd
      ├─ allFromTable(), allLayerIds()  → lagerna i kartan
      ├─ createExportJob(), registerExportCleanup()  → tillfällig katalog som städas när skriptet slutar
      ├─ writeExportDrawings()     → ritade lager från request-body
      ├─ exportLayerRequests(), exportTargets(), fetchExportData()
      │                            → hämtar data från karttjänsten (WMS/WFS) enligt formatet
      ├─ exportFmeParameters()     → parametrar till FME-arbetsflödet enligt formatet
      └─ submitFmeJob()            → FME Flow konverterar och mejlar resultatet till användaren

Inställningar: constants/exportConfig.php (servrar, kataloger, FME), constants/exportFormats.php
(vad varje exportformat gör), constants/fmeToken.php (hemlig token) och constants/trustedDomain.php.
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/export");

readAndCloseSession();

$dbh = dbh();
require './constants/authMethod.php';

if ($authMethod === 'ldap') {
    initUserLdap($dbh);
}

if (empty($_SESSION['user'])) {
    exitWithExportMessage($dbh, 200, '<b style="color:#ff0000">Ej inloggad!</b>');
}

require './constants/fmeToken.php';
if ($fmeToken === '' || $fmeToken === 'REPLACE_WITH_FME_FLOW_TOKEN') {
    error_log('FME token constant is empty.');
    exitWithExportMessage($dbh, 503, '<b>Exporttjänsten är inte konfigurerad.</b>');
}

$exportBaseUrl = exportBaseUrl();
if ($exportBaseUrl === '') {
    error_log('Export host rejected: '.($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '-'));
    exitWithExportMessage($dbh, 400, '<b>Ogiltigt värdnamn.</b>');
}

// Set to true to keep the temporary files and send the response when the script ends, so that the PHP log can be read
$keepTempFilesForDebugging = false;

printExportPage('Du får ett epostmeddelande när exporten är färdig.');

if (!$keepTempFilesForDebugging && function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

runExportJob($dbh, $_SESSION['user'], $_GET, exportRequestBody(), $exportBaseUrl, $fmeToken, $keepTempFilesForDebugging);
