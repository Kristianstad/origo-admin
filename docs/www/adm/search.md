# Sökmodul (search)

**Entry points:** `adm/search.php` (sök-API), `adm/clearSearchCache.php` (töm-knappen i Verktyg-vyn)
**Loader:** `www/search/search-loader.php` (se ARCHITECTURE.md)
**Funktionsfiler:** `adm/functions/search/*.php`; konfigurationscachen ligger i `adm/functions/common/search*.php`, eftersom `search.php`, `manage.php`, `sql_import.php` och `clearSearchCache.php` delar den

## Syfte
Söker i konfigurerade databastabeller och returnerar träffarna som JSON, till exempel för Origos sökfunktion. Vilka tabeller som söks, i vilken databas och hur, konfigureras i adminverktyget (vyn **Sök**) och lagras i konfigurationsdatabasen.

## Anropas med
`/php/search/search-loader.php?<sökmodell>=<sökord>`

| Parameter | Beskrivning |
|---|---|
| `<sökmodell>` | Parameterns namn är en sökmodells id (`searchmodels.searchmodel_id`) och värdet är sökordet. Första parametern vars namn är en sökmodell används. PHP ersätter punkt och mellanslag i parameternamn med `_`, så modell-id:n bör bara innehålla bokstäver, siffror, `-` och `_` |

Anges ingen känd modell svarar sökningen med HTTP 400 och `{"error":"Unknown search model"}`; det finns ingen reservmodell. Prefixet `/php/` är det som kodexemplen i övriga moduler använder; hur det mappas bestäms av webbserverkonfigurationen och kan inte verifieras här.

**Svar:** JSON-array med träffar. Varje träff har `NAMN` (söktabellens sökfält), `GID`, eventuella extrafält, `TYPE` (söktabellens `customtype`, annars tabellens namn), `TITLE` (om söktabellen har titel eller titelfält) och `GEOM` (WKT). Svarshuvudena `X-Search-Source` (`live`, `cache`, `mixed` eller `none`) och `X-Search-Cache-Age-Seconds` anger varifrån träffarna kom. Kan ingen konfiguration läsas svarar sökningen med HTTP 503.

## Konfiguration i databasen
Tre typer i manage-verktyget. Vyn **Sök** (`constants/views.php`) visar `searchmodels`, `searchtables` och `tables`; databaserna hanteras i vyn Meta.

| Tabell | Innehåll |
|---|---|
| `databases` | Anslutningssträng (`connectionstring`) och `usepconnect` (beständig anslutning med `pg_pconnect`, annars `pg_connect`). Anslutningen används för att söka i söktabellens data; vilken databas som gäller för en söktabell framgår av tabellens id |
| `searchtables` | En sökbar tabell eller vy: `table` (ett id i typen tabell, `databas.schema.tabell`; databas, schema och tabellnamn härleds ur id:t med uppdelning på de två första punkterna), `searchfield`, `geometryfield` (standard `geom`), `gidfield` (standard `gid`), `title` eller `titlefield` (kategori per träff), `mode`, `sortmode`, `ttl`, `maxcacheagefactor`, `"limit"`, `usecentroid`, `customtype`, `fields` (extra kolumner) |
| `searchmodels` | En sökmodell: `searchtables` (ordnad lista; ordningen styr träffarnas ordning), `"limit"` (totalt antal träffar, standard 20) och `liverows` (rader vid direktsökning i läget `intelligent`, standard 300) |

Fältens betydelse beskrivs i hjälptexterna (`searchtable:*`, `searchmodel:*`, `database:usepconnect` i `helps`). Id-byte på en databas, ett schema, en tabell eller en söktabell uppdaterar referenserna i `searchtables.table` respektive `searchmodels.searchtables` (se manage.md, "Ändra id på ett objekt"). Kolumnerna `limit` och `table` är reserverade ord i PostgreSQL; `appendUpdatedColumnsToSql()` citerar alla kolumnnamn. `fields` och `searchtables` finns i `constants/arrayColumns.php`.

**Tabeller och vyer:** `table` väljs bland tabellerna i konfigurationen. `read_schema_tables.php` registrerar bara bastabeller (`table_type = 'BASE TABLE'`), så vyer och materialiserade vyer måste skapas manuellt som tabell med id `databas.schema.vynamn` innan de kan väljas.

## Så söks det
För varje söktabell i modellen, i modellens ordning:

- **`ttl` 0 eller utan färsk resultatcache:** direktsökning mot databasen (`searchLiveRows()` med `searchSql()`).
- **Annars:** träffarna tas ur söktabellens resultatcache (`searchCachedRows()`), där namnen poängsätts mot sökordet.

`mode` `simple` söker namn som börjar med sökordet; `intelligent` söker namn som innehåller sökordet och rangordnar med `ts_rank_cd` (prefixmatchning). Vid lika relevans sorteras `sortmode` tom på sista numret i sökfältet (adresser) och `blockunit` på blocknummer före `:` och därefter enhetsnummer efter `:` (fastigheter, till exempel `Julebod 1:2`); alla avslutas med sökfältet. Träffar med samma GID tas bort, träffar med liknande basnamn sprids (`searchDiversifyRows()`), och söktabeller med titelfält fördelar sin gräns jämnt mellan kategorierna (`searchSelectRowsByTitle()`). Till sist kapas den samlade träfflistan proportionellt per `TYPE` till modellens gräns (`searchTrimToLimit()`).

Sökordet och sökvärdena skickas som bundna SQL-parametrar; schema, tabell och kolumner citeras med `pg_escape_identifier()` mot måldatabasens anslutning.

## Resultatcache
Varje söktabell med `ttl` över 0 har en cachefil `results_<id>_<hash>.jsonl` i `sys_get_temp_dir()/origo-search/` (en JSON-rad per tabellrad, hela tabellen).

| Ålder | Beteende |
|---|---|
| Högst `ttl` sekunder | Cachen används |
| Över `ttl` men högst `ttl × maxcacheagefactor` (standard 2) | Cachen används och byggs om efter svaret |
| Äldre eller saknas | Direktsökning; cachen tas bort och byggs om efter svaret |

Cachefilen läses rad för rad. En rad avkodas bara om den innehåller det JSON-escapade sökordet (skiftlägesokänsligt); övriga rader hoppas över, vilket sparar tid och minne. Därefter kontrolleras namnet som vanligt, så träffar och ordning påverkas inte. En skadad rad upptäcks bara om den innehåller sökordet; då ger tabellen inga träffar och cachen byggs om. Frågor som matchar nästan alla rader blir något långsammare än innan.

Ombyggnaden körs efter att svaret skickats (`searchAfterResponse()`, kräver `fastcgi_finish_request()`), ett bygge i taget via `flock` på `results.lock`. Filen skrivs via en temporär fil och `rename()`, så en halvskriven cache läses aldrig.

## Konfigurationscache
Sökningen läser inte konfigurationsdatabasen vid varje anrop. Alla sökmodeller (med färdigupplösta söktabeller och anslutningssträngar) cachas som en PHP-fil som `opcache` kan hålla i minnet. Allt ligger i `sys_get_temp_dir()/origo-search/` (katalog 0700, filer 0600).

| Fil | Innehåll |
|---|---|
| `config.<token>.php` | Själva cachen (`return array(...)`); en ny fil per version, aldrig ändrad på plats |
| `config.current` | Token för den färdigskrivna cache som används |
| `config.desired` | Token för den version som önskas; skiljer den sig från `config.current` är cachen gammal |
| `config.lock` | `flock`-lås; bara en process bygger om cachen i taget, och låset släpps automatiskt om processen dör |
| `config.attempt` | Markör (tidpunkt) för senaste försök, används för att inte försöka igen oftare än var 30:e sekund automatiskt |

- **Första sökningen** (ingen cache): processen väntar högst 5 sekunder på ett pågående bygge, annars bygger den själv via `dbh()`. Går det inte svarar sökningen 503.
- **Gammal cache:** den används vidare (grace) tills en ny är helt skriven. Den första sökningen efter en ändring svarar direkt med den gamla cachen och bygger om efter svaret. Är databasen otillgänglig finns den gamla cachen kvar.
- **Skrivning:** den nya cachen skrivs till en temporär fil som döps om, därefter flyttas pekaren `config.current` på samma sätt, och först sedan tas de gamla filerna bort. Ändras konfigurationen under ett pågående bygge förblir cachen gammal och byggs om igen.
- **Ingen fast giltighetstid.** Cachen markeras gammal när konfigurationen ändras:
  - `manage.php` anropar `searchConfigTableChanged()` efter en lyckad ändring av `searchmodels`, `searchtables` eller `databases`, eller när ett tabell-id byts (direkt eller genom att dess schema eller databas döps om). Det gäller skapa, uppdatera, kopiera, radera, ångra/gör om, återställ och id-byte, och cachen byggs om direkt med den öppna anslutningen.
  - `sql_import.php` markerar och bygger om efter varje lyckad import.
  - Knappen **Töm sökcache** i Verktyg-vyn kör `clearSearchCache.php`, som tar bort alla söktabellers resultatcachar (`searchClearResultCaches()`; de byggs om av nästa sökning) och markerar konfigurationscachen gammal och bygger om den direkt. Misslyckas bygget finns den gamla konfigurationscachen kvar.
  - Ändringar som görs direkt i databasen (utanför verktyget) upptäcks inte; använd knappen.

## Beror på
**Common-funktioner:** `dbh()`, `allFromTable()`, `configTableNames()`, `arrayColumnSearch()`, `pgArrayToPhp()` samt de nya `searchStorageDir()`, `searchWriteFile()`, `searchConfig*()` och `searchTableConfig()` (se common.md)

**Konstanter:** `constants/configSchema.php`

**PHP-tillägg:** `pgsql`, `mbstring`; `opcache` ger snabbast läsning av konfigurationscachen men krävs inte

## Filer och funktioner (`functions/search/`)

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `searchAfterResponse.php` | `searchAfterResponse($task)` | Skickar svaret (`fastcgi_finish_request()`) och kör sedan funktionen; gör ingenting om webbservern inte kan avsluta begäran i förtid |
| `searchBaseName.php` | `searchBaseName($name)` | Tar bort ett avslutande enhets- eller nummertillägg (`JULEBODA 1:2` → `JULEBODA`) |
| `searchCacheAge.php` | `searchCacheAge($tableKey)` | Resultatcachens ålder i sekunder, eller `null` |
| `searchCachedRows.php` | `searchCachedRows($tableConfig, $query, &$needsRefresh)` | Träffar ur söktabellens resultatcache, poängsatta mot sökordet |
| `searchCacheFile.php` | `searchCacheFile($tableKey)` | Sökväg till söktabellens resultatcache; id:t saneras och får ett kort hashvärde så att olika id:n aldrig delar fil |
| `searchCacheMatchScore.php` | `searchCacheMatchScore($name, $query)` | Relevanspoäng för cachade träffar (prefix, ordstart, Levenshtein, ordlikhet) |
| `searchConnect.php` | `searchConnect($connectionString, $usePconnect)` | Öppnar anslutning (UTF8) eller returnerar `false` |
| `searchDedupeByGid.php` | `searchDedupeByGid($rows)` | Tar bort rader som upprepar ett tidigare GID |
| `searchDiversifyRows.php` | `searchDiversifyRows($rows, $limit)` | Sprider träffarna över så många olika basnamn som möjligt |
| `searchGenerateCache.php` | `searchGenerateCache($tableConfig, $dbh)` | Bygger söktabellens resultatcache via temporär fil och `rename()` |
| `searchLiveRows.php` | `searchLiveRows($tableConfig, $query, &$connections)` | Direktsökning; återanvänder anslutningar per anslutningssträng. Returnerar `null` vid anslutnings- eller SQL-fel (loggas) |
| `searchLoadCache.php` | `searchLoadCache($tableKey, $query, &$needsRefresh)` | Generator som ger resultatcachens rader vars rad innehåller sökordet; en saknad eller skadad fil sätter `$needsRefresh` |
| `searchLoadConfig.php` | `searchLoadConfig()` | Konfigurationscachen (`config`, `stale`); bygger den om den saknas, annars HTTP 503 |
| `searchRebuildCaches.php` | `searchRebuildCaches($tableConfigs)` | Bygger om gamla eller saknade resultatcachar, grupperat per anslutning; ett bygge i taget (`flock`) |
| `searchSelectModel.php` | `searchSelectModel($models, $params)` | Väljer modell och sökord ur frågeparametrarna, eller `null` |
| `searchSelectRowsByTitle.php` | `searchSelectRowsByTitle($rows, $limit)` | Fördelar gränsen jämnt över `TITLE`-kategorier och returnerar träffarna grupperade |
| `searchShouldRefreshCache.php` | `searchShouldRefreshCache($tableKey, $ttl, $maxAgeFactor)` | 0 (färsk eller ej använd), 1 (gammal men användbar) eller 2 (saknas eller för gammal) |
| `searchSql.php` | `searchSql($dbh, $query, $options)` | Bygger parameteriserad SQL; frågan `%` hämtar alla rader utan gräns (används vid cachebygge) |
| `searchTrimRows.php` | `searchTrimRows($rows, $limit, $groupByTitle)` | Kapar en söktabells träffar till dess gräns |
| `searchTrimToLimit.php` | `searchTrimToLimit($results, $limit)` | Proportionell kapning per `TYPE` (minst en träff per typ) |

## Begränsningar och risker
- Konfigurationen innehåller anslutningssträngar i klartext (som `databases.connectionstring`) och finns även i konfigurationscachen i `sys_get_temp_dir()`, med åtkomst bara för webbserveranvändaren.
- `search.php` är en publik endpoint men använder den ordinarie konfigurationsanslutningen (`dbh()`); koden läser bara.
- Grace-beteendet kan ge en äldre konfiguration (till exempel en raderad modell) tills den nya cachen är klar.
- Raderas en tabell i konfigurationen fortsätter söktabellen att fungera, eftersom bara tabellens id används, men söktabellen visas inte i "Används av".
- Ett sökord med tecken som är ogiltiga i en `tsquery` (till exempel `&`, `|` eller `:` på fel plats) ger ett SQL-fel i läget `intelligent` vid direktsökning; felet loggas och söktabellen ger inga träffar.
- Samma `customtype` (eller tabellnamn) på flera söktabeller gör att de delar kvot vid den proportionella kapningen.
- Inga automatiska tester finns; SQL och cache kräver PostgreSQL för att verifieras.
