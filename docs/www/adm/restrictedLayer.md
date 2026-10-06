# Restricted layer-modul (restrictedLayer)

**Entry point:** `adm/restrictedLayer.php`
**Funktionsfiler:** `adm/functions/restrictedLayer/*.php`

## Syfte
Fungerar som en säkerhetsgateway/proxy mellan Origo-kartan och den
bakomliggande karttjänsten (WMS/WFS, t.ex. QGIS Server). Vissa kartlager
är märkta som skyddade (`RESTRICTEDLAYERS`-konstanten, med tillåtna
användare och/eller grupper per lager). Anrop som inte innehåller några
skyddade lager, eller där användaren är behörig till alla begärda lager,
vidarebefordras oförändrat till karttjänsten. Anrop till lager användaren
saknar behörighet till degraderas istället till ett "tomt" svar anpassat
efter anropstyp, så att kartan i frontend visar inget innehåll för de
lagren istället för att krascha.

Fungerar oberoende av vilket autentiseringsspår (LDAP eller Azure/
forwardauth) som satte `$_SESSION['user']` – se ARCHITECTURE.md.

## Anropas med
`restrictedLayer.php?PATH=<sökväg>&<övriga WMS/WFS-parametrar>`

Detta är **inte** ett eget API i vanlig mening – det är en transparent
proxy som vidarebefordrar godtyckliga WMS/WFS-parametrar (`SERVICE`,
`REQUEST`, `LAYERS`/`LAYER`/`TYPENAME`, `FORMAT`, `INFO_FORMAT`,
`OUTPUTFORMAT`, m.fl.) till karttjänsten, utöver den egna `PATH`-parametern
som anger vilken delsökväg på karttjänsten som ska anropas.

**Beteende per anropstyp när behörighet saknas för ett eller flera begärda lager:**

| REQUEST | Svar när obehörig |
|---|---|
| `GetCapabilities` (WMS) | Alltid obegränsat – returneras oförändrat oavsett behörighet |
| `GetLegendGraphic` | Låst hänglås-bild (`img/png/lock_yellow.png`) |
| `GetMap` | Tom/transparent bild (`img/png/empty.png`) |
| `GetFeatureInfo` | Tom GeoJSON FeatureCollection |
| Övriga | HTTP 500 "Rättigheter saknas!" |

## Loader-filer

`authorization/restrictedLayer-loader.php` gör `chdir('../adm/')` och
inkluderar `restrictedLayer.php`.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `readAndCloseSession()` – läser in sessionen
- `includeFileConstant('RESTRICTEDLAYERS')` – laddar konstanten
  `RESTRICTEDLAYERS` (lista över skyddade lager, se nedan)
- `initUserLdap()` – anropas om `$authMethod === 'ldap'`. Utan
  `$dbh`-argument öppnar funktionen själv en databasanslutning med `dbh()`

**Konstanter:**
- `constants/authMethod.php` → `$authMethod`
- `constants/restrictedServiceUrl.php` → `$restrictedServiceUrl` (bas-URL
  till den bakomliggande karttjänsten)
- `RESTRICTEDLAYERS` (via `includeFileConstant`, se `constants/`-mappen på
  toppnivå, `constants/RESTRICTEDLAYERS.php`) – array med skyddade lager,
  varje post har minst `name`, `authorized_users`, `authorized_groups`

**Session:** läser `$_SESSION['user']['id']` och `$_SESSION['user']['groups']`
(satt av antingen authorization- eller forwardauth-modulen).

**Filsystem:** läser statiska bildfiler direkt (`../img/png/lock_yellow.png`,
`../img/png/empty.png`).

**Nätverk:** gör utgående HTTP-anrop till `$restrictedServiceUrl` via
`file_get_contents()` med stream context (inte curl, till skillnad från
forwardauth-modulen).

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `authorizationFilter.php` | `authorizationFilter($layerNames): array` | Filtrerar en lista lagernamn till de som är antingen obegränsade eller där användaren är behörig. Returnerar array av lager-arrayer (inte bara namn) |
| `authorizationNamesFilter.php` | `authorizationNamesFilter($layerNames): array` | Wrapper runt `authorizationFilter()` som returnerar enbart lagernamnen |
| `fetchWithStatus.php` | `fetchWithStatus($url, $context, $maxAttempts = 2): array` | Hämtar en URL, läser ut HTTP-statuskoden, gör om anropet vid 5xx-fel (max `$maxAttempts` försök, 150ms paus mellan försök). Returnerar `['content' => ..., 'status' => ...]` |
| `finishError500.php` | `finishError500($cause)` | Avslutar requesten med HTTP 500 och texten "Rättigheter saknas!". `$cause` tas emot men används inte |
| `userAuthorized.php` | `userAuthorized($user, $restrictedLayer): bool` | Avgör om en given användare är behörig till ett specifikt skyddat lager, baserat på `authorized_users` (id-matchning) eller `authorized_groups` (någon gemensam grupp) |

## Begränsningar och risker

- `restrictedLayer.php` tolkar `QUERY_STRING` manuellt: `str_replace('&?', '&', ...)`
  och `str_replace('?', '&', ...)` hanterar Origos URL-format med extra
  frågetecken. Tolkningen beror på hur Origo bygger sina anrop.
- `$queryarray['FORMAT']`, `INFO_FORMAT`, `OUTPUTFORMAT`, `REQUEST` och
  `SERVICE` läses på flera ställen utan `isset()`-kontroll och ger
  PHP-varningar (`Undefined array key`) om parametern saknas.
- `finishError500($cause)` tar emot `$cause` men skriver alltid samma text.
- Konstantfilerna `EMPTYPNG.php` och `LOCKPNG.php` finns inte; modulen
  läser bilderna direkt med `file_get_contents()`.
- `RESTRICTEDLAYERS.php` genereras av `writeConfig.php` vid varje
  publicering via `defineFileConstant('RESTRICTEDLAYERS', ...)`, baserat på
  tjänster som är markerade `restricted` i databasen. En kopia ligger i
  `adm/tmp/`.
