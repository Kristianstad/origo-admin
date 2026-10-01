# Nyhetsmodul (news)

**Entry point:** `adm/news.php`
**Funktionsfiler:** `adm/functions/news/*.php`
**Stilmall:** `adm/styles/news.css`

## Syfte
Visar och hanterar nyheter/meddelanden för inloggade användare i adminpanelen.
Varje användare kan se en lista över nyheter, läsa en nyhet (markeras då som
läst), radera en nyhet ur sin egen vy (raderar inte för andra användare),
och kolla om det finns olästa nyheter.

Anropas via GET med parametern `action`. Nyhetslistan bäddas in som iframe
i inloggnings- och utloggningsvyerna i authorization-modulen (`news.php`
eller `news-loader.php`). Flera actions returnerar rått JSON eller
HTML-fragment utan layout.

## Anropas med
`news.php?action=<action>&newId=<id>&return=<fält>`

| action     | Kräver newId | Beskrivning | Svarsformat |
|---|---|---|---|
| `list`     | Nej | Lista med new_id för alla nyheter användaren inte raderat, sorterade nyast först | JSON-array |
| `subjects` | Nej | HTML-tabell: rubrik (abstract) + raderaknapp per nyhet, oläst fetstilad | HTML-fragment |
| `load`     | Ja | Visar/returnerar en specifik nyhet | HTML eller JSON, styrs av `return` |
| `read`     | Ja | Markerar nyheten som läst av användaren | Ingen output (redirect sker ej) |
| `delete`   | Ja | Markerar nyheten som raderad av användaren | Redirect till `?action=subjects` |
| `unread`   | Nej | Finns olästa nyheter för användaren? | `"true"` / `"false"` som text |

`return`-parametern (kommaseparerad) styr vilka fält `load` skall svara med,
t.ex. `return=text` ger en färdig HTML-sida med nyhetstexten, medan
`return=abstract,date` ger JSON med bara de fälten. `return=all` eller
tomt ger alla fält som JSON.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `readAndCloseSession()` – läser in sessionen
- `dbh()` – öppnar databasanslutning
- `initUserLdap()` – (endast om `authMethod === 'ldap'`)
- `pgArrayToPhp()` – konverterar Postgres arraysyntax (`{a,b,c}`) till PHP-array, används av `userNews()`
- `includeDirectory()` – laddar en hel mapp med require

**Konstanter:**
- `constants/authMethod.php` → `$authMethod`
- `constants/configSchema.php` → `$configSchema` (databasschema)
- `constants/proxyRoot.php` → `$proxyRoot` (används för att bygga länkar/formulär-URL:er)

**Databas:** tabellen `<configSchema>.news` med kolumner
`new_id, abstract, text, date, reads (array), deletes (array)`.

**Session:** förutsätter `$_SESSION['user']['id']` är satt (inloggning sker
i auktoriseringsmodulen, se `authorization.php`).

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `pgNewsArray.php` | `pgNewsArray(&$dbh)` | Hämtar **alla** nyheter från databasen, oavsett användare |
| `printNews.php` | `printNews($username, $selectedNew, $return)` | Skriver ut en enskild nyhet (HTML eller JSON), markerar den som läst vid textvisning |
| `printNewsList.php` | `printNewsList($userNews)` | Skriver ut JSON-array med `new_id` för alla nyheter, nyast först |
| `printNewsSubjects.php` | `printNewsSubjects($username, $userNews)` | Skriver ut HTML-tabell med rubriker + raderaknapp |
| `readDelete.php` | `readDelete($username, $selectedNew, $action)` | Lägger till användaren i `reads`- eller `deletes`-arrayen i databasen |
| `selectNew.php` | `selectNew($userNews, $newId)` | Plockar ut en enskild nyhet från listan via `new_id` |
| `testUnread.php` | `testUnread($username, $userNews)` | Returnerar `"true"`/`"false"` om det finns olästa nyheter |
| `userNews.php` | `userNews($username, $pgNewsArray)` | Filtrerar bort nyheter som användaren själv raderat; konverterar pg-arrayer till PHP-arrayer |

## Begränsningar och risker

- **SQL-injektionsrisk i `readDelete.php`:** `$newId` (från
  `$_GET['newId']` i `news.php`) byggs in direkt i SQL-strängen utan
  escaping eller parametrisering.
- `abstract` och `text` skrivs ut utan `htmlspecialchars()` i
  `printNewsSubjects.php` och `printNews.php`. Innehållet måste därför
  komma från betrodda administratörer.
- `includeDirectory("./functions/common")` laddar hela mappen, men modulen
  använder bara `readAndCloseSession`, `dbh`, `initUserLdap` och
  `pgArrayToPhp`. Andra moduler delar därför filerna i `common/`.
- Funktionsfilerna saknar `declare(strict_types=1)` och typdeklarationer.
