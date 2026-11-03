# Restricted layer-modul (restrictedLayer)

**Entry point:** `adm/restrictedLayer.php`
**Funktionsfiler:** `adm/functions/restrictedLayer/*.php`

## Syfte
Fungerar som en säkerhetsgateway/proxy mellan Origo-kartan och den
bakomliggande karttjänsten (WMS/WFS, t.ex. QGIS Server). Vissa kartlager
är märkta som skyddade (restricted-cachen innehåller tillåtna användare
och grupper per lager). Anrop som inte innehåller några
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
- `restrictedCacheLoad()` – läser restricted-cachen eller bygger den vid första anropet
- `initUserLdap()` – anropas om `$authMethod === 'ldap'`. Utan
  `$dbh`-argument öppnar funktionen själv en databasanslutning med `dbh()`

**Konstanter:**
- `constants/authMethod.php` → `$authMethod`
- `constants/restrictedServiceUrl.php` → `$restrictedServiceUrl` (bas-URL
  till den bakomliggande karttjänsten)
**Session:** läser `$_SESSION['user']['id']` och `$_SESSION['user']['groups']`
(satt av antingen authorization- eller forwardauth-modulen).

**Filsystem:** läser statiska bildfiler direkt (`../img/png/lock_yellow.png`,
`../img/png/empty.png`). Restricted-cachen ligger utanför webbträdet i
`sys_get_temp_dir()/origo-cache/`; `restricted.current` pekar på en
versionsfil `restricted.<token>.php`. Nya versioner skrivs färdigt innan
pekaren byts, så befintliga läsare kan fortsätta använda föregående fil.

**Nätverk:** gör utgående HTTP-anrop till `$restrictedServiceUrl` via
`file_get_contents()` med stream context (inte curl, till skillnad från
forwardauth-modulen).

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `authorizationFilter.php` | `authorizationFilter($layerNames, $restrictedLayers): array` | Filtrerar en lista lagernamn till de som är antingen obegränsade eller där användaren är behörig. Returnerar array av lager-arrayer (inte bara namn) |
| `authorizationNamesFilter.php` | `authorizationNamesFilter($layerNames, $restrictedLayers): array` | Wrapper runt `authorizationFilter()` som returnerar enbart lagernamnen |
| `fetchWithStatus.php` | `fetchWithStatus($url, $context, $maxAttempts = 2): array` | Hämtar en URL, läser ut HTTP-statuskoden, gör om anropet vid 5xx-fel (max `$maxAttempts` försök, 150ms paus mellan försök). Returnerar `['content' => ..., 'status' => ...]` |
| `finishError500.php` | `finishError500($cause)` | Avslutar requesten med HTTP 500 och texten "Rättigheter saknas!". `$cause` tas emot men används inte |
| `userAuthorized.php` | `userAuthorized($user, $restrictedLayer): bool` | Avgör om en given användare är behörig till ett specifikt skyddat lager, baserat på `authorized_users` (id-matchning) eller `authorized_groups` (någon gemensam grupp) |

## Begränsningar och risker

- `restrictedLayer.php` tolkar `QUERY_STRING` manuellt: `str_replace('&?', '&', ...)`
  och `str_replace('?', '&', ...)` hanterar Origos URL-format med extra
  frågetecken. Tolkningen beror på hur Origo bygger sina anrop.
- `$queryarray['PATH']` läses utan `isset()`-kontroll och ger en PHP-varning
  (`Undefined array key`) om parametern saknas. `SERVICE` och `REQUEST` får
  tomma standardvärden. `Content-Type` sätts från `INFO_FORMAT` eller
  `FORMAT`; saknas båda (till exempel WFS-anrop med bara `OUTPUTFORMAT`)
  används `text/xml; charset=utf-8`. `OUTPUTFORMAT=geojson` jämförs utan
  hänsyn till skiftläge.
- `finishError500($cause)` tar emot `$cause` men skriver alltid samma text.
- Konstantfilerna `EMPTYPNG.php` och `LOCKPNG.php` finns inte; modulen
  läser bilderna direkt med `file_get_contents()`.
## Restricted-cache

Cacheinnehållet byggs från `layers`, `sources` och `services`: icke-GROUP-lager vars källa pekar på en tjänst med `restricted = 't'` tas med, tillsammans med normaliserade användar- och gruppnamn. Gruppjämförelsen är UTF-8-skiftlägesokänslig, eftersom LDAP och Azure kan leverera samma gruppnamn med olika skiftläge. Cachefilen skrivs atomiskt under fillås i `sys_get_temp_dir()/origo-cache/` och ligger inte i webbträdet.

`manage.php` bygger om cachen efter lyckade ändringar i `layers`, `sources` eller `services`, inklusive create/update/copy/delete, undo/redo och restore. Kartpublicering i `writeConfig.php` påverkar inte cachen. JSON- och SQL-import, samt ändringar direkt i databasen, kräver manuell ombyggnad via **Bygg om restricted-cache** i Verktyg.

Vid misslyckad ombyggnad lämnas en befintlig cache orörd och fortsätter användas. Om cache saknas försöker första proxyanropet bygga den; om det misslyckas svarar proxyn HTTP 503 i stället för att behandla lagren som obegränsade.
