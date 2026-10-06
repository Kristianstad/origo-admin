# Export-modul (export)

**Entry point:** `adm/export.php`
**Funktionsfiler:** `adm/functions/export/*.php`
**Stilmall:** `adm/styles/export.css`

**OBS – organisationsspecifik kod:** denna modul är hårt kopplad till er
specifika infrastruktur (FME Server-instans, filsökvägar, domännamn,
API-nycklar) och ingår **inte** i det publika GitHub-repot. Den är inte
byggd för att vara generellt återanvändbar av andra organisationer, och
det är ett medvetet val att inte bryta ut de organisationsspecifika
värdena till konstanter i nuläget. Observationerna nedan noterar ändå
var dessa värden finns, för den som behöver hitta och ändra dem – inte
som förslag på generalisering.

**adm/export.php och adm/functions/export/ finns inte i den här
arbetsytan.** Koden är utelämnad ur det publika repot. Dokumentationen
beskriver modulen utifrån en granskning i en miljö där filerna fanns, som
referens för den organisation som äger koden, och kan inte verifieras mot
filer i detta arbetsträd.

Det finns även en delvis duplicerad kopia av flera av dessa
funktionsfiler i den fristående `export/`-mappen på toppnivå (utanför
`adm/`) – se anteckning i ARCHITECTURE.md. Mappen finns inte i arbetsytan
och kan inte verifieras.

## Syfte
Låter en inloggad användare beställa export av ett kartutsnitt (angivet
som rektangel eller polygon, ritad i Origo-kartan) till valfritt av
flera filformat (DXF, Shapefile, GeoJSON, TIFF/bakgrundskarta) eller
specialrapporter (intresseyta, höjddata/NNH, stompunkter). Exporten är
**asynkron**: sidan svarar omedelbart till användaren att exporten är
på gång (`fastcgi_finish_request()`), fortsätter sedan köra i
bakgrunden, hämtar data från bakomliggande WMS/WFS-tjänster, och
lämnar över den faktiska formatkonverteringen och filleveransen till en
extern **FME Server**, som mejlar resultatet när det är klart.

## Anropas med
`export.php?<parametrar>` (GET för parametrar, kroppen kan även
innehålla JSON-data via POST body för vissa fält)

| Parameter | Beskrivning |
|---|---|
| `B` | Bounding box som rektangel: `minX,minY,maxX,maxY` |
| `BP` | Alternativ till `B`: polygon som punktlista, används för att räkna ut en omslutande rektangel |
| `map` | Id för kartan exporten gäller |
| `group` | Gruppnamn (läses men används inte i exportflödet) |
| `layers` | Semikolon-separerad lista med lagernamn att exportera |
| `F` | Exportformat: `bas_dxf`, `bas_tiff`, `tl_bak`, `tl_dxf`, `tl_shape`, `tl_geojson`, `intresse`, `nnh_csv`, `stmp_csv` |
| `C` | Koordinatsystem (SRS), default `EPSG:3008` (SWEREF99 13 30) |
| `SF` | (endast `bas_dxf`) valfritt filnamn för resultatet |
| `V` | (endast `intresse`) parameter till intresserapport-rapporten |
| JSON body `J`, `RN` | Ritade lagerobjekt (GeoJSON per ritlager) och deras namn, kopplas till exporten som extra indata till FME |
| `PHPSESSID` (POST-fält eller JSON) | Låter anropande kod återanvända ett specifikt session-id |

**Svar:** ett kort textmeddelande (`"Du får ett epostmeddelande när
exporten är färdig."`) — det faktiska resultatet skickas senare via
e-post från FME Server, inte via detta HTTP-svar.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `readAndCloseSession()`, `dbh()`, `initUserLdap($dbh)` – standardstart
- `allFromTable()`, `arrayColumnSearch()`, `pgArrayToPhp()` – hämtar
  och slår upp maps/groups/layers/sources/services

**Konstanter:**
- `constants/authMethod.php` → `$authMethod`
- `constants/configSchema.php` → `$configSchema`

**Databas:** läser `maps`, `groups`, `layers`, `sources`, `services`.

**Filsystem:** skapar temporära arbetskataloger under
`/fmeserver/data/resources/temp/ikarta/<user_id>/<uniqid>/`, skriver
hämtad kartdata dit, och städar upp katalogen i slutet (inkl. en
`sleep(10)` innan borttagning – se begränsningar).

**Externa tjänster (hårdkodade, organisationsspecifika):**
- Interna QGIS Server-instanser, t.ex.
  `https://kartor.kristianstad.se/qgisserver-internt/ows/grundkarta_qgs`
  (basskarta) och `https://kartor.kristianstad.se<baseUrl>/<sourceName>`
  (övriga lager, `baseUrl` från `services`-tabellen)
- **FME Server** REST API:
  `https://fmeflow.kristianstad.se/fmerest/v3/transformations/transact/MSF_Origo/<fmeFile>`,
  autentiserat med en hårdkodad token
  (`Authorization: fmetoken token=...`) direkt i källkoden
- FME-arbetsflödesfiler (`.fmw`) per exportformat: `raster.fmw`,
  `intresseyta.fmw`, `nnh2csv.fmw`, `stompunkt2csv.fmw`, `tl_dxf.fmw`,
  `tl_shape.fmw`, `tl_geojson.fmw`, `tandalager.fmw`
- NFS-sökväg inbäddad i FME-parametrar:
  `$(MSF_NFSVolume)rancher-nfs/fmeserver23619/...`

## Filer och funktioner

| Fil | Funktion | Beskrivning |
|---|---|---|
| `allLayerIds.php` | `allLayerIds($mapOrGroup, $allLayerIds=[])` | Samlar rekursivt ihop alla lager-id:n för en karta, inklusive lager i undergrupper |
| `commonCurlSetopt.php` | `commonCurlSetopt($ch)` | Delade curl-inställningar för alla fetch-funktionerna: timeout, SSL, IPv4, DNS-cache, samt vidarebefordrar aktuell PHP-sessions-cookie till den bakomliggande tjänsten |
| `fetchWms.php` | `fetchWms($serveraddr, $layers, $format, $bbox, $srs, $width, $height, $savefile)` | Hämtar en bildkarta (WMS GetMap) och sparar till fil |
| `fetchDxf.php` | `fetchDxf($serveraddr, $layers, $bbox, $srs, $savefile)` | Hämtar en DXF-export (WMS GetMap med DXF-format, specialparametrar för symbolik/attribut) |
| `fetchShape.php` | `fetchShape($serveraddr, $layers, $bbox, $srs, $savefile)` | Hämtar features som Shapefile (WFS GetFeature, OUTPUTFORMAT=SHP) |
| `fetchGeojson.php` | `fetchGeojson($serveraddr, $layers, $bbox, $srs, $savefile)` | Hämtar features som GeoJSON (WFS GetFeature, OUTPUTFORMAT=GEOJSON) |
| `fetchWfs.php` | `fetchWfs($serveraddr, $typename, $bbox, $srs, $savefile)` | Hämtar rå GML (WFS GetFeature). Används inte i `export.php`s flöde |

## Begränsningar och risker

Implementationen kan inte verifieras i arbetsytan; uppgifterna nedan gäller
den granskade koden.

- **Hårdkodad hemlig token** (`Authorization: fmetoken token=...`) ligger i
  klartext i `export.php` och ger åtkomst till FME Server.
- JSON-strängarna till FME byggs manuellt med strängkonkatenering, utan
  escaping av `$_SESSION['user']['mail']` m.fl. Citattecken i ett
  användarfält bryter JSON-strukturen.
- `sleep(10)` före `rmdir()` blockerar PHP-processen i tio sekunder efter att
  svaret skickats (`fastcgi_finish_request()`), så att FME hinner läsa
  filerna. Det är en fast väntetid, inte en synkronisering.
- `fetchWms.php` läser `$styles` utan att sätta variabeln (PHP-varning vid
  varje anrop; `STYLES=` blir tomt, vilket WMS accepterar).
- `fetchWfs.php` har en okommenterad `var_dump($wfsstr);`.
- `$groupName` (från `?group=`) läses men används inte.
- `export.php` har ett stort utkommenterat block kring `bas_dxf`/`bas_tiff`
  och `fetchDxf.php`/`fetchWms.php` har utkommenterade `var_dump`/`exit`.
- `array_shift(array_filter($allLayerIds, ...))` hittar första elementet som
  matchar villkoret.
