# Group layer fix-modul (grouplayerfix)

**Entry point:** `adm/grouplayerfix.php`
**Funktionsfiler:** `adm/functions/grouplayerfix/*.php`

## Syfte
En specialiserad proxy mot QGIS Server som löser ett QGIS-specifikt
problem: QGIS Server stödjer inte alltid "grupplager" (ett lagernamn som
egentligen representerar en grupp av flera underliggande lager) för vissa
anropstyper. Denna proxy upptäcker sådana fall, tar reda på gruppens
faktiska underlager (via QGIS-projektets `GetProjectSettings`-XML,
cachad), och expanderar anropet till att gälla alla underlager istället –
transparent för den anropande klienten (Origo-kartan).

Hanterar två specialfall (se nedan) och vidarebefordrar allt annat
oförändrat till QGIS Server.

## Anropas med
`grouplayerfix.php?qgis_url=<url-eller-relativ-path>&<godtyckliga WMS/WFS-parametrar>`

| Parameter | Beskrivning |
|---|---|
| `qgis_url` | Full URL eller path som börjar med `/` till QGIS Server. Om relativ path byggs full URL från klientens `HTTP_HOST` |
| `ttl` | (valfri) Cache-livslängd i sekunder för projektstruktur/describeFeatureType, default 600 |
| övriga | Godtyckliga WMS/WFS-parametrar, vidarebefordras till QGIS Server |

**Specialfall 1 – WMS GetMap med FILTER på grupplager:**
Triggas när `REQUEST=GetMap`, `SERVICE=WMS`, `FILTER` är satt, och
`LAYERS`/`layers` pekar på ett lager som visar sig vara en grupp. Både
`LAYERS` och `FILTER` expanderas till att räkna upp gruppens underlager.

**Specialfall 2 – WFS describeFeatureType på grupplager:**
Triggas när `request=describeFeatureType`, `service=WFS`, `typeName` är
satt. Om `typeName` är en grupp görs **parallella** describeFeatureType-
anrop (via `curl_multi`) mot varje underlager, med individuell retry
(upp till 4 försök per lager, exponentiell backoff), och resultaten slås
ihop till ett enda `featureTypes`-svar.

**Allt annat:** vidarebefordras oförändrat via `forwardToQgisServer()`.

## Beror på
**OBS: `functions/common/` inkluderas INTE i denna modul** – till skillnad
från nästan alla andra moduler vi dokumenterat. Detta är medvetet
kommenterat i koden (`//includeDirectory("./functions/common");`) men
motivet är inte förklarat. Konsekvens: ingen databasanslutning, ingen
session, ingen av de vanliga common-hjälpfunktionerna är tillgängliga
här – modulen är helt fristående från resten av `adm/`.

**Cache:** APCu (`apcu_fetch`/`apcu_store`) om tillgängligt, annars
filbaserad cache i systemets temp-katalog (`sys_get_temp_dir()`). Loggar
ett meddelande om APCu saknas.

**Externt system:** QGIS Server, anropat via `curl` (både enskilda
anrop och parallella `curl_multi`-anrop).

**Filsystem:** skriver cache-filer till `sys_get_temp_dir()`, läses
tillbaka baserat på filens ändringstid jämfört med TTL.

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `forwardToQgisServer.php` | `forwardToQgisServer($url, $params, $maxRetries = 4): array` | Skickar ett anrop till QGIS Server via curl, med retry vid transienta fel (ingen HTTP-status alls, d.v.s. nätverksfel, inte vid 4xx/5xx). Returnerar `['body' => ..., 'headers' => ...]` |
| `getCachedDescribeFeatureType.php` | `getCachedDescribeFeatureType($qgisUrl, $typeName): string` | Hämtar describeFeatureType-JSON för ett enskilt lager, cachad enligt samma mönster |
| `getCachedProjectSettings.php` | `getCachedProjectSettings($qgisUrl): string` | Hämtar QGIS-projektets `GetProjectSettings`-XML, cachad (APCu eller fil) enligt TTL |
| `getLayerNamesInGroup.php` | `getLayerNamesInGroup($xml, $groupName): array` | Rekursiv, namespace-säker XPath-sökning: hittar alla "löv"-lagernamn under en given grupp i projekt-XML:en. Returnerar tom array om `$groupName` inte är en grupp |
| `getResponseContentType.php` | `getResponseContentType($params): string` | Bestämmer Content-Type baserat på `outputFormat`-parametern, annars `text/xml`. Har ingen anropare |

## Begränsningar och risker

- **Odefinierad variabel `$DEFAULT_QGIS_SERVER_PATH`:** koden sätter
  `$DEFAULT_QGIS_SERVER_URL = '';` men läser
  `$_GET['qgis_url'] ?? $DEFAULT_QGIS_SERVER_PATH`. `_PATH`-variabeln sätts
  aldrig, så fallbacken när `qgis_url` saknas ger en PHP-varning
  (undefined variable) och `null`.
- **Operatorprioritet i `getCachedProjectSettings.php`:**
  `if ($response['headers']['http_code'] ?? 0 >= 200 && ($response['headers']['http_code'] ?? 0) < 300)`
  tolkas som `$response['headers']['http_code'] ?? (0 >= 200 && ...)`.
  Villkoret blir därför sant för varje satt, icke-noll statuskod, och
  felsvar kan cachas. `getCachedDescribeFeatureType.php` har motsvarande
  kontroll skriven med parenteser (`$httpCode >= 200 && $httpCode < 300`).
- `grouplayerfix.php` har i specialfall 2 (describeFeatureType) två
  identiska block efter varandra med kommentar, `$combinedFeatureTypes = []`
  och `$ttl`; den andra tilldelningen skriver över den första med samma
  värden.
- `forwardToQgisServer()` gör om anropet vid *nätverksfel*
  (`$rawResponse === false || $httpCode === 0`), medan `fetchWithStatus()` i
  restrictedLayer-modulen gör om vid *5xx-svar*.
- `getResponseContentType()` har ingen anropare; `grouplayerfix.php` sätter
  Content-Type-headers direkt på flera ställen.
- `grouplayerfix.php` och `restrictedLayer.php` pekar mot **två skilda
  QGIS-tjänster**, så avsaknaden av koppling till `RESTRICTEDLAYERS` och
  `$_SESSION['user']` i grouplayerfix läcker inte skyddad information.
- Curl-inställningarna (`CURLOPT_*`) upprepas tre gånger i
  `grouplayerfix.php` (initiering och retry-block för describeFeatureType)
  och en fjärde gång i `forwardToQgisServer.php`.
