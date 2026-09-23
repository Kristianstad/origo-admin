# Gemensamma funktioner (common)

**Plats:** `adm/functions/common/`
**Laddas av:** samtliga huvudfiler i `adm/`, via `includeDirectory()` (se ARCHITECTURE.md)

> Tabellen är sorterad alfabetiskt efter funktionsnamn för att göra
> det lätt att slå upp en specifik funktion. Se mappen för fullständig
> lista över vad som finns.

| Funktion | Fil | Beskrivning | Används av |
|---|---|---|---|
| `all_from_table($dbh, $schema, $table)` | `all_from_table.php` | Hämtar alla rader från angiven tabell | info, multiselect (⚠️ hårdkodar schema `map_configs`), read_db_schemas, export, writeTablesForAllLayers |
| `array_column_search($value, $column, $rows)` | `array_column_search.php` | Hittar första raden där given kolumn matchar värdet | info, read_db_schemas, export, writeConfig, manage, writeTablesForAllLayers |
| `assoc_array_values($array)` | `assoc_array_values.php` | Kontrollerar/hämtar faktiska värden i nästlad associativ array | info, manage |
| `clearAuthSession()` | `clearAuthSession.php` | Nollställer `$_SESSION['user']`, sätter en ny `login_time_stamp` och stänger sessionen. Anropas av `initUserLdap()` när ingen giltig användare kan slås upp | authorization (indirekt via initUserLdap) |
| `configTables($dbh)` | `configTables.php` | Hämtar samtliga konfigtabeller i ett svep, avsedd att packas upp med `extract()` | writeConfig, manage, read_json |
| `dbh($connectionString=null)` | `dbh.php` | Öppnar PostgreSQL-anslutning. Utan argument: standarddatabasen. Med anslutningssträng: godtycklig extern databas | news, mapstate, info, authorization, read_db_schemas, export, writeConfig, manage, read_schema_tables, updated, help, writeTablesForAllLayers |
| `defineFileConstant($name, $value)` | `defineFileConstant.php` | Skriver en PHP-konstant till fil, läsbar via `includeFileConstant()`. Källan till `RESTRICTEDLAYERS`-konstanten | writeConfig |
| `ensureSessionWritable()` | `ensureSessionWritable.php` | Säkerställer att sessionen är öppen/skrivbar | authorization, forwardauth |
| `findAllParents($dbh, $child)` | `findAllParents.php` | Rekursivt: alla objekt som refererar till ett givet objekt | info, manage |
| `findParents($tableToRemoveFrom, $target)` | `findParents.php` | Icke-rekursiv variant: hittar direkta föräldrar | manage (printRemoveOperation) |
| `getCookieOptions($expiryTimestamp)` | `getCookieOptions.php` | Bygger cookie-inställningar (path/domain/secure/httponly/samesite) | authorization |
| `includeFileConstant($name)` | `includeFileConstant.php` | Läser en fil-konstant skriven av `defineFileConstant()` | restrictedLayer |
| `initUserLdap($dbh)` | `initUserLdap.php` | Initierar användarinfo via LDAP (`$authMethod==='ldap'`). **Bieffekt:** skriver och stänger sessionen | news, authorization, export, restrictedLayer |
| `insertIdSql($id, $tableName)` | `insertIdSql.php` | Bygger en parameteriserad `INSERT`-sats som skapar en ny rad med angivet id som primärnyckel, övriga kolumner tomma; returnerar SQL och parametrar | manage (postButton-kommandona `create`/`copy`) |
| `isBasicTarget($target)` | `isBasicTarget.php` | Kontrollerar om en target är "basic" (värdet är en sträng/id) snarare än "full" (värdet är en config-array). Bygger på `isTarget()` | manage (target-infrastruktur) |
| `isFullTarget($target)` | `isFullTarget.php` | Kontrollerar om en target är "full" (värdet är en config-array) snarare än "basic" | manage, target-infrastruktur |
| `isIdUniqueInTable($id, $tablePkColumn, $table)` | `isIdUniqueInTable.php` | Kontrollerar att ett id inte redan finns bland värdena i tabellens primärnyckelkolumn | manage (validering vid skapande) |
| `isTarget($target)` | `isTarget.php` | Kontrollerar om en variabel har formen av ett giltigt "target" (se manage.md) | manage |
| `makeBasicTarget($type, $id)` | `makeBasicTarget.php` | Skapar en basic target `[$type => $id]` | info, manage, target-infrastruktur |
| `makeFullTarget($type, $config)` | `makeFullTarget.php` | Skapar en full target `[$type => $config]` från en konfigurationsrad | info, manage, target-infrastruktur |
| `makeTargetBasic($target)` | `makeTargetBasic.php` | Konverterar en full target till en basic target | manage (används flitigt i samtliga print*Form) |
| `mbUcfirst($str)` | `mbUcfirst.php` | Multibyte-säker `ucfirst()` (via `mb_strtoupper()`/`mb_substr()`, kräver `mbstring`-tillägget) – PHP:s vanliga `ucfirst()` är byte-baserad och versaliserar bara ASCII a-z, vilket missar svenska ord som börjar på å/ä/ö | printHeadForms, multiselect (används runt `toSwedish()`-resultat, där ett svenskt ord kan börja på å/ä/ö) |
| `pgArrayToPhp($pgArray)` | `pgArrayToPhp.php` | Konverterar Postgres arraysyntax (`{a,b,c}`) till PHP-array | news, export, writeConfig |
| `pkColumnOfTable($table)` | `pkColumnOfTable.php` | Returnerar primärnyckelns kolumnnamn för en tabell | info, writeTablesForAllLayers, target-infrastruktur (targetId, targetIdColumn via targetTable), validateUpdate |
| `readAndCloseSession()` | `readAndCloseSession.php` | Läser in `$_SESSION` och stänger sessionen | news, export |
| `setTargetConfigParam(&$fullTarget, $configParam, $value)` | `setTargetConfigParam.php` | Ändrar ett konfigurationsvärde i en full target in-memory | manage, target-infrastruktur |
| `tableNamesFromSchema($dbh, $schema)` | `tableNamesFromSchema.php` | Listar tabellnamn i ett databasschema | read_schema_tables |
| `tableType($table)` | `tableType.php` | Returnerar typen för tabellnamnet genom att ta bort ett avslutande `s` (`layers` → `layer`) | manage, target-infrastruktur |
| `tablesFromQgsXml($qgsXml, $layerName=null, $tables=[], $subtree=null)` | `tablesFromQgsXml.php` | Läser PostGIS-tabeller ur ett QGIS-projekts XML-lagerträd; används av manage och writeTablesForAllLayers | manage, writeTablesForAllLayers |
| `targetConfigParam($fullTarget, $configParam)` | `targetConfigParam.php` | Läser ett konfigurationsvärde från en full target | info, manage, target-infrastruktur |
| `targetId($target)` | `targetId.php` | Returnerar targetens id-värde, oavsett om targeten är basic eller full | info, manage, target-infrastruktur |
| `targetIdColumn($target)` | `targetIdColumn.php` | Returnerar targetens primärnyckelkolumn, inklusive specialfallet `proj4defs` → `code` | info, manage, target-infrastruktur |
| `targetTable($target)` | `targetTable.php` | Returnerar konfigurations-tabellen för targetens typ | info, manage, target-infrastruktur |
| `targetType($target)` | `targetType.php` | Returnerar typen (nyckeln) för ett target | manage |
| `typeTableName($type)` | `typeTableName.php` | Returnerar tabellnamnet för en typ genom att lägga till `s` | manage, target-infrastruktur |
| `toSwedish($string)` | `toSwedish.php` | Översätter interna typ-/kolumnnamn till svenska för visning | info, multiselect, printCopyButton, printDeleteButton, printAddOperation, printRemoveOperation, printHeadForm/Forms, validateUpdate |
| `usedInMaps($dbh, $target, $checkedTargets=[], $usedInMaps=[])` | `usedInMaps.php` | Rekursivt: hittar alla kartor (`map`-target) som ett givet target ytterst ingår i, via `findAllParents()`. Håller reda på redan besökta targets för att undvika oändlig rekursion vid cirkulära referenser | manage (avgör vilka kartor som ska markeras `changed` vid en ändring) |
| `includeDirectory($path)` | *(i `adm/functions/`, ej i `common/` — placerad sist, utanför den alfabetiska listan, eftersom den fysiskt hör hemma på en annan plats)* | Laddar alla `.php`-filer i angiven mapp med `require_once` | samtliga moduler |

> **Not:** `updated_from_table()` beskrevs tidigare felaktigt här som en
> common-funktion. Den ligger i `functions/manage/` — se manage.md. Samma
> gäller `targetConfig()`, som också hör till `functions/manage/`.
