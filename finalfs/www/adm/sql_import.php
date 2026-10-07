<?php
/* Kör uppladdad eller inklistrad SQL mot Origos konfigurationsdatabas. */

header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
includeDirectory("./functions/sql_import");
require("./constants/configSchema.php");

$dbh=dbh();
$currentSkin=currentSkin(allFromTable($dbh, $configSchema, 'skins'));
pg_close($dbh);

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    $csrfToken=generateCsrfToken();
    $helpSql=topFormHelpButton('sql_import:sql');
	$helpSqlFile=topFormHelpButton('sql_import:sql_file');
    echo <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>SQL-import</title>
HTML;
    $closeButton = renderCloseButton('updateButton');
    renderUtilityHead($currentSkin, 'sql_import');
    echo '<script>';
    includeDirectory("./js-functions/sql_import");
    echo '</script>';
    echo <<<HTML
</head>
<body>
<form method="post" enctype="multipart/form-data"
    onsubmit="return confirmSqlImport();">
	<input type="hidden" name="csrf_token" value="$csrfToken">
	<div class="sqlImportForm">
        <span class="optionSpan"><label title="sql_import:sql" for="sql">SQL-text:</label> <textarea class="textareaXLarge" id="sql" name="sql" rows="1"></textarea>{$helpSql}</span><wbr>
        <span class="optionSpan sqlFileOption"><label title="sql_import:sql_file" for="sql_file">SQL-fil:</label>{$helpSqlFile}<input class="sqlImportFile" type="file" id="sql_file" name="sql_file" accept=".sql,text/plain" onchange="updateSqlInputState(this)"></span><wbr>
		<div class="readJsonButtonDiv">
			<button class="updateButton" type="submit">Kör SQL</button>
            $closeButton
		</div>
	</div>
</form>
</body>
</html>
HTML;
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null))
{
    sqlImportError('Ogiltig eller saknad säkerhetstoken. Ladda om formuläret och försök igen.');
}

$sql=(string) ($_POST['sql'] ?? '');
if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] !== UPLOAD_ERR_NO_FILE)
{
    if ($_FILES['sql_file']['error'] !== UPLOAD_ERR_OK)
    {
        sqlImportError('SQL-filen kunde inte laddas upp.');
    }
    if ($_FILES['sql_file']['size'] > 10 * 1024 * 1024)
    {
        sqlImportError('SQL-filen får vara högst 10 MB.');
    }
    $sql=(string) file_get_contents($_FILES['sql_file']['tmp_name']);
}

if (trim($sql) === '')
{
    sqlImportError('Ange SQL-text eller välj en SQL-fil.');
}

$dbh=dbh();
$existingMapIds=array();
$existingMapsResult=pg_query($dbh, "SELECT map_id FROM {$configSchema}.maps");
if ($existingMapsResult === false)
{
    $error=pg_last_error($dbh);
    pg_close($dbh);
    sqlImportError('SQL-importen kunde inte förberedas: '.$error);
}
while ($row=pg_fetch_assoc($existingMapsResult))
{
    $existingMapIds[$row['map_id']]=true;
}
$result=pg_query($dbh, $sql);
if ($result === false)
{
    $error=pg_last_error($dbh);
    pg_close($dbh);
    sqlImportError('SQL-importen misslyckades: '.$error);
}
try
{
    markNewMapsChanged($dbh, $configSchema, $existingMapIds);
}
catch (Throwable $exception)
{
    $error=$exception->getMessage();
    pg_close($dbh);
    sqlImportError('SQL-importen lyckades, men nya kartor kunde inte markeras som ändrade: '.$error);
}
pg_close($dbh);

sqlImportPage($currentSkin, 'SQL-import lyckades!', true);