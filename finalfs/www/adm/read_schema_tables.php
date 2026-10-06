<?php
// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");

$dbSchema = explode('.', $_GET['schema'] ?? '', 2);
unset($_GET);

$database = $dbSchema[0] ?? '';
$schema   = $dbSchema[1] ?? '';

if ($database === '' || $schema === '') {
    http_response_code(400);
    exit('Missing schema parameter');
}

$dbh_config = dbh();
require("./constants/configSchema.php");

$connectionString = arrayColumnSearch(
    $database,
    'database_id',
    allFromTable($dbh_config, $configSchema, 'databases')
)['connectionstring'] ?? null;

if (!$connectionString) {
    pg_close($dbh_config);
    http_response_code(404);
    exit('Database not found');
}

$dbh = dbh($connectionString);
unset($connectionString);

foreach (tableNamesFromSchema($dbh, $schema) as $tableName) {
    // Använd parametriserad SQL för att undvika SQL-injection
    $tableId = $database . '.' . $schema . '.' . $tableName;
    $sql = "INSERT INTO {$configSchema}.tables(table_id) VALUES ($1) ON CONFLICT (table_id) DO NOTHING;";
    $result = pg_query_params($dbh_config, $sql, [$tableId]);

    if (!$result) {
        error_log('Error in SQL query: ' . pg_last_error($dbh_config));
        pg_close($dbh);
        pg_close($dbh_config);
        http_response_code(500);
        exit('Database error');
    }
}

pg_close($dbh);
pg_close($dbh_config);