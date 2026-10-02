# Write config-modul (writeConfig)

**Entry point:** `adm/writeConfig.php`
**Funktionsfiler:** `adm/functions/writeConfig/*.php`

## Syfte
Den centrala "publiceringsfunktionen" i systemet: läser en kartas
fullständiga konfiguration från databasen (lager, grupper, källor,
stilar, kontroller, plugins) och genererar dels en Origo-kompatibel
JSON-konfiguration, dels en komplett, fristående, minifierad HTML-sida
som innehåller den färdiga kartan. Skriver resultatet till disk under
`<webRoot>/maps/<mapNamn>/`. Genererar även SEO-relaterad strukturerad
data (schema.org JSON-LD) och en `sitemap.xml` för kartor som är
markerade som sökmotorindexerbara. **Skriver även `RESTRICTEDLAYERS`-
konstanten** som används av `restrictedLayer.php`.

Anropas av knappen `printWriteConfigButton()` i `manage.php`.

## Anropas med
`writeConfig.php?map=<mapId>&<flaggor>`

| Parameter | Beskrivning |
|---|---|
| `map` | Id för kartan att publicera. Kan innehålla ett extra "swiper"-relaterat suffix separerat med `\` (workaround, se kod-kommentar i writeConfig.php) |
| `getJson` | `y` → returnera bara JSON-konfigurationen istället för HTML |
| `getHtml` | `y` → generera en förhandsgranskningsversion (annan bas-URL, från `previewBase` istället för `proxyRoot`); kan kombineras med `group`/`layer` för att förhandsgranska ett enskilt lager/grupp isolerat |
| `download` | `y` → skicka resultatet som nedladdningsbar fil istället för att visas i webbläsaren |
| `group` / `layer` | (endast med `getHtml=y`) begränsar förhandsgranskningen till en specifik grupp eller ett specifikt lager |
| `badJson` | Internt flöde – sätts av koden själv vid JSON-fel för att visa rå (ej validerad) JSON för felsökning, inte avsett att anropas direkt av användare |

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()`, `configTables($dbh)` – hämtar samtliga konfigtabeller (`maps`,
  `groups`, `layers`, `sources`, `services`, `styles`, `plugins`,
  `controls`, `proj4defs`, `tilegrids`, `footers`, m.fl.) i ett svep.
  `writeConfig.php` extraherar dem sedan till lokala variabler med
  `extract()`.
- `pgArrayToPhp()`, `array_column_search()`
- `defineFileConstant($name, $value)` – skriver en PHP-konstant till en fil
  på disk (källan till `RESTRICTEDLAYERS` och andra
  `includeFileConstant()`-lästa konstanter)

**Konstanter:**
- `constants/webRoot.php` → `$webRoot`
- `constants/proxyRoot.php`, `constants/previewBase.php`
- `constants/searchEngineMeta.php` → `$searchEngineMeta`, geo-/publisher-metadata för strukturerad data (array med `geo`, `contentLocation` och `publisher`)

**Externt bibliotek:** `matthiasmullie/minify` (composer,
`../../composer/minify/autoload.php`) för CSS/JS-minifiering.

**Databas:** läser samtliga konfigtabeller (via `configTables()`),
skriver `maps.changed = 'f'` (via `markMapUnchanged`).

**Filsystem:** skriver till `<webRoot>/maps/<mapNamn>/index[nummer].html`,
`structured-data[nummer].json` och `sitemap.xml`. `publishMapFiles()` skapar
även Brotli- och gzip-varianter, symlänkar i `<webRoot>/maps` och en symlink
`<webRoot>/<mapNamn>` till samma fysiska kartkatalog.

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `addControlsToJson.php` | `addControlsToJson($mapControls=null, &$mapCss='', &$mapJs='', &$mapOnload='', array &$context=array()): array` | Bygger kontroller som PHP-array och samlar CSS/JS/onload-kod i referensargument; delat konfigurationstillstånd finns i `$context` |
| `addGroupsToJson.php` | `addGroupsToJson($mapGroups, array &$context): array` | Bygger grupphierarkin rekursivt och uppdaterar grupp-/lagerdata i den explicita kontextarrayen |
| `addLayersToJson.php` | `addLayersToJson($mapLayersList, &$layersMeta, $groupLayer=false, array &$context=array()): array` | Bygger lager som PHP-arrayer, inklusive typspecifik logik (WMS/WFS/GEOJSON), abstract, legend-/ikon-URL:er och klusterstilar. GROUP-lager hanteras rekursivt; delat konfigurationstillstånd skickas i `$context` |
| `addPlugins.php` | `addPlugins($mapPlugins=null, &$mapCssFiles=array(), &$mapJsFiles=array(), &$mapCss='', &$mapJs='', &$mapOnload='', array &$context=array())` | Samlar JS/CSS (inline och som filer) samt onload-kod för aktiverade pluginer; konfigurationstillstånd skickas i `$context` |
| `addSourcesToJson.php` | `addSourcesToJson(array &$context): array` | Bygger en PHP-array för datakällor (`source`), inklusive URL, tile grid-inställningar och query-parametrar från konfigurationstillståndet |
| `addStylesToJson.php` | `addStylesToJson(array &$context): array` | Bygger en PHP-array för lagerstilar. Om `style_config` saknas i databasen byggs en enkel standardstil (label/ikon/filter) |
| `array_move.php` | `array_move(&$a, $oldpos, $newpos)` | Generisk hjälpfunktion: flyttar ett element i en array från ett index till ett annat |
| `compressBrotli.php` | `compressBrotli(string $data): ?string` | Komprimerar med Brotli om PHP-tillägget finns, annars `null` |
| `compressGzip.php` | `compressGzip(string $data): ?string` | Komprimerar med gzip (nivå 9) |
| `createSymlinkIfNotExists.php` | `createSymlinkIfNotExists(string $target, string $link): bool` | Skapar eller ersätter en symlänk; befintliga filer och kataloger på länkmålet tas bort |
| `fetchResourceContent.php` | `fetchResourceContent(string $resource): string\|false` | Hämtar innehåll från en lokal fil eller URL, med tillfälligt katalogbyte för att lösa relativa sökvägar. Används av `renderCssTags()` och `renderJavaScriptTags()` för att läsa in resurser som ska bäddas in i den publicerade HTML-sidan |
| `fixDuplicateDeclarations.php` | `fixDuplicateDeclarations($jsCode): string` | Textbaserad JS-transformation: hittar dubbeldeklarerade variabler (`const`/`let`/`var` med samma namn i samma "rot-scope") i administratörsskriven onload-JS, och skriver om dem till giltig JS (undviker `SyntaxError: Identifier has already been declared`). Radbaserad egen parsning; se "Begränsningar och risker" nedan |
| `getArrayValuesRecursively.php` | `getArrayValuesRecursively(array $array): array` | Plattar ut en nästlad array till en enkel lista med alla "löv"-värden |
| `groupDepth.php` | `groupDepth($groupIds, $layerIds=array(), array &$context=array())` | Rekursivt: bygger en nästlad struktur över lager och undergrupper, med lagernamn prefixade av gruppsökvägen (`grupp>lager`); använder kontextarrayen för delat konfigurationstillstånd |
| `indexweightedLayersList.php` | `indexweightedLayersList($layersList, array &$context)` | Sorterar lagerlistan efter `indexweight` och använder kontextarrayen för delat konfigurationstillstånd |
| `json_format.php` | `json_format($json): string` | Formaterar en JSON-sträng med indrag för läsbarhet (egen handskriven parser, äldre ursprung enligt kodkommentar – "Nicejson", 2008) |
| `markMapUnchanged.php` | `markMapUnchanged(&$dbh, $mapId)` | Sätter `maps.changed = 'f'` efter lyckad publicering |
| `pgArrayToText.php` | `pgArrayToText($pgArray): string` | Konverterar Postgres arraysyntax (`{a,b,c}`) till kommaseparerad text utan klamrar – enklare variant av `pgArrayToPhp()` som ger en sträng istället för en PHP-array |
| `pgBoolToText.php` | `pgBoolToText($pgBool)` | Konverterar Postgres bool-representation (`'t'`/`'f'`) till JS-litteralerna `"true"`/`"false"`. Returnerar värdet oförändrat om det inte är `'t'`/`'f'` |
| `pgBoxToText.php` | `pgBoxToText($pgBox): string` | Konverterar Postgres box-syntax (`(x2,y2),(x1,y1)`) till en kommaseparerad koordinatlista, och sorterar hörnen så att den mindre x-koordinaten kommer först |
| `pgCoordsToText.php` | `pgCoordsToText($pgCoords): string` | Konverterar Postgres punkt-syntax (`(x,y)`) till kommaseparerad text utan parenteser |
| `publishMapFiles.php` | `publishMapFiles($filepathWithoutSuffix, $html, $json, $mapId): void` | Skriver HTML/JSON och komprimerade varianter, skapar symlänkar i `/www/maps` och länkar `/www/<kartnamn>` till samma fysiska kartkatalog |
| `removePath.php` | `removePath(string $path): bool` | Tar bort filer, symlänkar och katalogträd innan ett länkmål ersätts |
| `renderCssTags.php` | `renderCssTags(array $items): string` | Bygger HTML för CSS-inkludering: `include(sökväg)`-syntax läses in och minifieras som inline `<style>`, annars renderas som vanlig `<link rel="stylesheet">` |
| `renderJavaScriptTags.php` | `renderJavaScriptTags(array $items): string` | Bygger HTML för JS-inkludering: stödjer `include(...)`/`include_minify(...)` (minifieras) och `include_nominify(...)` (lämnas oförändrad, för redan minifierade bundles), annars renderas som vanlig `<script src="...">` |
| `saveFile.php` | `saveFile(string $path, string $content): bool` | Enkel, defensiv wrapper runt `file_put_contents()` |
| `splitValues.php` | `splitValues($valueString): array` | Delar en kommaseparerad JS-värdelista utan att dela vid komma inom strängar eller `{}`/`[]`/`()`. Används av `fixDuplicateDeclarations()` |

## Koppling till manage-modulen: "changed"-flaggan

`manage.php` (via `markMapsChanged()`) sätter `maps.changed = 't'` varje
gång en ändring görs som påverkar en publicerad karta (lager, grupper,
källor, etc.), och `writeConfig.php` (via `markMapUnchanged()`) nollställer
flaggan (`'f'`) efter lyckad publicering. `printWriteConfigButton()` använder
flaggan för att visa knappen i "ändrad"-läge.

## Publiceringskedjan till disk

writeConfig.php
└─ publishMapFiles($filepathWithoutSuffix, $html, $json, $mapId)
├─ saveFile() → sparar okomprimerad .html och .json
├─ compressBrotli() → sparar .html.br / .json.br (om tillägget finns)
├─ compressGzip() → sparar .html.gz / .json.gz
├─ createSymlinkIfNotExists() → skapar publika symlänkar i
│  <webRoot>/maps/<mapId>.html[.br|.gz] och .json[.br|.gz]
├─ createSymlinkIfNotExists() → länkar <webRoot>/<kartnamn> till
│  <webRoot>/maps/<kartnamn>
└─ createSymlinkIfNotExists() → skapar root-länkar i <webRoot> som pekar
  via kartkatalogens symlink till samma fysiska filer

Okomprimerade och förkomprimerade varianter sparas båda. Hur webbservern
serverar `.br`/`.gz` styrs av webbserverkonfigurationen i basavbilden och kan
inte verifieras i det här repot.

## Begränsningar och risker

- Hela kartkonfigurationen byggs som
  PHP-arrayer, inklusive kontroller, `pageSettings`, kartmetadata,
  proj4-definitioner, grupper, lager, sources och styles, och serialiseras
  med `json_encode()`. SEO-blockets JSON-LD byggs också som array och
  serialiseras separat. CSS-, JS- och onload-bihang hanteras fortfarande
  via befintliga referensparametrar. Sitemap-filen är XML och byggs därför
  fortsatt som XML-text.
- `writeConfig.php` hämtar tabeller via `configTables()` och använder
  `extract($configTables)` för att skapa lokala variabler. Därefter
  samlas delat tillstånd i `$writeConfigContext`, som skickas explicit
  till de helpers som behöver det. Vid ändring av kontextens innehåll,
  följ både initieringen i entry pointen och helper-anropen.
- `fixDuplicateDeclarations()` är en radbaserad JavaScript-parser med enkel
  spårning av sträng- och scope-djup via räkning av `{`/`}`. Den hanterar
  inte flerradiga deklarationer, kommentarer som innehåller `{`/`}`,
  template literals eller andra syntaxfall fullt ut. Värdelistor delas av
  `splitValues()`.
- `json_format()` är en handskriven JSON-formaterare från 2008 (enligt
  kodkommentar). Genvägen `json_encode($json, JSON_PRETTY_PRINT)` används
  bara om indata inte redan är en sträng; `writeConfig.php` skickar en
  färdig JSON-sträng, så den manuella tecken-för-tecken-parsningen körs
  alltid.
- `markMapUnchanged.php` bygger in `$mapId` direkt i SQL-strängen utan
  escaping. `$mapId` kommer från `$_GET['map']` (efter flera
  `explode()`-steg).
- `renderCssTags.php` och `renderJavaScriptTags.php` bygger i
  felhanteringsgrenen variabeln `$url` (`$proxyRoot . $_SERVER["REQUEST_URI"] . ...
  . 'badJson=y'`) men använder den aldrig: koden skriver ett `alert()` med
  felmeddelandet och avslutar med `exit`. `writeConfig.php` gör motsvarande
  `window.location.href`-omdirigering till en `badJson=y`-variant.
- `renderCssTags.php` och `renderJavaScriptTags.php` är nästan identiska i
  struktur (loop, regex-matchning av `include(...)`, felhantering,
  minifiering). JS-varianten har fler kommandovarianter
  (`include_minify`/`include_nominify`). De läser `constants/proxyRoot.php`
  med `require_once` respektive `require`.
- `pgBoxToText()` förutsätter att en box har exakt två koordinatpar
  (`explode('),(', ...)` med indexering `[0]`/`[1]` utan kontroll). Det gäller
  för Postgres `box`-typen.
- Typningen är blandad: `publishMapFiles`, `renderCssTags`,
  `renderJavaScriptTags`, `saveFile`, `compressBrotli` och
  `fetchResourceContent` har PHP 8-typning, medan `pgArrayToText`,
  `pgBoolToText`, `pgBoxToText`, `pgCoordsToText`, `addControlsToJson` och
  `groupDepth` saknar typning.
- Lagrets `"abstract"`-fält byggs som en HTML-sträng: kontaktinfo,
  källinfo, tabellbeskrivningar och en hel `<form>` med en
  "Administrera"-knapp som postar tillbaka till samma sida med lagrets id.
  Strängen läggs i en PHP-array och escapes av `json_encode()`. SEO-logiken
  använder i stället `$layersMeta['abstract']`, en separat kopia utan
  adminformuläret, så att adminknappen inte hamnar i sökmotordata.
- Legend- och ikon-URL:erna mot QGIS Servers `GetLegendGraphic` använder
  fasta värden (DPI=250, ICONLABELSPACE=3, BOXSPACE=1.8, LAYERSPACE,
  LAYERFONTSIZE m.fl.) som påverkar hur legendikoner ser ut i den
  publicerade kartan. Ändra dem inte utan visuell kontroll.
- Helpers som bygger grupper, lager, källor och stilar tar emot
  `$writeConfigContext` explicit. `addLayersToJson()` returnerar en array
  och skickar samma kontext vidare vid rekursion, i stället för att bygga
  resultatet i en delad JSON-sträng.
