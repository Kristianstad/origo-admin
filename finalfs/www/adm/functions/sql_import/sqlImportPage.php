<?php

function sqlImportPage(array $skin, string $message, bool $success): void
{
    $class = $success ? 'importSuccessMessage' : 'importErrorMessage';
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $closeButton = renderCloseButton('updateButton');

    echo <<<HTML
<!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<title>SQL-import</title>
HTML;
    renderUtilityHead($skin, 'sql_import');
    echo <<<HTML
</head>
<body>
<div class="$class">$safeMessage</div>
$closeButton
</body>
</html>
HTML;
    exit;
}