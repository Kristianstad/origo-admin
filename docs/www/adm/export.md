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

**adm/export.php och adm/functions/export/ finns inte alls i den här
arbetsytan** (bekräftat med filsökning, 0 träffar) — skillnaden mot
"finns men är hemlig" är viktig: det är inte bara att koden är
utelämnad ur det publika repot, filerna saknas helt lokalt också. Denna
dokumentation beskriver modulen utifrån
tidigare granskning i en miljö där filerna fanns, som referens för den
organisation som äger den koden — inte utifrån filer som går att läsa
i just detta arbetsytträd.

Det finns även en delvis duplicerad kopia av flera av dessa
funktionsfiler i den fristående `export/`-mappen på toppnivå (utanför
`adm/`) – se anteckning i ARCHITECTURE.md om detta, följs upp vid
loader-genomgången.

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
| `group` | Gruppnamn (läses men används inte synligt i den kod vi sett – flaggat nedan) |
| `layers` | Semikolon-separerad lista med lagernamn att exportera |
| `F` | Exportformat: `bas_dxf`, `bas_tiff`, `tl_bak`, `tl_dxf`, `tl_shape`, `tl_geojson`, `intresse`, `nnh_csv`, `stmp_csv` |
| `C` | Koordinatsystem (SRS), default `EPSG:3008` (SWEREF99 13 30, troligen lokal standard) |
| `SF` | (endast `bas_dxf`) valfritt filnamn för resultatet |
| `V` | (endast `intresse`) parameter till intresserapport-rapporten |
| JSON body `J`, `RN` | Ritade lagerobjekt (GeoJSON per ritlager) och deras namn, kopplas till exporten som extra indata till FME |
| `PHPSESSID` (POST-fält eller JSON) | Låter anropande kod återanvända en specifik session-id, troligen för att export-anropet ska köra i samma session som huvudsidan |

**Svar:** ett kort textmeddelande (`"Du får ett epostmeddelande när
exporten är färdig."`) — det faktiska resultatet skickas senare via
e-post från FME Server, inte via detta HTTP-svar.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `readAndCloseSession()`, `dbh()`, `initUserLdap($dbh)` – standardstart
- `all_from_table()`, `array_column_search()`, `pgArrayToPhp()` – hämtar
  och slår upp maps/groups/layers/sources/services

**Konstanter:**
- `constants/authMethod.php` → `$authMethod`
- `constants/configSchema.php` → `$configSchema`

**Databas:** läser `maps`, `groups`, `layers`, `sources`, `services`.

**Filsystem:** skapar temporära arbetskataloger under
`/fmeserver/data/resources/temp/ikarta/<user_id>/<uniqid>/`, skriver
hämtad kartdata dit, och städar upp katalogen i slutet (inkl. en
`sleep(10)` innan borttagning – se flaggning).

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
| `fetchWfs.php` | `fetchWfs($serveraddr, $typename, $bbox, $srs, $savefile)` | Hämtar rå GML (WFS GetFeature). **Verkar oanvänd** i `export.php`s nuvarande flöde – kandidat för borttagning eller kvarleva från tidigare funktionalitet |

## Kända begränsningar / observationer (ej åtgärdat ännu)

*Notera: eftersom denna modul redan är känd som organisationsspecifik
och inte planerad för generalisering, fokuserar flaggningen nedan på
buggrisk och begriplighet snarare än på att föreslå att hårdkodade
värden bryts ut.*

- **⚠️ Hårdkodad hemlig token i klartext i källkoden:**
  `Authorization: fmetoken token=xxx`
  ligger direkt i `export.php`. Detta är en autentiseringsnyckel till
  FME Server. Oavsett generaliseringsambitioner är det värt att
  överväga att flytta just denna till en konstant/miljövariabel av
  ren säkerhetshygien (t.ex. för att kunna rotera token utan
  kodändring, och för att undvika att den av misstag hamnar i
  versionshantering synlig för fler än nödvändigt).
- **⚠️ SQL/koddel med `array_shift(array_filter(...))` utan
  `array_values()`:** `array_shift(array_filter($allLayerIds, ...))`
  fungerar korrekt i PHP (array_shift hanterar icke-sekventiella nycklar
  fint), men är en ovanlig kombination värd en kommentar om vad den gör
  – "hitta första elementet som matchar villkoret" – för läsbarhetens
  skull vid framtida underhåll.
- **Stor utkommenterad kodblock i `export.php`** (rader kring
  `bas_dxf`/`bas_tiff`-hantering av `$layerNames`, samt flera `var_dump`/
  `exit`-felsökningsrester i `fetchWfs.php`/`fetchDxf.php`/`fetchWms.php`).
  Ofarligt men gör filerna svårare att läsa – kandidat för enkel
  städning oavsett generaliseringsplaner.
- **`fetchWms.php` refererar till `$styles`** utan att variabeln någonsin
  sätts i funktionen (den utkommenterade koden som skulle sätta den är
  bortkommenterad). Detta ger en PHP-varning (undefined variable) vid
  varje anrop, men resulterar i praktiken i tom sträng vilket händelsevis
  fungerar (`STYLES=` tomt är giltigt för WMS). Ofarlig bugg, men värd
  att känna till.
- **`$groupName` (från `?group=`) läses men används aldrig synligt** i
  den kod vi ser. Antingen dödkod eller använt av kod vi inte sett –
  flaggat för uppföljning.
- **`sleep(10)` före `rmdir()` i slutet av scriptet** blockerar hela
  PHP-processen i 10 sekunder efter att svaret redan skickats till
  klienten (tack vare `fastcgi_finish_request()` spelar detta ingen
  roll för användarupplevelsen, men det håller en PHP-worker upptagen
  onödigt länge om `fastcgi_finish_request()` inte skulle vara
  tillgänglig i någon miljö). Sannolikt en enkel "vänta tills FME hunnit
  läsa filerna"-lösning. Fungerar, men skört – en race condition mellan
  hur snabbt FME hinner läsa filerna och den fasta 10-sekunders-väntan.
- **Ingen `htmlspecialchars()`** vid utskrift av `$_SESSION['user']['mail']`
  m.fl. i JSON-strängarna som byggs manuellt (strängkonkatenering,
  inte `json_encode()`). Om ett mejl eller annat användarfält innehåller
  citattecken kan det förstöra JSON-strukturen som skickas till FME.
  Värt att byta till `json_encode()` av hela `$postfields`-strukturen
  som en array, istället för manuell strängbyggnad, oavsett
  generaliseringsplaner – det är en robusthetsfråga snarare än en
  stilfråga.
- **Ingen `strict_types`** eller parametertypning i någon fil.
- **`fetchWfs.php` har en `var_dump($wfsstr);`** kvar okommenterad
  (till skillnad från motsvarande i övriga fetch-filer, som är
  utkommenterade) – skriver troligen ut felsökningsdata i produktion.
  Enkel städning.
