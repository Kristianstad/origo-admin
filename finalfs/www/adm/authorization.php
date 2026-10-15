<?php
/*
authorization.php
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/authorization")
 ├─ ensureSessionWritable()                  [common] – startar sessionen skrivbar
 ├─ (om authMethod === 'ldap') dbh() + initUserLdap($dbh)   [common]
 └─ router baserat på GET/POST:
     ├─ ?logout          → logout()                    [authorization]
     ├─ ?displaylogout    → displayLogout()             [authorization]
     ├─ (inloggad, ingen SERVICE-param) → displayLogout()
     ├─ POST               → login($dbh)                 [authorization]
     └─ annars             → displayLogin()               [authorization]
*/

// Tell browsers to not cache response
header("Cache-Control: must-revalidate, max-age=0, s-maxage=0, no-cache, no-store");

// Expose specific functions
require_once("./functions/includeDirectory.php");

// Expose all functions in given folders
includeDirectory("./functions/common");
includeDirectory("./functions/authorization");

require './constants/authMethod.php';

// Starta sessionen – VI MÅSTE KUNNA SKRIVA till den
ensureSessionWritable();

$dbh = null;

if ($authMethod === 'ldap') {
    $dbh = dbh();
    initUserLdap($dbh);
}

if (isset($_GET['logout'])) {
    logout();
}
elseif (isset($_GET['displaylogout']) || (!isset($_GET['SERVICE']) && !empty($_SESSION['user']))) {
    displayLogout();
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($authMethod !== 'ldap') {
        $dbh = dbh();
    }
    login($dbh);
}
else {
    displayLogin();
}

if (session_status() === PHP_SESSION_ACTIVE)
{
    session_write_close();
}

if (isset($dbh) && $dbh) {
    pg_close($dbh);
}

exit(0);