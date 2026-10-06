<?php
// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/updated");

require("./constants/dbhConnectionStringForUpdated.php");
$dbh = dbh($dbhConnectionStringForUpdated);

$tablesWithSchema = $_GET['table'] ?? '';
if (!is_string($tablesWithSchema) || trim($tablesWithSchema) === '') {
    pg_close($dbh);
    http_response_code(400);
    exit('Invalid table');
}
$tablesWithSchema = array_map('trim', explode(',', $tablesWithSchema));

require("./constants/configSchema.php");
$registeredTableIds = array_column(allFromTable($dbh, $configSchema, 'tables'), 'table_id');
$allowedTables = array();
foreach ($registeredTableIds as $registeredTableId) {
    $parts = explode('.', $registeredTableId);
    if (count($parts) >= 2) {
        $allowedTables[] = implode('.', array_slice($parts, -2));
    }
}
foreach ($tablesWithSchema as $tableWithSchema) {
    if ($tableWithSchema === '' || !in_array($tableWithSchema, $allowedTables, true)) {
        pg_close($dbh);
        http_response_code(400);
        exit('Invalid table');
    }
}

$updates = array();
foreach ($tablesWithSchema as $tableWithSchema) {
    $updated = updatedFromTable2($dbh, $tableWithSchema);
    if ($updated === false) {
        pg_close($dbh);
        http_response_code(400);
        exit('Invalid table');
    }
    if (isset($updated[1])) {
        $updates[$updated[0]] = $updated[1];
    }
}

pg_close($dbh);

arsort($updates);
$updated = key($updates);
$updated = substr($updated, 0, 10);

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<style>
HTML;

require("./styles/updated.css");

echo <<<HTML
	</style>
</head>
<body>
{$updated}
</body>
</html>
HTML;