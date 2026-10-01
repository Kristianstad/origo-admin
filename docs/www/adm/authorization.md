# Auktoriseringsmodul (authorization)

**Entry point:** `adm/authorization.php`
**Iframe-wrapper:** `adm/authorization-iframe.php`
**Funktionsfiler:** `adm/functions/authorization/*.php`
**Stilmall:** `adm/styles/authorization.css`
**Extern startpunkt (dokumenteras separat):** `authorization/authorization-loader.php`,
`authorization/authorization-iframe.php` (toppnivå, utanför adm – se separat loader-genomgång)

## Syfte
Hanterar inloggning och utloggning för adminpanelen mot Active Directory
via LDAP (biblioteket `adldap2`). Sätter dels en PHP-session
(`$_SESSION['user']`), dels en egen krypterad cookie som identifierar
användaren mellan sessioner. Visar antingen ett inloggningsformulär eller,
om användaren redan är inloggad, en "inloggad"-vy med utloggningsknapp och
en inbäddad nyhetslista (`news.php?action=subjects`, se news.md).

Stöder en `return_to`-parameter för att skicka användaren till en annan
sida efter lyckad inloggning, skyddad mot open redirect via
`isSafeReturnUrl()`.

## Anropas med
`authorization.php` (GET för att visa formulär, POST för att logga in)

| Parameter | Metod | Beskrivning |
|---|---|---|
| `logout` | GET (finns) | Loggar ut användaren direkt |
| `displaylogout` | GET (finns) | Tvingar fram "inloggad"-vyn (utloggningsknapp) |
| `call` | GET/POST | Skickas tyst genom till inloggningsformuläret (dolt fält), okänt exakt syfte ännu – flaggat nedan |
| `return_to` | GET/POST | URL att skicka användaren till efter lyckad inloggning. Valideras mot open redirect |
| `user`, `passwd` | POST | Inloggningsuppgifter (skickas endast via POST, aldrig GET) |

Route-logiken (i tur och ordning): logout → visa "inloggad"-vy (om
inloggad utan SERVICE-param) → hantera POST som inloggningsförsök → visa
inloggningsformulär.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()` – databasanslutning (endast vid LDAP-metod)
- `initUserLdap($dbh)` – hämtar/initierar användarens LDAP-data,
  **skriver och stänger sessionen som en bieffekt** (se `login.php`s
  kommentar `// skriver + stänger sessionen`) – viktigt att känna till
  vid felsökning av sessionsrelaterade buggar
- `ensureSessionWritable()` – öppnar sessionen igen med applikationens
  cookie-inställningar om den är stängd (till exempel efter `read_and_close`)
- `getCookieOptions($expiryTimestamp)` – bygger array med cookie-inställningar
  (path, domain, secure, httponly, samesite) givet ett utgångsdatum

**Konstanter:**
- `constants/authMethod.php` → `$authMethod`
- `constants/proxyRoot.php` → `$proxyRoot`
- `constants/adldapConfig.php` → `$adldapConfig` (LDAP-anslutningsinställningar)
- `constants/adDomain.php` → `$adDomain`
- `constants/cookieConfig.php` → `$cookieConfig` (cookienamn, krypteringsnyckel, livslängd)

**Externt bibliotek:** `adldap2` (LDAP-klient för PHP), laddas via
`../../composer/adldap2/autoload.php` – en composer-installerad
tredjepartsberoende utanför `adm/`. **OBS:** denna `require_once` ligger
på toppnivå i `login.php` (inte inuti en `if`), vilket betyder att
autoloadern alltid laddas så fort `functions/authorization/` inkluderas
via `includeDirectory()` — även när `$authMethod` inte är `'ldap'`. Se
begränsningar nedan.

**Session:** sätter `$_SESSION['user']` vid lyckad inloggning (exakt
struktur sätts av `initUserLdap()`, se common.md).

**Cookies:** sätter en egen krypterad cookie (`$cookieConfig['cookieName']`)
utöver PHP:s sessionscookie, för att identifiera användaren mellan
sessioner (AES-256-CBC-krypterat användarnamn).

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `displayHtmlFooter.php` | `displayHtmlFooter()` | Skriver ut avslutande `</body></html>` |
| `displayHtmlHeader.php` | `displayHtmlHeader()` | Skriver ut `<html><head>` inklusive `authorization.css` |
| `displayLogin.php` | `displayLogin()` | Visar HTML-inloggningsformulär (användarnamn + lösenord) |
| `displayLogout.php` | `displayLogout()` | Visar "inloggad"-vy med utloggningsknapp och inbäddad nyhetslista |
| `displayWithHtml.php` | `displayWithHtml($content)` | Wrapper: header + valfritt innehåll + footer |
| `isSafeReturnUrl.php` | `isSafeReturnUrl(string $url): bool` | Whitelist-kontroll mot open redirect – tillåter relativa URL:er eller samma host som `HTTP_HOST` |
| `login.php` | `login(&$dbh)` | Autentiserar mot LDAP, sätter krypterad cookie, initierar session, redirectar till `return_to` eller visar "inloggad"-vy |
| `logout.php` | `logout()` | Förstör session och cookies (session, autentiseringscookie, refresh-cookie), visar inloggningsformulär igen |

## Begränsningar och risker

- Cookien byggs i `login.php` som `base64_encode($encrypted . '::' . $iv)`.
  `openssl_encrypt()` med `options=0` ger base64-text som inte kan innehålla
  `::`, och `initUserLdap()` delar upp värdet med
  `explode('::', $decoded, 2)`. IV:n (binär) kan därför innehålla vilka
  bytes som helst.
- `login.php` och `initUserLdap.php` kräver `../../composer/adldap2/autoload.php`
  med `require_once` på toppnivå, så biblioteket läses in när
  `functions/authorization` eller `functions/common` inkluderas, oavsett
  `$authMethod`. Saknas composer-biblioteket orsakar det ett fatalt fel.
- Villkoret `$authMethod === 'ldap'` i `displayLogout.php` är aktivt:
  utloggningsknappen byggs bara för LDAP-metoden.
- `$_GET['call']` skickas (escapad) vidare som dolt fält i formuläret;
  modulen läser den inte.
- `login.php` och `displayLogout.php` avgör news-iframens URL med samma
  logik (`basename($formAction) === 'authorization-loader.php'`), så modulen
  beter sig olika beroende på om den anropas via `authorization-loader.php`
  (utanför `adm/`).
- Användarens lösenord nollas explicit (`$passwd = null; unset($passwd);`)
  direkt efter användning.
- `isSafeReturnUrl()` används i både `displayLogin()` och `login()`.
