# Utvecklingsplan

Planen samlar framtida arbete för att förenkla, förtydliga och minska redundans i kod och dokumentation. Den beskriver önskat läge och nästa steg, inte hur koden fungerar i dag; verifierat nuläge, begränsningar och säkerhetsrisker finns i modulreferenserna i `docs/www/adm/`. Genomförda punkter tas bort. Principer och arbetssätt finns i `AGENTS.md`.

## Arbetsregler för planen

- Gör ändringar i små, verifierbara steg. Uppdatera kod och berörd modulreferens i samma ändring.
- Ändra inte POST-namn, command-värden, hidden fields eller databasschema utan separat beslut.
- `export.php` finns inte i detta repo. Exportmodulen kan bara dokumenteras, inte ändras, här.
- Nya förbättringsidéer läggs i den här filen, inte som önskelistor i modulreferenserna.

## Fas 1. Dokumentation

- [ ] Avgör om README:s beskrivning av filerna i `constants/` ska länka till `constants.md` i stället för att upprepa dem. README är installationsguiden och `docs/` ingår inte i Docker-avbilden.
- [ ] Dokumentera loader-filerna (`authorization/`, `forwardauth/`, `grouplayerfix/`, `mapstate/`, `updated/`, `restrictedLayer/`) i respektive modulreferens.

## Fas 2. SQL och identifierare

Värden ska alltid bindas med `pg_query_params()`. Tabell- och schemanamn kan inte bindas och ska valideras mot en tillåten uppsättning och citeras.

- [ ] Parametrisera värden i `markMapsChanged`, `markMapUnchanged`, `functions/mapstate/*`, `functions/news/readDelete` och `tableNamesFromSchema`.
- [ ] Validera och citera tabell- och schemanamn i `all_from_table`, `updated_from_table`, `updated_from_table2` och `multiselect.php`.
- [ ] Samla de upprepade `die("Error in SQL query: ...")` i en gemensam hjälpfunktion.
- [ ] Verifiera att `?type=` och `?table=` i `info.php` och `multiselect.php` valideras mot typregistret (se fas 3).

## Fas 3. Target-abstraktionen

- [ ] Inför ett typregister (typ → tabell, id-kolumn, formulärfunktion, singular/plural) som ersätter `.'s'`, `rtrim($x, 's')`, `proj4defs`-undantag och `'print'.ucfirst($type).'Form'`. Registret blir tillåtelselista för dynamiska typer.
- [ ] Byt namn på konverterarna: `makeTargetBasic` → `toBasicTarget` och `makeTargetFull` → `toFullTarget`. Flytta `makeTargetFull`, `targetConfig` och `tableConfigs` till `functions/common/`.
- [ ] Stärk kontraktet: `isTarget` kräver exakt en post, `targetId` validerar, id `"0"` accepteras, `array_key_first()` ersätter `key()`/`current()`.
- [ ] Ersätt de manuella view-grenarna i `manage.php` och direkta `current($fullTarget)`-läsningar med target-helpers.
- [ ] Samla `die()` vid ogiltig target i en gemensam helper.

## Fas 4. `manage.php` delas upp

- [ ] Kommandohandlers för create, copy, delete, restore, undo, redo, update och operation med ett enhetligt resultat (`ok`, `error`, påverkade kartor, historikpost).
- [ ] Transaktionskoordinator för skrivflödet.
- [ ] Separata renderare för vald karta, databas, schema, klass, infogrupp, grupp och barnobjekt.
- [ ] Gemensam builder för metadata-urval (contacts, origins, updates) som i dag upprepas tre gånger.
- [ ] Flytta sidskalet och nyckelordskategori-JavaScript till egna funktioner.

## Fas 5. Gemensamma UI-hjälpare

- [ ] `renderCloseButton()` och en gemensam stängfunktion i JavaScript.
- [ ] `renderIconButton()`; slå ihop undo/redo och add/remove-operation.
- [ ] `jsonForInlineJs()` för JSON i inline-JavaScript och en kort HTML-escape-helper.
- [ ] Gemensam sidhuvudhjälpare för utility-sidorna (skin, stilmall, resize-skript).
- [ ] Delad grundstilmall för knappar i stället för samma regel i flera stilmallar.
- [ ] Konstant för alternativen `f`/`t` i formulären.

## Fas 6. Övrigt

- [ ] `writeConfig.php`: ta bort `extract($configTables)` och ersätt `json_format` med `json_encode()` med pretty print.
- [ ] `info`: ersätt `strftime()` (borttagen i PHP 9) och dela upp `printUniqueLogins`.
- [ ] Ta bort kod utan anropare efter verifiering: `getResponseContentType`, `getGraphToken`.
- [ ] CI: kör `php -l` över `finalfs/www/adm` och lägg till ett smoke-test mot PostgreSQL för import, CRUD och ångra/gör om.
- [ ] CSRF för mutationer i `manage.php` och `news.php` (ändrar POST-kontraktet; kräver beslut).

## Modulbacklog

Åtgärder som tidigare stod i modulreferenserna. Risker och begränsningar beskrivs fortfarande där.

- **manage:** förenkla upprepad schema-/tabellvisning (`printHeadForm` och `printChildSelect` har var sin prefix-strippning av föräldrans id); bryt ut delad QGIS-metadatahantering (`qgisProjectMetadata`) som även `info.php` och `writeTablesForAllLayers.php` behöver; utred om `_viewDepth`-villkoret för radering är avsiktligt; lägg till null-kontroll i `initMessageListener.js` och ersätt den hopkodade `<textareaId>::<tabell>:<värden>`-strängen med separata data-attribut; validera fler fält i `validateUpdate()` (till exempel JSON-fält) och låt `updatedFullTarget()` bevara fält som inte postats; ersätt `exit(1)` utan meddelande i `tableConfigs()`; låt `markMapsChanged()` använda parametrar.
- **writeConfig:** utvärdera `fixDuplicateDeclarations` (radbaserad JavaScript-parser); slå ihop `renderCssTags` och `renderJavaScriptTags`; bryt ut en `buildLegendUrl` utan att ändra värdena (kräver visuell verifiering); städa oanvända delar.
- **info:** bryt ut källdetaljer och AD-användardetaljer ur `info.php`; ersätt upprepad inloggningsräkning med en funktion.
- **forwardauth:** flytta organisationsdomänen (`isSafeReturnTo`, fallback i `azure-callback.php`) till en konstant; rätta docblock i `getOnPremisesSamAccountName`; ta bort eller styr utkommenterade debugblock.
- **authorization:** utred den vidarebefordrade `call`-parametern; bryt ut dubblerad logik för nyhets-iframens URL; läs in `adldap2` först när LDAP används.
- **multiselect:** använd `$configSchema` i stället för hårdkodat schema; ersätt den hopkodade `table`-parametern med separata parametrar; flytta inline-`onclick` till en namngiven funktion.
- **restrictedLayer:** lägg till `isset()`-kontroller för frågeparametrar; använd eller ta bort `$cause` i `finishError500`; kontrollera om `EMPTYPNG`/`LOCKPNG` finns i driftmiljön; överväg tydligare filnamn för `authorization_filter`.
- **grouplayerfix:** rätta `$DEFAULT_QGIS_SERVER_PATH`/`_URL` och operatorprioritet i `getCachedProjectSettings`; ta bort dubblerade kodblock; harmonisera retry med `fetchWithStatus`; samla curl-inställningar.
- **mapstate:** bekräfta att anonym åtkomst och öppen CORS är avsiktliga; använd `random_bytes()` för UUID.
- **read_json:** smoke-testa mot en riktig PostgreSQL med specialtecken i titlar, URL:er och beskrivningar samt rollback efter databasfel.
- **read_db_schemas:** utred skydd för `databases.connectionstring` och syftet med `unset($_GET)`.
- **news:** ta bort död kod i `pgNewsArray` och `selectNew`; begränsa `includeDirectory("./functions/common")` till nödvändiga filer.
- **writeTablesForAllLayers:** överväg batchning och framsteg om timeout uppstår vid många lager.
- **export:** städning och väntetider kan bara planeras efter verifiering i den organisationsspecifika implementationen.
