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
| `currentSkin(array $skins): array` | `currentSkin.php` | Väljer skin från cookien `origo_admin_skin` om id:t finns bland tillgängliga skins; annars returneras en tom array | help, info, manage, multiselect, read_json, sql_import |
| `dbh($connectionString=null)` | `dbh.php` | Öppnar PostgreSQL-anslutning. Utan argument: standarddatabasen. Med anslutningssträng: godtycklig extern databas | news, mapstate, info, authorization, read_db_schemas, export, writeConfig, manage, read_schema_tables, updated, help, writeTablesForAllLayers |
| `defineFileConstant($name, $value)` | `defineFileConstant.php` | Skriver en PHP-konstant till fil, läsbar via `includeFileConstant()`. Källan till `RESTRICTEDLAYERS`-konstanten | writeConfig |
| `escapeHtml(string $value): string` | `escapeHtml.php` | Escapar text för HTML-text eller attribut med UTF-8 och citerade attributtecken | gemensamma renderers, manage |
| `ensureSessionWritable()` | `ensureSessionWritable.php` | Säkerställer att sessionen är öppen/skrivbar | authorization, forwardauth |
| `findAllParents($dbh, $child)` | `findAllParents.php` | Rekursivt: alla objekt som refererar till ett givet objekt | info, manage |
| `findParents($tableToRemoveFrom, $target)` | `findParents.php` | Icke-rekursiv variant: hittar direkta föräldrar | manage (printRemoveOperation) |
| `generateCsrfToken(): string` | `generateCsrfToken.php` | Säkerställer en skrivbar session och skapar eller återanvänder dess CSRF-token | read_json, sql_import |
| `getCookieOptions($expiryTimestamp)` | `getCookieOptions.php` | Bygger cookie-inställningar (path/domain/secure/httponly/samesite) | authorization |
| `includeFileConstant($name)` | `includeFileConstant.php` | Läser en fil-konstant skriven av `defineFileConstant()` | restrictedLayer |
| `initUserLdap($dbh)` | `initUserLdap.php` | Initierar användarinfo via LDAP (`$authMethod==='ldap'`). **Bieffekt:** skriver och stänger sessionen | news, authorization, export, restrictedLayer |
| `insertIdSql($id, $tableName)` | `insertIdSql.php` | Bygger en parameteriserad `INSERT`-sats som skapar en ny rad med angivet id som primärnyckel, övriga kolumner tomma; returnerar SQL och parametrar | manage (postButton-kommandona `create`/`copy`) |
| `invalidTarget($functionName): never` | `invalidTarget.php` | Avslutar med ett gemensamt felmeddelande när target-form eller target-id är ogiltigt | target-hjälpare, manage |
| `isBasicTarget($target)` | `isBasicTarget.php` | Kontrollerar om en target är "basic" (värdet är en sträng/id) snarare än "full" (värdet är en config-array). Bygger på `isTarget()` | manage (target-infrastruktur) |
| `isFullTarget($target)` | `isFullTarget.php` | Kontrollerar om en target är "full" (värdet är en config-array) snarare än "basic" | manage, target-infrastruktur |
| `isHostInDomain(string $host, string $domain): bool` | `isHostInDomain.php` | Kontrollerar att ett värdnamn är domänen själv eller en underdomän till den (skiftlägesokänsligt). En tom domän ger alltid `false` | forwardauth (`isSafeReturnTo`), export |
| `isIdUniqueInTable($id, $tablePkColumn, $table)` | `isIdUniqueInTable.php` | Kontrollerar att ett id inte redan finns bland värdena i tabellens primärnyckelkolumn | manage (validering vid skapande) |
| `isTarget($target)` | `isTarget.php` | Kontrollerar att target är en array med exakt en icke-tom strängnyckel (se manage.md) | manage |
| `jsonForInlineJs(mixed $value): string` | `jsonForInlineJs.php` | JSON-kodar ett värde för säker inbäddning i JavaScript, med HTML-känsliga tecken hex-kodade | manage och verktygssidor |
| `makeBasicTarget($type, $id)` | `makeBasicTarget.php` | Skapar en basic target `[$type => $id]` från icke-tomma strängar; id-värdet `'0'` är tillåtet | info, manage, target-infrastruktur |
| `makeFullTarget($type, $config)` | `makeFullTarget.php` | Skapar en full target `[$type => $config]` från en konfigurationsrad | info, manage, target-infrastruktur |
| `mbUcfirst($str)` | `mbUcfirst.php` | Multibyte-säker `ucfirst()` (via `mb_strtoupper()`/`mb_substr()`, kräver `mbstring`-tillägget) – PHP:s vanliga `ucfirst()` är byte-baserad och versaliserar bara ASCII a-z, vilket missar svenska ord som börjar på å/ä/ö | printHeadForms, multiselect (används runt `toSwedish()`-resultat, där ett svenskt ord kan börja på å/ä/ö) |
| `pgArrayToPhp($pgArray)` | `pgArrayToPhp.php` | Konverterar Postgres arraysyntax (`{a,b,c}`) till PHP-array | news, export, writeConfig |
| `pkColumnOfTable($table)` | `pkColumnOfTable.php` | Returnerar primärnyckelns kolumnnamn för en tabell | info, writeTablesForAllLayers, target-infrastruktur (targetId, targetIdColumn via targetTable), validateUpdate |
| `printSkinVariables(array $skin): void` | `printSkinVariables.php` | Skriver tillgängliga skinfärger och typografi som CSS custom properties under `:root` | manage, utility-sidor via renderUtilityHead |
| `qualifiedTableIdentifier($dbh, $tableWithSchema)` | `qualifiedTableIdentifier.php` | Kontrollerar att `schema.tabell` är en bastabell i den anslutna databasen och returnerar citerade identifierare; annars `false` | manage (updatedFromTable), updated |
| `readAndCloseSession()` | `readAndCloseSession.php` | Läser in `$_SESSION` och stänger sessionen | news, export |
| `renderCloseButton(string $class=''): string` | `renderCloseButton.php` | Renderar den gemensamma Stäng-knappen; anropar `closeTopFrame()` från `js-functions/common/` | help, info, multiselect, read_json, sql_import |
| `renderIconButton(array $button, string $icon, ?string $caption=null, string $iconClass=''): string` | `renderIconButton.php` | Renderar en escaped ikonknapp; formulär och POST-värden ägs av anroparen | manage (historik och add/remove) |
| `renderUtilityHead(array $skin, string $styleName): void` | `renderUtilityHead.php` | Skriver skinvariabler, `styles/common.css` följd av sidans stylesheet, samt gemensamma utility-skript. Tillåtna stilmallar är help, info, multiselect, read_json och sql_import | help, info, multiselect, read_json, sql_import |
| `searchConfigFromDb($dbh)` | `searchConfigFromDb.php` | Bygger alla sökmodeller med färdigupplösta söktabeller ur `searchmodels`, `searchtables` och `databases`; `null` om tabellerna saknas | search (konfigurationscachen) |
| `searchConfigMarkStale()` | `searchConfigMarkStale.php` | Markerar sökkonfigurationens cache som gammal (ny önskad token); den gamla cachen används tills en ny är klar | search, manage, sql_import |
| `searchConfigRead()` | `searchConfigRead.php` | Läser konfigurationscachen: `array('config', 'stale')` eller `null` om ingen användbar cache finns | search |
| `searchConfigRefresh($dbh=null, $waitSeconds=0, $honorBackoff=false)` | `searchConfigRefresh.php` | Bygger om konfigurationscachen om den saknas eller är gammal. Ett bygge i taget (`flock`), atomisk skrivning och pekarbyte, gamla filer tas bort först när den nya är klar. Returnerar `true` om cachen är aktuell efteråt | search, manage, sql_import, clearSearchConfigCache |
| `searchConfigTableChanged($dbh, $table, $renames=[])` | `searchConfigTableChanged.php` | Markerar och bygger om konfigurationscachen om `$table` är `searchmodels`, `searchtables` eller `databases`, eller om `$renames` (från `idRenames()`) innehåller ett tabell-id-byte | manage |
| `searchConfigToken($name)` | `searchConfigToken.php` | Läser versionstoken (`desired` eller `current`) för konfigurationscachen; ogiltiga värden ger `null` | search (konfigurationscachen) |
| `searchStorageDir()` | `searchStorageDir.php` | Katalogen för sökmodulens cachar (`sys_get_temp_dir()/origo-search/`, läge 0700), skapas vid behov | search |
| `searchTableConfig($searchtable, $databases, $modelLimit, $liveRows)` | `searchTableConfig.php` | Normaliserar en `searchtables`-rad (databas, schema och tabellnamn härleds ur `table`-id:t; anslutning från `databases`) till sökmodulens inställningar; `null` och loggning om raden är oanvändbar | search (konfigurationscachen) |
| `searchWriteFile($path, $contents)` | `searchWriteFile.php` | Skriver en fil via temporär fil i samma katalog och `rename()`, så att läsare aldrig ser en halvskriven fil | search (konfigurationscachen) |
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
| `topFormHelpButton(string $helpId): string` | `topFormHelpButton.php` | Renderar en hjälpknapp för importformulär och lägger till tooltip-JS en gång per request | read_json, sql_import |
| `toPgArrayLiteral(array $items): string` | `toPgArrayLiteral.php` | Escapar strängar till en citerad PostgreSQL-arrayliteral som kan skickas som bunden SQL-parameter | news, read_json |
| `toSwedish($string)` | `toSwedish.php` | Översätter interna typ-/kolumnnamn till svenska för visning | info, multiselect, printCopyButton, printDeleteButton, printAddOperation, printRemoveOperation, printHeadForm/Forms, validateUpdate |
| `usedInMaps($dbh, $target, $checkedTargets=[], $usedInMaps=[])` | `usedInMaps.php` | Rekursivt: hittar alla kartor (`map`-target) som ett givet target ytterst ingår i, via `findAllParents()`. Håller reda på redan besökta targets för att undvika oändlig rekursion vid cirkulära referenser | manage (avgör vilka kartor som ska markeras `changed` vid en ändring) |
| `validateCsrfToken(?string $token): bool` | `validateCsrfToken.php` | Säkerställer en skrivbar session och jämför token mot sessionens värde med `hash_equals()` | read_json, sql_import |
| `includeDirectory($path)` | *(i `adm/functions/`, ej i `common/` — placerad sist, utanför den alfabetiska listan, eftersom den fysiskt hör hemma på en annan plats)* | Laddar alla `.php`-filer i angiven mapp med `require_once` | samtliga moduler |

> `updatedFromTable()` ligger i `functions/manage/`; se manage.md.

`styles/common.css` innehåller gemensamma knappregler, typografi- och
bakgrundsbas för `body`, scrollbarernas utseende samt textmarkering. Den laddas
före sidans egen stilmall av `renderUtilityHead()` och före
`manage.css` i `manage.php`. `renderUtilityHead()` laddar också
`js-functions/common/` och anropar `resizeParentFrame()` för att registrera
iframe-aviseringen; `closeTopFrame()` anropas av Stäng-knappen.
