<?php
/*
news.php
 ├─ includeDirectory("./functions/common")   → laddar ALLA filer i common
 ├─ includeDirectory("./functions/news")     → laddar samtliga 8 funktionsfiler nedan
 ├─ readAndCloseSession()                    [common]
 ├─ dbh()                                    [common] – öppnar Postgres-anslutning
 ├─ initUserLdap($dbh)                       [common] – bara om authMethod === 'ldap'
 ├─ pgNewsArray($dbh)                        → hämtar ALLA nyheter från databasen
 ├─ userNews($username, $pgNewsArray)        → filtrerar bort nyheter användaren raderat
 ├─ selectNew($userNews, $newId)             → plockar ut EN nyhet ur listan (om newId angivet)
 └─ switch på $action:
     ├─ 'list'      → printNewsList($userNews)
    ├─ 'load'      → printNews($dbh, $username, $selectedNew, $return)
    ├─ 'delete'    → readDelete($dbh, $username, $selectedNew, 'delete')
    ├─ 'read'      → readDelete($dbh, $username, $selectedNew, 'read')
    ├─ 'subjects'  → printNewsSubjects($dbh, $username, $userNews)  → anropar printNews() internt
     └─ 'unread'    → testUnread($username, $userNews)
*/

header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
includeDirectory("./functions/news");

require './constants/authMethod.php';

readAndCloseSession();
$dbh = null;

if ($authMethod === 'ldap') {
    $dbh = dbh();
    initUserLdap($dbh);
}

$action = $_GET['action'] ?? '';
$newId  = $_GET['newId']  ?? '';
$return = isset($_GET['return']) ? explode(',', $_GET['return']) : [];

$isLoggedIn = isset($_SESSION['user']) && $_SESSION['user'] !== false;

// === Början av sidan ===
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
HTML;

require "./styles/news.css";

echo <<<HTML
</style>
</head>
<body>
HTML;

// === Innehåll ===
if ($isLoggedIn) {
    ignore_user_abort(true);

    $username = $_SESSION['user']['id'];

    if ($authMethod !== 'ldap') {
        $dbh = dbh();
    }

    $pgNewsArray = pgNewsArray($dbh);
    $userNews    = userNews($username, $pgNewsArray);

    $selectedNew = null;
    if ($newId !== '') {
        $selectedNew = selectNew($userNews, $newId);
    }

    if ($action === 'list') {
        printNewsList($userNews);
    }
    elseif ($action === 'load' && !empty($selectedNew)) {
        printNews($dbh, $username, $selectedNew, $return);
    }
    elseif (
        ($action === 'delete' || $action === 'read') &&
        !empty($selectedNew) &&
        !in_array($username, $selectedNew[$action . 's'] ?? [], true)
    ) {
        readDelete($dbh, $username, $selectedNew, $action);
    }
    elseif ($action === 'subjects') {
        printNewsSubjects($dbh, $username, $userNews);
    }
    elseif ($action === 'unread') {
        testUnread($username, $userNews);
    }

    ignore_user_abort(false);
} else {
    echo '<b style="color:#000000">Ej inloggad!</b>';
}

if (isset($dbh) && $dbh) {
    pg_close($dbh);
}

// === Slut på sidan ===
echo <<<HTML
</body>
</html>
HTML;