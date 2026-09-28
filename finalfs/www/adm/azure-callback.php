<?php
/*
Azure OAuth2 Callback – med bakåtkompatibel session-struktur

azure-callback.php  (Azure skickar användaren hit efter inloggning)
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/forwardauth")
 ├─ ensureSessionWritable()
 ├─ getAzureProvider()                              → skapar OAuth2-klient
 ├─ validerar state-parameter mot $_SESSION['oauth2state']  (CSRF-skydd)
 ├─ provider->getAccessToken() + getResourceOwner() → hämtar Azure-token + användarinfo
 ├─ getAzureGroups($token)                          → hämtar användarens AD-grupper
 ├─ getOnPremisesSamAccountName($token)              → hämtar lokalt AD-användarnamn
 ├─ bygger $_SESSION['user'] med id, mail, name, groups, expires_at
 └─ redirect tillbaka till sparad return_to
*/

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
includeDirectory("./functions/forwardauth");

ensureSessionWritable();

/*
error_log(sprintf(
    "azure-callback: sid=%s cookie=%s host=%s state_session=%s state_get=%s match=%s keys=%s",
    session_id(),
    $_COOKIE['PHPSESSID'] ?? 'saknas',
    $_SERVER['HTTP_HOST'] ?? '-',
    $_SESSION['oauth2state'] ?? 'saknas',
    $_GET['state'] ?? 'saknas',
    (isset($_SESSION['oauth2state'], $_GET['state']) && $_SESSION['oauth2state'] === $_GET['state']) ? 'ja' : 'nej',
    implode(',', array_keys($_SESSION))
));
*/

$provider = getAzureProvider();

if (isset($_GET['error'])) {
    http_response_code(401);
    echo "Azure fel: " . htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
    exit(1);
}

if (!isset($_GET['code']) || !isset($_SESSION['oauth2state']) || !isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    
    $return_to = $_SESSION['return_to'] ?? null;
    $had_session = !empty($_SESSION);
    session_destroy();
    
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $safe_return = ($return_to && isSafeReturnTo($return_to))
        ? htmlspecialchars($return_to)
        : 'https://kartor.kristianstad.se';
    
    $msg = $had_session
        ? "Din session har gått ut."
        : "Inloggningen kunde inte slutföras.";
    
    http_response_code(200);
    echo "{$msg} <a href='{$safe_return}'>Klicka här för att logga in igen</a>.";
    exit(1);
}

try {
    $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    $azureUser = $provider->getResourceOwner($token);
    //$graphToken = getGraphToken($token);
    //$groups = getAzureGroups($graphToken);
    //$adUser = mb_strtolower(getOnPremisesSamAccountName($graphToken) ?? $azureUser->getId(), 'UTF-8');
	$groups = getAzureGroups($token);
    $adUser = mb_strtolower(getOnPremisesSamAccountName($token) ?? $azureUser->getId(), 'UTF-8');
	//error_log("AD-user: ".$adUser." Grupper: ".json_encode($groups));

	require('./constants/forwardauthSessionConfig.php');

    // Använd baseLifetime från sessionConfig
    $baseLifetime = $forwardauthSessionConfig['baseLifetime'] ?? 60*60*10; // 10 timmar

    // Redirect tillbaka till ursprunglig sida
    $return_to = $_SESSION['return_to'] ?? 'https://kartor.kristianstad.se';
    
    $was_post = $_SESSION['return_to_was_post'] ?? false;
	session_unset();
	session_regenerate_id(true); // true = radera gamla session-filen

	$_SESSION['user'] = [
        'id'                => $adUser,
        //'azureId'           => $azureUser->getId(),
        'mail'              => $azureUser->getUpn() ?? $azureUser->claim('email') ?? '',
        'name'              => $azureUser->getUpn() ?? $azureUser->claim('name') ?? '',
        'groups'            => $groups,
        'authenticated_at'  => time(),
        'expires_at'        => time() + $baseLifetime,
    ];

	if ($was_post) {
    	$safe_return = htmlspecialchars($return_to);
    	session_write_close();
    	echo "<!DOCTYPE html>
	<html>
	<head><meta charset='utf-8'></head>
	<body>
	<script>
    	alert('Sessionen gick ut och operationen kunde inte genomföras. Försök igen.');
    	window.location.href = '{$safe_return}';
	</script>
	</body>
	</html>";
    	exit(0);
	} else {
    	session_write_close();
    	header('Location: ' . $return_to);
    	exit(0);
	}
} catch (\Exception $e) {
	session_write_close();
    http_response_code(500);
    error_log("Azure Callback Error: " . $e->getMessage());
    echo "Ett fel uppstod vid inloggning: " . htmlspecialchars($e->getMessage());
    exit(1);
}