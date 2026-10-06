<?php
/*
read_db_schemas.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/read_db_schemas")
 ├─ läser $_GET['database'], sedan unset($_GET) direkt (se notering nedan)
 ├─ dbh()                                    [common] → ansluter till KONFIGURATIONS-databasen
 ├─ allFromTable(...) + arrayColumnSearch(...)  [common] → slår upp anslutningssträng för $database
 ├─ dbh($connectionString)                   [common] → ansluter till DEN EXTERNA databasen
 ├─ schemaNamesFromDb($dbh)                  [read_db_schemas] → listar scheman i den externa databasen
 └─ för varje schema: INSERT ... ON CONFLICT DO NOTHING i konfigurationsdatabasens schemas-tabell
      (parameteriserad SQL via pg_query_params)
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/read_db_schemas");

$database = $_GET['database'] ?? '';
unset($_GET);

if ($database === '') {
    http_response_code(400);
    exit('Missing database parameter');
}

$dbh_config = dbh();
require "./constants/configSchema.php";

$connectionString = arrayColumnSearch(
    $database,
    'database_id',
    allFromTable($dbh_config, $configSchema, 'databases')
)['connectionstring'] ?? null;

if ($connectionString === null) {
    pg_close($dbh_config);
    http_response_code(404);
    exit('Database not found');
}

$dbh = dbh($connectionString);
unset($connectionString);

foreach (schemaNamesFromDb($dbh) as $schemaName) {
    // Parametriserad SQL – undvik SQL-injection
    $schemaId = $database . '.' . $schemaName;
    $sql = "INSERT INTO {$configSchema}.schemas(schema_id) VALUES ($1) ON CONFLICT (schema_id) DO NOTHING;";
    $result = pg_query_params($dbh_config, $sql, [$schemaId]);

    if (!$result) {
        $error = pg_last_error($dbh_config);
        pg_close($dbh);
        pg_close($dbh_config);
        error_log("Error in SQL query: " . $error);
        http_response_code(500);
        exit('Database error');
    }
}

pg_close($dbh);
pg_close($dbh_config);

// Valfritt: ge ett enkelt OK om anropet förväntar sig svar
// header('Content-Type: text/plain; charset=utf-8');
// echo 'OK';