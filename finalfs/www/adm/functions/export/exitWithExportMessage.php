<?php

// Takes the database handle, an HTTP status code (int) and a message (HTML string).
// Closes the database connection, answers with the message as a page and ends the script.
function exitWithExportMessage($dbh, int $status, string $messageHtml): never
{
    pg_close($dbh);
    http_response_code($status);
    printExportPage($messageHtml);
    exit;
}
