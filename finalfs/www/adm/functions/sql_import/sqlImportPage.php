<?php

function sqlImportPage(array $skin, string $message, bool $success): void
{
    echo <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>SQL-import</title>
<style>
HTML;

    printSkinVariables($skin);
    require("./styles/sql_import.css");

    $class = $success ? 'importSuccessMessage' : 'importErrorMessage';
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
</style>
<script>
\twindow.onload = function() {
\t\tif (window.parent !== window) {
\t\t\twindow.parent.postMessage({ action: 'resize' }, window.location.origin);
\t\t}
\t};
</script>
</head>
<body>
<div class="$class">$safeMessage</div>
<button class="updateButton" type="button" title="Stäng" aria-label="Stäng" onclick="window.parent.postMessage({ action: 'close' }, window.location.origin);"><span aria-hidden="true">&#x22A0;</span></button>
</body>
</html>
HTML;
    exit;
}