<?php
/*
mapstate.php
 ├─ includeDirectory("./functions/common")   → laddar common (men bara dbh() används)
 ├─ includeDirectory("./functions/mapstate") → laddar samtliga 6 funktionsfiler nedan
 ├─ dbh()                                    [common] – öppnar databasanslutning
 ├─ cleanupOldMapStates($dbh, $mapstateMaxUnused)  → körs på VARJE request, oavsett metod
 └─ switch på HTTP-metod:
     ├─ OPTIONS → stänger anslutning, avslutar (CORS-preflight)
     ├─ POST    → createMapState($dbh)
     ├─ GET     → retrieveMapState($dbh) → validateMapStateId() → updateLastUse()
     └─ övrigt  → 405 Method Not Allowed
*/

require_once __DIR__ . '/functions/includeDirectory.php';
includeDirectory(__DIR__ . '/functions/common');
includeDirectory(__DIR__ . '/functions/mapstate');

require __DIR__ . '/constants/mapstateMaxUnused.php';

$dbh = dbh();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    pg_close($dbh);
    exit(0);
}

if (!$dbh) {
    http_response_code(500);
    echo json_encode(['error' => 'Databasanslutning saknas']);
    exit;
}

cleanupOldMapStates($dbh, $mapstateMaxUnused);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    createMapState($dbh);
    pg_close($dbh);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    retrieveMapState($dbh);
    pg_close($dbh);
    exit;
}

pg_close($dbh);
http_response_code(405);
echo json_encode(['error' => 'Metod ej tillåten']);