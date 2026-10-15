<?php
require_once('../../composer/adldap2/autoload.php');

function login(&$dbh)
{
    require './constants/adldapConfig.php';
    require './constants/adDomain.php';
    require './constants/cookieConfig.php';
    require './constants/authMethod.php';
    require './constants/proxyRoot.php';

    // Se till att sessionen är skrivbar
    ensureSessionWritable();

    $user   = strtolower(trim($_POST['user'] ?? ''));
    $passwd = $_POST['passwd'] ?? '';

    $authUser = false;

    if ($user !== '' && $passwd !== '') {
        try {
            $ad = new Adldap\Adldap();
            $ad->addProvider($adldapConfig);
            $provider = $ad->connect();
            $authUser = $provider->auth()->attempt("$user@$adDomain", $passwd);
        } catch (Exception $e) {
            error_log('LDAP auth error: ' . $e->getMessage());
            $authUser = false;
        }
    }

    // Nolla lösenordet så fort det inte behövs
    $passwd = null;
    unset($passwd);

    if ($authUser) {
        // --- Skapa krypterad cookie ---
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($user, 'aes-256-cbc', $cookieConfig['cookieKey'], 0, $iv);
        $cookiestr = base64_encode($encrypted . '::' . $iv);

        setcookie(
            $cookieConfig['cookieName'],
            $cookiestr,
            getCookieOptions(time() + $cookieConfig['cookieLifetime'])
        );

        // Rensa eventuell gammal session-data och initiera användaren
        unset($_SESSION['user']);

        if ($authMethod === 'ldap') {
            initUserLdap($dbh);   // skriver + stänger sessionen
        }

        // --- Säker hantering av return_to ---
        $return_to = $_POST['return_to'] ?? $_GET['return_to'] ?? '';
        if ($return_to !== '' && isSafeReturnUrl($return_to)) {
			
            // Sessionen är redan stängd av initUserLdap(), men vi tar det säkra före det osäkra
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
			
            // Stäng db-kopplingen om den finns
            if (isset($dbh) && $dbh) {
                pg_close($dbh);
                $dbh = false;
            }
	
            header('Location: ' . $return_to);
            exit;
        }

        // --- Visa inloggad-vy ---
        // Observera: även om sessionen är stängd finns datan kvar i $_SESSION i denna request
        $formAction = $proxyRoot . $_SERVER['PHP_SELF'];
        $src = (basename($formAction) === 'authorization-loader.php')
            ? dirname($formAction) . '/news-loader.php'
            : './news.php';

        $userId = htmlspecialchars($_SESSION['user']['id'] ?? '', ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<script>
sessionStorage.user_id = "{$userId}";
</script>
<b style="color:#023f88">Du är nu inloggad!</b>
<button id="loginbtn"
        style="cursor:pointer;background:#eee;border-radius:1rem;border:#eee;width:auto;text-align:center;white-space:nowrap;padding:0.5rem 0.75rem;font:14px Segoe UI,Roboto,Helvetica Neue,Arial,sans-serif;"
        type="button"
        onclick="sessionStorage.removeItem('user_id'); document.location.assign('{$formAction}?logout');">
    Logga ut
</button>
<br>
<iframe src="{$src}?action=subjects"
        style="border:none;width:100%;height:115px;margin-top:5px;margin-bottom:10px"></iframe>
HTML;

    } else {
        echo '<b style="color:#ff0000">Felaktig inloggning!</b><br>';
        displayLogin();
    }
}