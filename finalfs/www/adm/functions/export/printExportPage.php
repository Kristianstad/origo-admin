<?php

// Takes a message (HTML string) and prints it as a complete page with the export stylesheet.
function printExportPage(string $messageHtml): void
{
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
HTML;
    require './styles/export.css';
    echo <<<HTML
    </style>
</head>
<body>
HTML;
    echo $messageHtml;
    echo <<<HTML
</body>
</html>
HTML;
}
