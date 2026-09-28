<?php
/*
Traefik ForwardAuth med Azure AD (Entra ID) + gruppkrav
?required_group=Grupp1,Grupp2,Grupp3  → Användaren måste vara medlem i minst EN av grupperna

forwardauth.php  (anropas av Traefik/reverse proxy för VARJE skyddad request)
 ├─ includeDirectory("./functions/common")
 ├─ includeDirectory("./functions/forwardauth")
 ├─ ensureSessionWritable()                        [common]
 ├─ OM giltig session finns (user.id + expires_at > now):
 │    ├─ gruppkontroll mot ?required_group=A,B,C  (OR-logik)
 │    ├─ sliding expiration (förlänger sessionen)
 │    └─ svarar 200 (Traefik släpper igenom requesten) eller 403
 └─ OM ingen giltig session:
      ├─ sparar return_to (dit användaren ska tillbaka efter inloggning)
      ├─ getAzureAuthUrl()  → bygger Azure-inloggnings-URL + sparar oauth2state
      └─ 302 redirect till Azure-inloggningen
*/

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
includeDirectory("./functions/forwardauth");

ensureSessionWritable();

/*
error_log(sprintf(
    "forwardauth: sid=%s cookie=%s host=%s uri=%s has_user=%s has_state=%s",
    session_id(),
    $_COOKIE['PHPSESSID'] ?? 'saknas',
    $_SERVER['HTTP_HOST'] ?? '-',
    $_SERVER['REQUEST_URI'] ?? '-',
    isset($_SESSION['user']) ? 'ja' : 'nej',
    isset($_SESSION['oauth2state']) ? 'ja' : 'nej'
));
*/

$required_groups = $_GET['required_group'] ?? '';

if (!empty($_SESSION['user']['id']) && isset($_SESSION['user']['expires_at'])) {
	if ($_SESSION['user']['expires_at'] > time()) {
	
		// Gruppkontroll (OR-logik)
		if (!empty($required_groups)) {
			$required = array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, array_filter(array_map('trim', explode(',', $required_groups))));
			$user_groups = array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, $_SESSION['user']['groups'] ?? []);

			if (!array_intersect($required, $user_groups)) {
				session_write_close();
				http_response_code(403);
				echo "Åtkomst nekad. Krävs någon av följande grupper: " . htmlspecialchars($required_groups);
				exit(0);
			}
		}
		
		require('./constants/forwardauthSessionConfig.php');
		
		// === SLIDING EXPIRATION ===
		$newExpires = time() + $forwardauthSessionConfig['slideExtension'];
		if ($newExpires > $_SESSION['user']['expires_at']) {
			$_SESSION['user']['expires_at'] = min($newExpires, time() + $forwardauthSessionConfig['absoluteMax']);
		}
		
		session_write_close();

		// Header för Varnish-cache
		$ttl = $_SESSION['user']['expires_at'] - time();
		header('X-Auth-TTL: ' . $ttl);
		
		// Valfria headers att skicka
		//header('X-Forwarded-User: ' . $_SESSION['user']['id']);
		//header('X-Forwarded-Email: ' . ($_SESSION['user']['mail'] ?? ''));
		//header('X-Forwarded-Name: ' . ($_SESSION['user']['name'] ?? ''));

		http_response_code(200);
		exit(0);
	} else {
		// Session har gått ut → rensa den
		unset($_SESSION['user']);
	}
}

if (!empty($_GET['return_to']) && isSafeReturnTo($_GET['return_to'])) {
    $_SESSION['return_to'] = $_GET['return_to'];
} else {
    if (!empty($_GET['return_to'])) {
        error_log("Otillåten return_to: " . $_GET['return_to']);
    }
	$scheme = $_SERVER['HTTP_X_ORIGINAL_PROTO'] ?? $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https';
	$host   = $_SERVER['HTTP_X_ORIGINAL_HOST']  ?? $_SERVER['HTTP_X_FORWARDED_HOST']  ?? $_SERVER['HTTP_HOST'];
	$uri    = $_SERVER['HTTP_X_ORIGINAL_URI']   ?? $_SERVER['HTTP_X_FORWARDED_URI']   ?? $_SERVER['REQUEST_URI'];
    $_SESSION['return_to'] = $scheme . '://' . $host . $uri;
}

$method = $_SERVER['HTTP_X_ORIGINAL_METHOD'] ?? $_SERVER['HTTP_X_FORWARDED_METHOD'] ?? 'GET';
$_SESSION['return_to_was_post'] = ($method === 'POST');

if (empty($_SESSION['oauth2state'])) {
    $authUrl = getAzureAuthUrl(); // sätter $_SESSION['oauth2state']
    $_SESSION['authUrl'] = $authUrl;
} else {
    // Auth-flöde redan igång – återanvänd samma URL och state
    $authUrl = $_SESSION['authUrl'];
}

session_write_close();
header('Location: ' . $authUrl);
http_response_code(302);
exit(0);