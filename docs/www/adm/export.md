# Export-modul (export)

**Entry point:** `adm/export.php`
**Funktionsfiler:** `adm/functions/export/*.php`
**Inställningar:** `adm/constants/exportConfig.php`, `adm/constants/exportFormats.php`, `adm/constants/fmeToken.php`
**Stilmall:** `adm/styles/export.css`

Modulen är beroende av en FME Flow-instans och ett delat filsystem mellan
PHP och FME. Den ingår i repot, men måste konfigureras för varje
servermiljö. `constants/fmeToken.php` innehåller en placeholder i repot;
ersätt den i driftsmiljön med en hemlig token och committa aldrig det
verkliga tokenvärdet.
Servernamn, kataloger, FME-arbetsflöden och exportformat är samlade i
`exportConfig.php` och `exportFormats.php` så att modulen kan anpassas
till en annan serveruppsättning utan att funktionsfilerna ändras (se
"Anpassa till en annan miljö").

## Syfte
Låter en inloggad användare beställa export av ett kartutsnitt (angivet
som rektangel eller polygon, ritad i Origo-kartan) till valfritt av
flera filformat (DXF, Shapefile, GeoJSON, TIFF/bakgrundskarta) eller
specialrapporter (intresseyta, höjddata/NNH, stompunkter). Exporten är
**asynkron**: sidan svarar omedelbart till användaren att exporten är
på gång (`fastcgi_finish_request()`), fortsätter sedan köra i
bakgrunden, hämtar data från bakomliggande WMS/WFS-tjänster, och
lämnar över den faktiska formatkonverteringen och filleveransen till en
extern **FME Flow**, som mejlar resultatet när det är klart.

## Flöde
`export.php` gör kontroller och svarar; själva exporten ligger i
`runExportJob()`.

1. `export.php`: inloggning (`readAndCloseSession()`, `initUserLdap()` vid
   `ldap`), giltig FME-token, giltigt värdnamn (`exportBaseUrl()`). Fel ger
   en fullständig sida med rätt HTTP-status via `exitWithExportMessage()`.
2. `printExportPage()` skickar svaret; `fastcgi_finish_request()` avslutar
   anslutningen till användaren (hoppas över vid felsökning).
3. `runExportJob()`:
   1. `exportRequest()` validerar parametrarna mot `$exportFormats` och räknar
      ut bounding box, bredd och höjd (`exportBoundingBox()`).
   2. Tabellerna läses med `allFromTable()` och databasanslutningen stängs.
   3. `createExportJob()` skapar jobbkatalogen och `registerExportCleanup()`
      ser till att den tas bort när skriptet slutar.
   4. `writeExportDrawings()` skriver ritade lager från request-body.
   5. `exportLayerRequests()` ger tjänst-URL och bildformat per lager,
      `exportTargets()` listar vad som ska hämtas för formatet och
      `fetchExportData()` hämtar det med `fetchDxf/Shape/Geojson/Wms()`.
   6. `exportFmeParameters()` bygger parametrarna till FME-arbetsflödet och
      `submitFmeJob()` kör det.

Problem efter att svaret skickats når inte användaren; de loggas med
`error_log()`.

## Anropas med
`export.php?<parametrar>` (GET för parametrar, kroppen kan även
innehålla JSON-data via POST body för vissa fält)

| Parameter | Beskrivning |
|---|---|
| `B` | Bounding box som rektangel: `minX,minY,maxX,maxY` |
| `BP` | Alternativ till `B`: polygon som punktlista, används för att räkna ut en omslutande rektangel |
| `map` | Id för kartan exporten gäller |
| `layers` | Semikolon-separerad lista med lagernamn att exportera. Bara namn som finns i kartans lager (inklusive undergrupper) används |
| `F` | Exportformat, en nyckel i `$exportFormats`. Okänt format avbryter exporten |
| `C` | Koordinatsystem (SRS), standard `defaultSrs` i `$exportConfig` (`EPSG:3008`) |
| `SF` | (endast `bas_dxf`) valfritt filnamn för resultatet; endast filnamnsdelen används, standard `baseMap.fileName` |
| `V` | (formatets `parameters`, i dag endast `intresse`) skickas vidare till FME |
| JSON body `J`, `RN` | Ritade lagerobjekt (GeoJSON per ritlager, avgränsade med `@`) och deras namn (avgränsade med `,`). Antalet måste stämma; annars loggas ett fel och ritdata utelämnas. Används bara av format med `drawings` satt |

Utsnittets längsta sida är `longestSide` bildpunkter (2000). Bounding
boxen justeras så att proportionerna följer bredd och höjd. Saknas eller är
`B`/`BP` ogiltig loggas det och exporten avbryts utan fel mot användaren.

**Svar:** ett kort textmeddelande (`"Du får ett epostmeddelande när
exporten är färdig."`) — det faktiska resultatet skickas senare via
e-post från FME Flow, inte via detta HTTP-svar. Ej inloggad ger
`Ej inloggad!`. Om `constants/fmeToken.php` saknar giltig token (tom eller
placeholdern `REPLACE_WITH_FME_FLOW_TOKEN`) svarar modulen `503`, och ogiltigt
värdnamn ger `400`.

## Inställningar

### `constants/exportConfig.php` → `$exportConfig`

| Nyckel | Betydelse |
|---|---|
| `tempRoot` | Katalog där PHP skriver hämtad data (`<tempRoot>/<user_id>/<jobbid>/`). FME måste kunna läsa samma katalog |
| `fmeResourceRoot` | Samma katalog som FME ser den. Läggs ihop direkt med användar-id, så ta med avslutande separator om FME behöver en |
| `fmeJobUrl` | FME Flows REST-adress för synkron körning (`POST`) |
| `fmeRepository` | FME-repository som innehåller arbetsflödena i `$exportFormats` |
| `baseMap` | Bakgrundskartan för format med källa `baseMap`: `path` (läggs efter värdnamnet), `layer`, `fileName` (standardnamn) och `imageFormat` |
| `defaultSrs` | Koordinatsystem när `C` saknas |
| `longestSide` | Bildpunkter för exportbildens längsta sida |
| `cleanupDelay` | Sekunder att vänta efter lyckat FME-anrop innan filerna tas bort |

### `constants/exportFormats.php` → `$exportFormats`
En post per värde på `F`:

| Nyckel | Betydelse |
|---|---|
| `source` | `layers` (begärda lager), `baseMap` (bakgrundskartan i `exportConfig`) eller `none` (inget hämtas) |
| `fetch` | Hur data hämtas: `dxf`, `shape`, `geojson`, `wms` eller `null` |
| `drawings` | Om ritade lager ur request-body skickas vidare till FME |
| `workspace` | FME-arbetsflödet (`.fmw`) som körs |
| `output` | FME-parametern `D`. `{userRoot}`, `{jobRoot}`, `{id}` och `{V}` ersätts |
| `srs` | Om koordinatsystemet skickas vidare (parameter `C`) |
| `format` | Värde för FME-parametern `FF`, eller `null` |
| `parameters` | Request-parametrar som skickas oförändrade till FME |
| `inputs` | FME-parametrar som listar de hämtade filerna: `parameter => {pattern, when}`. `when` begränsar parametern till jobb som hämtat det bildformatet. Hittas inga filer loggas ett fel och exporten avbryts |

Dagens format och arbetsflöden:

| Format (`F`) | Källa | Arbetsflöde |
|---|---|---|
| `bas_dxf` | bakgrundskarta, DXF | `tl_dxf.fmw` |
| `bas_tiff` | bakgrundskarta, WMS | `raster.fmw` |
| `tl_bak` | lager, WMS | `raster.fmw` |
| `tl_dxf` | lager, DXF | `tl_dxf.fmw` |
| `tl_shape` | lager, WFS Shape | `tl_shape.fmw` |
| `tl_geojson` | lager, WFS GeoJSON | `tl_geojson.fmw` |
| `intresse` | ingen hämtning | `intresseyta.fmw` |
| `nnh_csv` | ingen hämtning | `nnh2csv.fmw` |
| `stmp_csv` | ingen hämtning | `stompunkt2csv.fmw` |

### Övriga konstanter
- `constants/authMethod.php` → `$authMethod`
- `constants/configSchema.php` → `$configSchema`
- `constants/fmeToken.php` → `$fmeToken`, FME-token som sträng. Filen är
  versionshanterad med placeholdern `REPLACE_WITH_FME_FLOW_TOKEN`, som
  `export.php` avvisar. Sätt det verkliga värdet säkert i driftsmiljön;
  committa inte en riktig token
- `constants/proxyRoot.php` → `$proxyRoot`, ersätter `../` i tjänsternas
  `base_url` (till exempel `/origo`)
- `constants/trustedDomain.php` → `$trustedDomain`, läses inuti
  `exportBaseUrl()`. Om den är satt godtas bara den domänen eller en
  underdomän; är den tom (standard) godtas varje välformat värdnamn

## Anpassa till en annan miljö
1. Sätt `tempRoot`, `fmeResourceRoot`, `fmeJobUrl` och `fmeRepository` i
   `exportConfig.php`, och se till att PHP och FME delar katalogen.
2. Ange bakgrundskartans `path`, `layer`, `fileName` och `imageFormat`.
3. Namnge dina egna FME-arbetsflöden i `exportFormats.php`. Ta bort format
   du inte använder, eller lägg till nya poster; funktionsfilerna behöver
   inte ändras så länge formatet använder befintliga `fetch`-sätt.
4. Sätt `$trustedDomain` och `$proxyRoot` så att de stämmer med hur
   karttjänsterna nås, och lägg FME-token i `constants/fmeToken.php`.

## Beror på
**Common-funktioner** (`adm/functions/common/`, plus `adm/functions/includeDirectory.php`):
- `allFromTable()`, `arrayColumnSearch()`, `pgArrayToPhp()` – hämtar
  och slår upp maps/groups/layers/sources/services
- `dbh()`, `initUserLdap($dbh)` (bara om `$authMethod === 'ldap'`),
  `readAndCloseSession()`, `isHostInDomain()` – standardstart och värdnamnskontroll

**Databas:** läser `maps`, `groups`, `layers`, `sources`, `services`.

**Filsystem:** skapar temporära arbetskataloger under
`<tempRoot>/<user_id>/<jobbid>/` (behörighet `0777`) och skriver hämtad
kartdata och ritlager (`ritlager_<lager>.json`) dit. En shutdown-hanterare
(`registerExportCleanup()`) tar bort katalogen även vid fel och tidiga
`exit`. Efter lyckat FME-anrop väntar den `cleanupDelay` sekunder så att FME
hinner läsa filerna, tar bort `export-<jobbid>.zip` i användarens katalog om
den finns och tar sedan bort tempkatalogen. Sätt
`$keepTempFilesForDebugging = true` i `export.php` för att tillfälligt
behålla katalogen och hoppa över `fastcgi_finish_request()` vid felsökning.
Då skickas svaret först när PHP-skriptet avslutas och PHP-loggen kan
samlas in.

**Session:** anrop till karttjänsterna (WMS/WFS, inklusive skyddade lager via
`restrictedLayer`) skickar användarens egen `PHPSESSID` som cookie
(`session_id()`), så att skyddade lager kontrolleras mot användarens
behörighet. Anropen måste gå mot samma adress som användaren nådde exporten
via (se `exportBaseUrl()`), eftersom en annan adress kan ha en annan loader
i sin proxykonfiguration.

**Externa tjänster:**
- QGIS Server (eller annan WMS/WFS) på samma adress som användaren nådde
  exporten via: `<exportBaseUrl><baseMap.path>` för bakgrundskartan och
  `<exportBaseUrl><baseUrl>/<sourceName>` för övriga lager (`baseUrl` är
  tjänstens `base_url` från `services` där `../` ersatts med `$proxyRoot`
  och ett snedstreck). `<exportBaseUrl>` är `https://` plus värdnamnet från
  `X-Forwarded-Host` eller `Host`
- **FME Flow** REST API: `POST <fmeJobUrl>` med JSON
  `{repository, workspace, publishedParameters}`, byggd med `json_encode()` och
  autentiserad med `Authorization: fmetoken token=<$fmeToken>`.
  TLS-certifikatet verifieras

## Funktioner

| Fil | Funktion | Beskrivning |
|---|---|---|
| `allLayerIds.php` | `allLayerIds($mapOrGroup, $groups, $allLayerIds=[], $visitedGroupIds=[])` | Samlar rekursivt ihop alla lager-id:n för en karta, inklusive lager i undergrupper. Hoppar över redan besökta grupper, så att cykler avslutas |
| `commonCurlSetopt.php` | `commonCurlSetopt(CurlHandle $curl)` | Delade curl-inställningar för fetch-funktionerna: timeout, TLS-verifiering, IPv4, DNS-cache, samt aktuell PHP-sessions-cookie |
| `createExportJob.php` | `createExportJob(array $config, string $userId): array` | Skapar jobbkatalogen och ger jobbet: `id`, `directory`, `archive` (som PHP ser dem) samt `fmeUserRoot` och `fmeJobRoot` (som FME ser dem) |
| `exitWithExportMessage.php` | `exitWithExportMessage($dbh, int $status, string $messageHtml): never` | Stänger databasanslutningen, svarar med meddelandet som en sida med angiven HTTP-status och avslutar |
| `exportBaseUrl.php` | `exportBaseUrl(): string` | Ger `https://<värdnamn>` från `X-Forwarded-Host` (första värdet) eller `Host`, med valfritt portnummer. Värdnamnet måste vara välformat och, om `$trustedDomain` är satt, vara den domänen eller en underdomän; annars tom sträng |
| `exportBoundingBox.php` | `exportBoundingBox(string $coordinates, bool $isPolygon, int $longestSide): ?array` | Räknar ut bounding box justerad efter bildens proportioner samt bredd och höjd; `null` vid ogiltiga koordinater |
| `exportFileName.php` | `exportFileName($name, string $default): string` | Tar bort katalogdelar ur ett filnamn från requesten; ger `$default` om inget återstår |
| `exportFmeFileList.php` | `exportFmeFileList(string $directory, string $fmeJobRoot, string $pattern): array` | Listar filer i jobbkatalogen som matchar ett mönster, med sökvägar som FME ser dem |
| `exportFmeParameters.php` | `exportFmeParameters(array $format, array $request, array $job, array $imageFormats, bool $hasDrawings, string $mail): array` | Bygger de publicerade parametrarna till FME-arbetsflödet enligt formatet |
| `exportLayerRequests.php` | `exportLayerRequests(array $layerNames, array $allLayerIds, array $tables, string $exportBaseUrl, string $proxyRoot): array` | Ger en post (`layer`, `url`, `format`) per exporterbart lager. Ett lager som listar andra lager i kolumnen `exports` ersätts av dem |
| `exportRequest.php` | `exportRequest(array $query, array $config, array $formats): ?array` | Validerar parametrarna; ger den validerade requesten eller `null` efter loggning |
| `exportRequestBody.php` | `exportRequestBody(): array` | Request-body avkodad som JSON, eller tom array |
| `exportResponseSnippet.php` | `exportResponseSnippet($response): string` | De första 200 tecknen av ett svar, med icke-utskrivbara tecken utbytta mot mellanslag, för felloggning |
| `exportTargets.php` | `exportTargets(array $format, array $request, array $layerRequests, array $config, string $exportBaseUrl): array` | Listar vad som ska hämtas: lager, URL, format och filnamn (utan ändelse) |
| `fetchDxf.php` | `fetchDxf($serveraddr, $layers, $bbox, $srs, $savefile)` | Hämtar en DXF-export (WMS GetMap med DXF-format). Sparar bara svar som innehåller `DXF created` |
| `fetchExportData.php` | `fetchExportData(array $format, array $targets, array $request, string $directory): array` | Hämtar alla mål till jobbkatalogen; ger de bildformat som hämtades |
| `fetchGeojson.php` | `fetchGeojson($serveraddr, $layers, $bbox, $srs, $savefile)` | Hämtar features som GeoJSON (WFS GetFeature) |
| `fetchShape.php` | `fetchShape($serveraddr, $layers, $bbox, $srs, $savefile)` | Hämtar features som Shapefile (WFS GetFeature) |
| `fetchWms.php` | `fetchWms($serveraddr, $layers, $format, $bbox, $srs, $width, $height, $savefile)` | Hämtar en bildkarta (WMS GetMap) och sparar till `<savefile>.<format-suffix>` |
| `printExportPage.php` | `printExportPage(string $messageHtml)` | Skriver ut ett meddelande som en komplett sida med exportens stilmall |
| `registerExportCleanup.php` | `registerExportCleanup(array $job, int $delay, bool $keepFiles, array &$state)` | Tar bort jobbkatalogen när skriptet slutar; efter lyckat FME-jobb väntar den först och tar bort zip-filen |
| `removeDirectory.php` | `removeDirectory(string $directory): bool` | Tar bort en katalog rekursivt. Följer inte symboliska länkar och returnerar `false` vid fel |
| `runExportJob.php` | `runExportJob($dbh, array $user, array $query, array $body, string $exportBaseUrl, string $fmeToken, bool $keepFiles)` | Hela exportjobbet efter att svaret skickats (se Flöde) |
| `submitFmeJob.php` | `submitFmeJob(array $config, string $token, string $workspace, array $parameters): bool` | Kör arbetsflödet på FME Flow och väntar på resultatet. Fel loggas med parametervärdena bortplockade ur FME:s svar |
| `writeExportDrawings.php` | `writeExportDrawings(array $body, array $layerNames, array $allLayerIds, string $directory): bool` | Skriver begärda ritade lager till jobbkatalogen som `ritlager_<namn>.json`; `true` om minst en fil skrevs |

Alla fetch-funktioner loggar curl-fel, HTTP-status utanför 2xx, anropets URL och de första 200 tecknen av svaret med `error_log()`, så att anropet kan provas i en webbläsare. De loggar också misslyckad filskrivning; ingen av dem rapporterar fel till anroparen. URL:en innehåller inte sessionen, som skickas som cookie.

## Begränsningar och risker

- Tokenen i `constants/fmeToken.php` ger åtkomst till FME Flow och ligger i
  klartext. Filen måste hållas utanför Git.
- Fel efter att svaret skickats (`fastcgi_finish_request()`) når inte
  användaren; de loggas bara med `error_log()`. Användaren får därför
  meddelandet om e-post även om exporten misslyckas.
- `$keepTempFilesForDebugging = true` lämnar tempkatalogen och eventuell FME-ZIP kvar avsiktligt.
- `sleep($cleanupDelay)` efter lyckat FME-anrop blockerar PHP-processen efter att
  svaret skickats, så att FME hinner läsa filerna innan ZIP och tempkatalog tas
  bort. Det är en fast väntetid, inte en synkronisering; om FME skapar
  ZIP-filen senare finns den kvar.
- `exportRequest()` validerar format och bounding box. `map`, `layers` och
  `C` kontrolleras inte mer än att de är strängar; `B`/`BP` och `C` ingår i
  URL:er till karttjänsterna och i FME-parametrar, och `V` ingår i en sökväg i
  FME-parametern `D`.
- Lagernamn och bounding box läggs in i fristående frågesträngar utan
  `urlencode()`.
- Tempkataloger skapas med behörighet `0777`.
