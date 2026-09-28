<?php
require_once('../../composer/adldap2/autoload.php');

function initUserLdap(&$dbh = false)
{
    // Om användaren redan är inloggad och sessionen inte är för gammal → hoppa över
    if (
        isset($_SESSION['user']['id']) &&
        isset($_SESSION['login_time_stamp']) &&
        (time() - $_SESSION['login_time_stamp']) < 36000
    ) {
        return false;
    }

    require './constants/cookieConfig.php';
    require './constants/adldapConfig.php';
    require './constants/adGroupFilter.php';
    require './constants/configSchema.php';

    $cookieName     = $cookieConfig['cookieName'];
    $cookieLifetime = $cookieConfig['cookieLifetime'];
	
    // === Smart cookie-förlängning ===
	// Körs bara om vi redan har en giltig session-användare
    if (isset($_SESSION['user']['id']) && isset($_COOKIE[$cookieName])) {
        $refreshThreshold = $cookieLifetime / 2;
        $lastRefresh = (int)($_COOKIE[$cookieName . '_last_refresh'] ?? 0);

        if ((time() - $lastRefresh) > $refreshThreshold) {
            $options = getCookieOptions(time() + $cookieLifetime);

            // Förläng den riktiga cookien
            setcookie($cookieName, $_COOKIE[$cookieName], $options);

            // Spara när vi senast förlängde
            setcookie($cookieName . '_last_refresh', (string)time(), $options);
        }
    }

    // === Försök hämta och dekryptera cookie ===
    $origoUserId = $_COOKIE[$cookieName] ?? $_POST[$cookieName] ?? null;

    if ($origoUserId === null) {
        clearAuthSession();
        return true;
    }

    // Dekryptera
    $decoded = base64_decode($origoUserId, true);
    if ($decoded === false || strpos($decoded, '::') === false) {
        clearAuthSession();
        return true;
    }

    list($encrypted_data, $iv) = explode('::', $decoded, 2);

    $user = openssl_decrypt($encrypted_data, 'aes-256-cbc', $cookieConfig['cookieKey'], 0, $iv);

    // Enkel validering av användarnamn
    if ($user === false || !preg_match('/^[a-z0-9._-]{2,64}$/i', $user)) {
        clearAuthSession();
        return true;
    }

    $user = strtolower($user);

    // === Hämta användarinfo från LDAP ===
    try {
        $ad = new Adldap\Adldap();
        $ad->addProvider($adldapConfig);
        $provider = $ad->connect();

        $userInfo = $provider->search()->users()->findBy('cn', $user);
        if (empty($userInfo)) {
            $userInfo = $provider->search()->users()->findBy('samaccountname', $user);
        }

        if (empty($userInfo)) {
            clearAuthSession();
            return true;
        }

        $name       = $userInfo->getDisplayName() ?? '';
        $email      = $userInfo->getEmail() ?? '';
        $company    = $userInfo->getCompany() ?? '';
        $department = $userInfo->getDepartment() ?? '';
        $adgroups = array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, array_values($userInfo->getGroupNames(true)));
        $adGroupFilter = array_map(function ($group) { return mb_strtolower($group, 'UTF-8'); }, $adGroupFilter);
        $adgroups = array_diff($adgroups, $adGroupFilter);
    } catch (Exception $e) {
        error_log('LDAP error in initUserLdap: ' . $e->getMessage());
        clearAuthSession();
        return true;
    }

    // === Skriv till session (fungerar även efter read_and_close) ===
    ensureSessionWritable();

    $_SESSION['user'] = [
        'id'     => $user,
        'mail'   => $email,
        'groups' => $adgroups,
    ];
    $_SESSION['login_time_stamp'] = time();
    session_write_close();

    // === Uppdatera databasen (parametriserad SQL) ===
    $dbclose = false;
    if (!$dbh) {
        $dbh = dbh();
        $dbclose = true;
    }

	if ($dbh) {
		$adgroupsStr = '{' . implode(',', $adgroups) . '}';

		// Kolla om användaren redan finns
		$checkSql = "SELECT 1 FROM {$configSchema}.adusers WHERE aduser_id = $1";
		$checkResult = pg_query_params($dbh, $checkSql, [$user]);

		if ($checkResult && pg_num_rows($checkResult) === 0) {
			$insertSql = "INSERT INTO {$configSchema}.adusers (aduser_id) VALUES ($1)";
			pg_query_params($dbh, $insertSql, [$user]);
		}

		$updateSql = "
			UPDATE {$configSchema}.adusers
			SET name = $1,
				email = $2,
				company = $3,
				department = $4,
				adgroups = $5,
				lastlogin = now()
			WHERE aduser_id = $6
		";

		$result = pg_query_params($dbh, $updateSql, [
			$name,
			$email,
			$company,
			$department,
			$adgroupsStr,
			$user
		]);

		if (!$result) {
			error_log('SQL error in initUserLdap: ' . pg_last_error($dbh));
		}

		if ($dbclose) {
			pg_close($dbh);
			$dbh = false;
		}
    }

    return true;
}