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

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `readAndCloseSession()` – läser in sessionen
- `includeFileConstant('RESTRICTEDLAYERS')` – laddar konstanten
  `RESTRICTEDLAYERS` (lista över skyddade lager, se nedan)
- `initUserLdap()` – anropas om `$authMethod === 'ldap'`. **⚠️ Se
  flaggning nedan** – anropas här utan `$dbh`-argument, till skillnad
  från news/authorization-modulerna

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
| `authorization_filter.php` | `authorization_filter($layerNames): array` | Filtrerar en lista lagernamn till de som är antingen obegränsade eller där användaren är behörig. Returnerar array av lager-arrayer (inte bara namn) |
| `authorization_names_filter.php` | `authorization_names_filter($layerNames): array` | Wrapper runt `authorization_filter()` som returnerar enbart lagernamnen |
| `fetchWithStatus.php` | `fetchWithStatus($url, $context, $maxAttempts = 2): array` | Hämtar en URL, läser ut HTTP-statuskoden, gör om anropet vid 5xx-fel (max `$maxAttempts` försök, 150ms paus mellan försök). Returnerar `['content' => ..., 'status' => ...]` |
| `finishError500.php` | `finishError500($cause)` | Avslutar requesten med HTTP 500 och texten "Rättigheter saknas!". **OBS:** `$cause`-parametern tas emot men används aldrig i funktionen – se flaggning |
| `userAuthorized.php` | `userAuthorized($user, $restrictedLayer): bool` | Avgör om en given användare är behörig till ett specifikt skyddat lager, baserat på `authorized_users` (id-matchning) eller `authorized_groups` (någon gemensam grupp) |

## Kända begränsningar / observationer (ej åtgärdat ännu)

- **⚠️ `initUserLdap()` anropas utan `$dbh`-argument** i
  `restrictedLayer.php` (`initUserLdap();`), medan samma funktion i
  news- och authorization-modulerna alltid anropas med `$dbh` som
  argument (`initUserLdap($dbh)`). Ingen databasanslutning öppnas alls i
  den här filen. Detta är antingen en bugg (funktionen kanske behöver
  `$dbh` internt och kommer felfunktionera/kasta fel/varning här) eller
  ett tecken på att LDAP-inloggning aldrig faktiskt used i praktiken för
  denna endpoint (t.ex. om `restrictedLayer.php` bara anropas efter att
  användaren redan loggat in på annat håll och sessionen redan är
  komplett). **Bör verifieras när vi dokumenterar `initUserLdap.php`** i
  common – om funktionen kräver `$dbh` är detta en riktig bugg värd att
  fixa.
- **⚠️ Manuell query-string-parsning är skör:**
  `str_replace('&?', '&', ...)` och `str_replace('?', '&', ...)` för att
  hantera Origos sätt att bygga URL:er (troligen dubbla frågetecken från
  hopslagna URL:er) är svårläst och känsligt för framtida ändringar i
  hur Origo bygger sina anrop. Värt att dokumentera *varför* detta behövs
  (vilket exakt URL-format från Origo som orsakar problemet) om
  informationen går att få tag på, annars risk att någon råkar "städa
  bort" det här vid framtida refaktorering utan att förstå varför det
  finns.
- **`$queryarray['FORMAT']`/`INFO_FORMAT`/`OUTPUTFORMAT`/`REQUEST`/`SERVICE`
  läses utan `isset()`-kontroll** på flera ställen (t.ex.
  `$queryarray['OUTPUTFORMAT'] === 'geojson'`,
  `header('Content-Type: '.$queryarray['FORMAT'])`), vilket genererar
  PHP-varningar (`Undefined array key`) om parametern saknas i anropet.
  Fungerar sannolikt i praktiken eftersom Origo alltid skickar dessa,
  men är skört mot förändringar i anropande kod och bör städas med
  `??`-null-coalescing vid refaktorering.
- **`finishError500($cause)` använder aldrig `$cause`** – parametern
  skickas in (`finishError500('saknar rättigheter')`) men funktionen
  skriver alltid samma hårdkodade text. Antingen bör `$cause` användas i
  utskriften (för bättre felsökning) eller parametern tas bort för att
  inte vilseleda läsaren.
- **Ologiskt namn på filen `authorization_filter.php` vs `functions/authorization/`-mappen** (den andra auktoriseringsmodulen) – de har inget med varandra att göra men liknande namn (`authorization_filter` vs `functions/authorization/`), vilket kan skapa förvirring vid sökning i kodbasen. Ingen brådskande åtgärd, men värt att notera terminologimässigt: kanske byt namn till `restrictedLayerAuthorizationFilter` eller liknande vid framtida refaktorering, för att tydligare skilja från LDAP/Azure-auktorisering.
- **Blandade engelska/svenska funktionsnamn och kommentarer** rakt igenom
  filen (`callLayers`, `unrestricted` vs `'saknar rättigheter'`) – i linje
  med resten av kodbasen, ingen ny observation men konsekvent mönster.
- Ingen `strict_types` eller parametertypning i någon av filerna.
- **Ingen `pg_close()` eller liknande cleanup synlig** i denna fil till
  skillnad från news/mapstate/info – rimligt eftersom ingen `dbh()`
  öppnas här (se punkten om `initUserLdap()` ovan), men värt att bekräfta
  att ingen databasanslutning öppnas dolt inuti någon av de anropade
  funktionerna.
- **⚠️ Möjlig oanvänd konstant:** `constants/EMPTYPNG.php` och
  `constants/LOCKPNG.php` (toppnivå, utanför `adm/`) har namn som
  antyder att de är tänkta att användas av just den här modulen, men
  `restrictedLayer.php` läser istället motsvarande bilder direkt från
  disk via `file_get_contents()`. Oklart om konstanterna är kvarlämnad,
  oanvänd kod, eller används av en annan del av systemet vi inte
  dokumenterat än (t.ex. en annan loader). Följs upp vid loader-genomgången.
- **✅ Löst tidigare öppen fråga:** `constants/RESTRICTEDLAYERS.php`
  genereras av `writeConfig.php` (se writeConfig.md), som vid varje
  publicering av en karta bygger om listan över skyddade lager baserat
  på vilka tjänster som är markerade `restricted` i databasen, och
  skriver den till fil via `defineFileConstant('RESTRICTEDLAYERS', ...)`.
  Det förklarar även varför en kopia finns i `adm/tmp/` – sannolikt en
  mellanlagringsplats innan filen flyttas/skrivs till sin slutgiltiga
  plats (bör bekräftas när `publishMapFiles.php`/`saveFile.php` granskats).
