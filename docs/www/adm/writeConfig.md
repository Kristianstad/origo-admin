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
konstanten** som används av `restrictedLayer.php` (se nedan) – detta är
den bekräftade källan till den kopplingen vi tidigare flaggade som
öppen fråga.

Anropas troligen från en "Publicera"/"Spara"-knapp i `manage.php` (se
`functions/manage/printWriteConfigButton.php`, ej dokumenterad ännu).

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
- `dbh()`, `configTables($dbh)` – **ny, ej tidigare dokumenterad**,
  hämtar sannolikt samtliga konfigtabeller (`maps`, `groups`, `layers`,
  `sources`, `services`, `styles`, `plugins`, `controls`, `proj4defs`,
  `tilegrids`, `footers`, m.fl.) i ett svep, extraheras sedan till
  lokala variabler med `extract()` (se flaggning nedan)
- `pgArrayToPhp()`, `array_column_search()`
- `defineFileConstant($name, $value)` – **ny, ej tidigare dokumenterad**,
  skriver en PHP-konstant till en fil på disk (grund för hur
  `RESTRICTEDLAYERS` och andra `includeFileConstant()`-lästa konstanter
  uppstår)

**Konstanter:**
- `constants/webRoot.php` → `$webRoot`
- `constants/proxyRoot.php`, `constants/previewBase.php`
- `constants/searchEngineMeta.php` → geo/publisher-metadata för strukturerad data

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
| `fetchResourceContent.php` | `fetchResourceContent(string $resource): string\|false` | Hämtar innehåll från en lokal fil eller URL, med tillfälligt katalogbyte för att lösa relativa sökvägar. Används sannolikt för att bädda in externa CSS/JS-resurser i den publicerade HTML-sidan |
| `fixDuplicateDeclarations.php` | `fixDuplicateDeclarations($jsCode): string` | Textbaserad JS-transformation: hittar dubbeldeklarerade variabler (`const`/`let`/`var` med samma namn i samma "rot-scope") i administratörsskriven onload-JS, och skriver om dem till giltig JS (undviker `SyntaxError: Identifier has already been declared`). Se flaggning nedan – detta är en betydande mängd egen parsning |
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

## Koppling till manage-modulen: "changed"-flaggan

Nu bekräftad i sin helhet: `manage.php` (via `markMapsChanged()`)
sätter `maps.changed = 't'` varje gång en ändring görs som påverkar en
publicerad karta (lager, grupper, källor, etc.), och `writeConfig.php`
(via `markMapUnchanged()`) nollställer flaggan (`'f'`) efter lyckad
publicering. Detta ger sannolikt underlag för en "denna karta har
osparade ändringar, klicka för att publicera"-indikator någonstans i
manage-gränssnittet (troligen i `printWriteConfigButton.php`, ej
granskad ännu).

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

Anledningen till att både okomprimerade och förkomprimerade varianter
sparas är sannolikt att webbservern (nginx/Apache) är konfigurerad att
servera `.br`/`.gz`-varianten direkt till klienter som stödjer det,
utan att behöva komprimera vid varje request – en vanlig
prestandaoptimering för statiska filer.

## Kända begränsningar / observationer (preliminära – gäller granskade filer)

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
- **`fixDuplicateDeclarations()` är en handskriven, förenklad
  JS-parser** (radbaserad, med enkel sträng/scope-djup-spårning via
  räkning av `{`/`}`). Den hanterar inte flerradiga deklarationer,
  kommentarer som innehåller `{`/`}`, template literals, eller andra
  JS-syntax-särfall fullt ut. Fungerar sannolikt för det begränsade
  JS-mönster administratörer faktiskt skriver i onload-fält, men är
  skört mot mer komplex JS. Innehåller även en stor utkommenterad
  kodsektion (`$mapOnloadInit`) samt ett exempel-testblock i botten av
  filen – båda kan städas bort om funktionen anses stabil.
- **`json_format()` är en egen, handskriven JSON-formaterare** från
  2008 (enligt kommentar), med en kommentar om att anpassa den till
  PHP ≥5.4 – PHP har sedan version 5.4 haft `JSON_PRETTY_PRINT` inbyggt
  i `json_encode()`. Funktionen har redan logik för att använda detta
  (`if (phpversion() >= 5.4) return json_encode($json, JSON_PRETTY_PRINT);`)
  men **bara om indata inte redan är en sträng** – eftersom
  `writeConfig.php` alltid skickar in en redan färdig JSON-**sträng**
  (`json_format($json)` där `$json` redan är en strängvariabel), tas
  denna genväg aldrig i praktiken, och hela den manuella
  tecken-för-tecken-parsningen körs alltid. Kandidat för enkel
  förenkling: `json_encode(json_decode($json), JSON_PRETTY_PRINT |
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)` skulle sannolikt
  kunna ersätta hela filen, given att `$json` redan valideras med
  `json_decode()` strax innan i `writeConfig.php`.
- **`markMapUnchanged.php` har SQL injection-risk:** `$mapId` klistras
  in direkt i SQL-strängen utan escaping, samma mönster som flera
  tidigare `die()`-baserade underhållsfunktioner. `$mapId` kommer
  ursprungligen från `$_GET['map']` (bearbetad genom flera `explode()`-
  steg, men inte SQL-escapad).
- **`createSymlinkIfNotExists.php` har en `// GROK:`-kommentar** – ännu
  ett tecken på AI-assisterad kod i denna modul, konsekvent med
  mönstret i forwardauth/grouplayerfix.
- **God separation i de mindre hjälpfunktionerna** (`array_move`,
  `compressBrotli`, `compressGzip`, `getArrayValuesRecursively`) – dessa
  är rena, väldokumenterade, testbara funktioner utan globala
  beroenden. Bra förebilder för hur resten av modulen skulle kunna se
  ut efter refaktorering.
- Blandad kodstil: vissa filer har PHP 8-stil typade parametrar och
  returtyper (`compressBrotli`, `fetchResourceContent`,
  `getArrayValuesRecursively` delvis), andra har ingen typning alls
  (`addControlsToJson`, `groupDepth`, m.fl.) – bekräftar att modulen är
  en blandning av äldre och nyare kod, i linje med tidigare
  observationer om kodbasens historik.
- **Dödkod i både `renderCssTags.php` och `renderJavaScriptTags.php`:**
  variabeln `$url` byggs upp (`$proxyRoot . $_SERVER["REQUEST_URI"] . ...
  . 'badJson=y'`) i felhanteringsgrenen men **används aldrig** – koden
  skriver bara ut ett `alert()` med felmeddelandet och avslutar med
  `exit`, utan att navigera till `$url` (till skillnad från motsvarande
  felhantering i `writeConfig.php` självt, som *gör* en
  `window.location.href`-omdirigering till en `badJson=y`-variant för
  felsökning). Sannolikt en ofärdig kopiering av samma mönster – om
  avsikten var att erbjuda samma felsöknings-omväg här, saknas
  `window.location.href`-raden. Enkel att åtgärda eller ta bort
  variabeln om den inte behövs.
- **`renderCssTags.php` och `renderJavaScriptTags.php` är nästan
  identiska** i struktur (loop, regex-matchning av `include(...)`,
  felhantering, minifiering) – JS-varianten har bara fler kommandovarianter
  (`include_minify`/`include_nominify`). Kandidat att slå ihop till en
  gemensam hjälpfunktion med en parameter för tagg-typ, om ni vill minska
  dubblering vid framtida förenkling.
- **Blandat `require` / `require_once`** för samma konstant
  (`constants/proxyRoot.php`) mellan de två annars nästan identiska
  filerna (`renderCssTags.php` använder `require_once`,
  `renderJavaScriptTags.php` använder `require`) – ofarligt eftersom
  koden ändå avslutar med `exit` direkt efter, men ytterligare ett tecken
  på att filerna kopierats från varandra utan fullständig konsekvens.
- **`pgBoxToText()` gör antagandet att en box alltid har exakt två
  koordinatpar** (`explode('),(', ...)` följt av indexering `[0]`/`[1]`
  utan kontroll). Detta är korrekt för Postgres `box`-typen per
  definition, så ingen bugg, men värt att notera som ett implicit
  antagande om indata-formatet.
- **God, tydlig dokumentation i `publishMapFiles.php`** (PHPDoc-kommentar
  med parameterbeskrivningar) – bra förebild jämfört med de äldre
  `pg*ToText`-funktionerna som saknar all dokumentation.
- Fortsatt blandad typning: `publishMapFiles`, `renderCssTags`,
  `renderJavaScriptTags`, `saveFile` har fullständig PHP 8-typning;
  `pgArrayToText`, `pgBoolToText`, `pgBoxToText`, `pgCoordsToText` har
  ingen alls – ytterligare bekräftelse på åldersskiktning inom samma
  funktionsmapp.
- **HTML byggs ihop som ett enda JSON-strängvärde** för `"abstract"`-fältet:
  kontaktinfo, källinfo, tabellbeskrivningar och en hel `<form>` med
  inbäddad "Administrera"-knapp slås ihop till en lång HTML-sträng.
  Strängen läggs nu in i en PHP-array och escapes av `json_encode()`, så
  citattecken och radbrytningar förstör inte längre JSON-strukturen. HTML-
  innehållets egen säkerhet och presentation är fortfarande en separat
  fråga.
- **`$adminForm`-HTML:en bäddar in en hel `<form>`-tagg i
  `"abstract"`-strängen**, inklusive ett `<button>` som postar tillbaka
  till samma sida med lagrets id. Fungerar, men gör "abstract"-fältet
  till en blandning av faktisk beskrivning och UI-kontroller – lite
  ovanligt datamodellsmässigt (adminverktyg inbäddat i det som
  konceptuellt är "lagerbeskrivning"), värt att känna till om
  abstract-fältet någonsin ska återanvändas i ett annat sammanhang
  (t.ex. sökmotorexport, vilket vi redan sett i `writeConfig.php`s
  SEO-logik – där används dock `$layersMeta['abstract']`, en **separat**
  och renare kopia av beskrivningen utan adminformuläret, vilket är bra
  och undviker att adminknappen läcker ut i sökmotordata).
- **Mycket djup, delvis odokumenterad domänlogik för legend-/ikon-
  generering** (DPI, symbolstorlekar, LAYERSPACE, LAYERFONTSIZE, etc. som
  query-parametrar mot QGIS Servers `GetLegendGraphic`). Dessa "magiska
  siffror" (DPI=250, ICONLABELSPACE=3, BOXSPACE=1.8, etc.) är sannolikt
  resultatet av mycket manuellt visuellt finjusterande, och bör **inte**
  ändras utan att förstå att de påverkar hur legendikoner faktiskt ser
  ut i den publicerade kartan. Detta är en bra kandidat för en egen,
  namngiven hjälpfunktion (t.ex. `buildLegendUrl($service, $sourceProject,
  $layerName, $variant)`) vid framtida förenkling – inte för att ändra
  värdena, utan för att separera "bygg en legend-URL" som ett eget,
  testbart, dokumenterat koncept från resten av den redan komplexa
  funktionen.
- Helpers som bygger grupper, lager, källor och stilar tar emot
  `$writeConfigContext` explicit. `addLayersToJson()` returnerar en array
  och skickar samma kontext vidare vid rekursion, i stället för att bygga
  resultatet i en delad JSON-sträng.
- Ingen `strict_types` eller parametertypning (`$mapLayersList`,
  `&$layersMeta`, `$groupLayer` är alla otypade), konsekvent med övriga
  äldre delar av writeConfig-modulen.
