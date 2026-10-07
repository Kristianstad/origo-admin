# Gemensamma funktioner (common)

**Plats:** `adm/functions/common/`
**Laddas av:** samtliga huvudfiler i `adm/`, via `includeDirectory()` (se ARCHITECTURE.md)

> Tabellen är sorterad alfabetiskt efter funktionsnamn för att göra
> det lätt att slå upp en specifik funktion. Se mappen för fullständig
> lista över vad som finns.

| Funktion | Fil | Beskrivning | Används av |
|---|---|---|---|
| `allFromTable($dbh, $schema, $table)` | `allFromTable.php` | Hämtar alla rader från en tillåten objekttabell i konfigurationsschemat; citerar schema- och tabellnamn | info, multiselect, read_db_schemas, export, writeTablesForAllLayers |
| `arrayColumnSearch($value, $column, $rows)` | `arrayColumnSearch.php` | Hittar första raden där given kolumn matchar värdet | info, read_db_schemas, export, writeConfig, manage, writeTablesForAllLayers |
| `assocArrayValues($array)` | `assocArrayValues.php` | Kontrollerar/hämtar faktiska värden i nästlad associativ array | info, manage |
| `booleanSelectOptions(): array` | `booleanSelectOptions.php` | Returnerar select-alternativen `f => ☐ Nej` och `t => ☑ Ja` | manage |
| `clearAuthSession()` | `clearAuthSession.php` | Nollställer `$_SESSION['user']`, sätter en ny `login_time_stamp` och stänger sessionen. Anropas av `initUserLdap()` när ingen giltig användare kan slås upp | authorization (indirekt via initUserLdap) |
| `configTableNames($dbh)` | `configTableNames.php` | Cacherar bastabeller i konfigurationsschemat som har förväntad id-kolumn; utesluter `object_identity` och `edit_cursor` | allFromTable, configTables, info, multiselect, updated |
| `configTables($dbh)` | `configTables.php` | Hämtar konfigurationen från tillåtna objekttabeller i ett svep, avsedd att packas upp med `extract()` | writeConfig, manage, read_json |
| `dbh($connectionString=null)` | `dbh.php` | Öppnar PostgreSQL-anslutning. Utan argument: standarddatabasen. Med anslutningssträng: godtycklig extern databas | news, mapstate, info, authorization, read_db_schemas, export, writeConfig, manage, read_schema_tables, updated, help, writeTablesForAllLayers |
| `defineFileConstant($name, $value)` | `defineFileConstant.php` | Skriver en PHP-konstant till fil, läsbar via `includeFileConstant()`. Källan till `RESTRICTEDLAYERS`-konstanten | writeConfig |
| `escapeHtml(string $value): string` | `escapeHtml.php` | Escapar text för HTML-text eller attribut med UTF-8 och citerade attributtecken | gemensamma renderers, manage |
| `ensureSessionWritable()` | `ensureSessionWritable.php` | Säkerställer att sessionen är öppen/skrivbar | authorization, forwardauth |
| `findAllParents($dbh, $child)` | `findAllParents.php` | Rekursivt: alla objekt som refererar till ett givet objekt | info, manage |
| `findParents($tableToRemoveFrom, $target)` | `findParents.php` | Icke-rekursiv variant: hittar direkta föräldrar | manage (printRemoveOperation) |
| `getCookieOptions($expiryTimestamp)` | `getCookieOptions.php` | Bygger cookie-inställningar (path/domain/secure/httponly/samesite) | authorization |
| `includeFileConstant($name)` | `includeFileConstant.php` | Läser en fil-konstant skriven av `defineFileConstant()` | restrictedLayer |
| `initUserLdap($dbh)` | `initUserLdap.php` | Initierar användarinfo via LDAP (`$authMethod==='ldap'`). **Bieffekt:** skriver och stänger sessionen | news, authorization, export, restrictedLayer |
| `insertIdSql($id, $tableName)` | `insertIdSql.php` | Bygger en parameteriserad `INSERT`-sats som skapar en ny rad med angivet id som primärnyckel, övriga kolumner tomma; returnerar SQL och parametrar | manage (postButton-kommandona `create`/`copy`) |
| `invalidTarget($functionName): never` | `invalidTarget.php` | Avslutar med ett gemensamt felmeddelande när target-form eller target-id är ogiltigt | target-hjälpare, manage |
| `isBasicTarget($target)` | `isBasicTarget.php` | Kontrollerar om en target är "basic" (värdet är en sträng/id) snarare än "full" (värdet är en config-array). Bygger på `isTarget()` | manage (target-infrastruktur) |
| `isFullTarget($target)` | `isFullTarget.php` | Kontrollerar om en target är "full" (värdet är en config-array) snarare än "basic" | manage, target-infrastruktur |
| `isIdUniqueInTable($id, $tablePkColumn, $table)` | `isIdUniqueInTable.php` | Kontrollerar att ett id inte redan finns bland värdena i tabellens primärnyckelkolumn | manage (validering vid skapande) |
| `isTarget($target)` | `isTarget.php` | Kontrollerar att target är en array med exakt en icke-tom strängnyckel (se manage.md) | manage |
| `jsonForInlineJs(mixed $value): string` | `jsonForInlineJs.php` | JSON-kodar ett värde för säker inbäddning i JavaScript, med HTML-känsliga tecken hex-kodade | manage och verktygssidor |
| `makeBasicTarget($type, $id)` | `makeBasicTarget.php` | Skapar en basic target `[$type => $id]` från icke-tomma strängar; id-värdet `'0'` är tillåtet | info, manage, target-infrastruktur |
| `makeFullTarget($type, $config)` | `makeFullTarget.php` | Skapar en full target `[$type => $config]` från en konfigurationsrad | info, manage, target-infrastruktur |
| `mbUcfirst($str)` | `mbUcfirst.php` | Multibyte-säker `ucfirst()` (via `mb_strtoupper()`/`mb_substr()`, kräver `mbstring`-tillägget) – PHP:s vanliga `ucfirst()` är byte-baserad och versaliserar bara ASCII a-z, vilket missar svenska ord som börjar på å/ä/ö | printHeadForms, multiselect (används runt `toSwedish()`-resultat, där ett svenskt ord kan börja på å/ä/ö) |
| `pgArrayToPhp($pgArray)` | `pgArrayToPhp.php` | Konverterar Postgres arraysyntax (`{a,b,c}`) till PHP-array | news, export, writeConfig |
| `pkColumnOfTable($table)` | `pkColumnOfTable.php` | Returnerar primärnyckelns kolumnnamn för en tabell | info, writeTablesForAllLayers, target-infrastruktur (targetId, targetIdColumn via targetTable), validateUpdate |
| `qualifiedTableIdentifier($dbh, $tableWithSchema)` | `qualifiedTableIdentifier.php` | Kontrollerar att `schema.tabell` är en bastabell i den anslutna databasen och returnerar citerade identifierare; annars `false` | manage (updatedFromTable), updated |
| `readAndCloseSession()` | `readAndCloseSession.php` | Läser in `$_SESSION` och stänger sessionen | news, export |
| `renderCloseButton(string $class=''): string` | `renderCloseButton.php` | Renderar den gemensamma Stäng-knappen; anropar `closeTopFrame()` från `js-functions/common/` | help, info, multiselect, read_json, sql_import |
| `renderIconButton(array $button, string $icon, ?string $caption=null, string $iconClass=''): string` | `renderIconButton.php` | Renderar en escaped ikonknapp; formulär och POST-värden ägs av anroparen | manage (historik och add/remove) |
| `renderUtilityHead(array $skin, string $styleName): void` | `renderUtilityHead.php` | Skriver skinvariabler, `styles/common.css` följd av sidans stylesheet, samt gemensamma utility-skript. Tillåtna stilmallar är help, info, multiselect, read_json och sql_import | help, info, multiselect, read_json, sql_import |
| `setTargetConfigParam(&$fullTarget, $configParam, $value)` | `setTargetConfigParam.php` | Ändrar ett konfigurationsvärde i en full target in-memory | manage, target-infrastruktur |
| `sqlQueryError($dbh)` | `sqlQueryError.php` | Avslutar vid SQL-fel med PostgreSQLs felmeddelande | allFromTable, configTableNames, markMapsChanged, markMapUnchanged, schemaNamesFromDb, tableNamesFromSchema, updated-funktioner |
| `tableConfigs($table, $configTablesOrDbh)` | `tableConfigs.php` | Hämtar rader för en konfigurationstabell ur en databaskoppling eller en redan laddad tabellarray | manage (target-uppslag) |
| `tableNamesFromSchema($dbh, $schema)` | `tableNamesFromSchema.php` | Listar bastabellnamn i ett databasschema; binder schemanamnet som SQL-värde | read_schema_tables |
| `tableType($table)` | `tableType.php` | Returnerar typen för tabellnamnet genom att ta bort ett avslutande `s` (`layers` → `layer`) | manage, target-infrastruktur |
| `tablesFromQgsXml($qgsXml, $layerName=null, $tables=[], $subtree=null)` | `tablesFromQgsXml.php` | Läser PostGIS-tabeller ur en QGIS-projekts XML-lagerträd; används av manage och writeTablesForAllLayers | manage, writeTablesForAllLayers |
| `targetConfig($target, $configTablesOrDbh=null)` | `targetConfig.php` | Returnerar konfigurationsraden för en full target eller slår upp den för en basic target | manage (target-uppslag) |
| `targetConfigParam($fullTarget, $configParam)` | `targetConfigParam.php` | Läser ett konfigurationsvärde från en full target | info, manage, target-infrastruktur |
| `targetId($target)` | `targetId.php` | Validerar och returnerar id-värdet som en icke-tom sträng; `'0'` är giltigt | info, manage, target-infrastruktur |
| `targetIdColumn($target)` | `targetIdColumn.php` | Returnerar targetens primärnyckelkolumn, inklusive specialfallet `proj4defs` → `code` | info, manage, target-infrastruktur |
| `targetTable($target)` | `targetTable.php` | Returnerar konfigurations-tabellen för targetens typ | info, manage, target-infrastruktur |
| `targetType($target)` | `targetType.php` | Returnerar typen (nyckeln) för ett target | manage |
| `typeTableName($type)` | `typeTableName.php` | Returnerar tabellnamnet för en typ genom att lägga till `s` | manage, target-infrastruktur |
| `toBasicTarget($target)` | `toBasicTarget.php` | Konverterar en full target till en basic target | manage, parent-traversering |
| `toFullTarget($target, $configTablesOrDbh)` | `toFullTarget.php` | Konverterar en basic target till en full target genom att slå upp dess konfigurationsrad; en redan full target behålls | manage |
| `toSwedish($string)` | `toSwedish.php` | Översätter interna typ-/kolumnnamn till svenska för visning | info, multiselect, printCopyButton, printDeleteButton, printAddOperation, printRemoveOperation, printHeadForm/Forms, validateUpdate |
| `usedInMaps($dbh, $target, $checkedTargets=[], $usedInMaps=[])` | `usedInMaps.php` | Rekursivt: hittar alla kartor (`map`-target) som ett givet target ytterst ingår i, via `findAllParents()`. Håller reda på redan besökta targets för att undvika oändlig rekursion vid cirkulära referenser | manage (avgör vilka kartor som ska markeras `changed` vid en ändring) |
| `includeDirectory($path)` | *(i `adm/functions/`, ej i `common/` — placerad sist, utanför den alfabetiska listan, eftersom den fysiskt hör hemma på en annan plats)* | Laddar alla `.php`-filer i angiven mapp med `require_once` | samtliga moduler |

> `updatedFromTable()` ligger i `functions/manage/`; se manage.md.

`styles/common.css` innehåller gemensamma knappregler, typografi- och
bakgrundsbas för `body`, scrollbarernas utseende samt textmarkering. Den laddas
före sidans egen stilmall av `renderUtilityHead()` och före
`manage.css` i `manage.php`. `renderUtilityHead()` laddar också
`js-functions/common/` och anropar `resizeParentFrame()` för att registrera
iframe-aviseringen; `closeTopFrame()` anropas av Stäng-knappen.
