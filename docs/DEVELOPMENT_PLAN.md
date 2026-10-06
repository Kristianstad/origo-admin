# Utvecklingsplan

Planen samlar framtida arbete för att förenkla, förtydliga och minska redundans i kod och dokumentation. Den beskriver önskat läge och nästa steg, inte hur koden fungerar i dag; verifierat nuläge, begränsningar och säkerhetsrisker finns i modulreferenserna i `docs/www/adm/`. Genomförda punkter tas bort. Principer och arbetssätt finns i `AGENTS.md`.

## Arbetsregler för planen

- Gör ändringar i små, verifierbara steg. Uppdatera kod och berörd modulreferens i samma ändring.
- Ändra inte POST-namn, command-värden, hidden fields eller databasschema utan separat beslut.
- `export.php` finns inte i detta repo. Exportmodulen kan bara dokumenteras, inte ändras, här.
- Nya förbättringsidéer läggs i den här filen, inte som önskelistor i modulreferenserna.
- Återstående faser körs i ordningen 3, 5, 4, 6 och 7. Varje fas gås igenom i detalj med ägaren innan implementationen börjar, så upplägget kan ändras.
- Nya och ändrade funktioner bör få parameter- och returtyper i vanligt läge (ingen bred omskrivning), men typning är en riktlinje och får vika om den försvårar förenkling eller generalisering av koden. Använd nullbara typer (`?string`) eller `mixed` där databasen kan ge `NULL`, och unionstyper som `array|false` där en funktion kan returnera `false`.

## Fas 3. Target-abstraktionen

Typ, tabell och id-kolumn följer av tre regler som ska finnas på ett ställe. Ett separat typregister behövs inte.

- [ ] Låt `tableType()` ta bort exakt ett avslutande `s` (i dag tar `rtrim` bort alla), och ersätt alla `rtrim($x, 's')` med `tableType()`: `pkColumnOfTable`, `printParents`, `printAddRemoveOperations`, `printChildSelect`, `printHeadForm`, `printHeadForms` och `multiselect.php`.
- [ ] Låt `targetIdColumn()` och `multiselect.php` anropa `pkColumnOfTable()` i stället för att upprepa `proj4defs`-undantaget.
- [ ] Ersätt `$type.'s'` i `sqlForOperation` och `findParents` med `typeTableName()`.
- [ ] Kontrollera dispatchen `'print'.ucfirst($type).'Form'` i `manage.php` med `function_exists()` efter att typen validerats mot tillåtelselistan.
- [ ] Byt namn på konverterarna: `makeTargetBasic` → `toBasicTarget` och `makeTargetFull` → `toFullTarget` (23 anrop). Flytta `makeTargetFull`, `targetConfig` och `tableConfigs` till `functions/common/`.
- [ ] Stärk kontraktet: `isTarget` kräver exakt en post, `targetId` validerar, id `"0"` accepteras, `array_key_first()` ersätter `key()`/`current()`.
- [ ] Ersätt de manuella view-grenarna i `manage.php` och direkta `current($fullTarget)`-läsningar med target-helpers.
- [ ] Låt `updatedFullTarget()` behålla tidigare värde för fält som saknas i POST i stället för att tömma dem.
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

- [ ] `writeConfig.php`: ta bort `extract($configTables)` och ersätt `jsonFormat` med `json_encode()` med pretty print.
- [ ] `info`: ersätt `strftime()` (borttagen i PHP 9) och dela upp `printUniqueLogins`.
- [ ] Läs tillåtna domäner och fallback-URL i forwardauth från en ny konstant `constants/forwardauthReturnConfig.php` (`allowedDomains`, `defaultUrl`), i stil med `azureConfig.php`. `isSafeReturnTo()` och fallbacken i `azure-callback.php` använder den i stället för `kristianstad.se` och `https://kartor.kristianstad.se`.
- [ ] CI: kör `php -l` över `finalfs/www/adm` och lägg till ett smoke-test mot PostgreSQL för import, CRUD och ångra/gör om.
- [ ] Statisk analys: kör PHPStan på låg nivå (lokalt eller i CI) för att hitta odefinierade variabler, fel antal argument och `null`-användning utan att köra koden.
- [ ] `declare(strict_types=1)` i rena hjälpfiler (target-funktionerna, `pg*ToText`, array-hjälpare) och i nya filer, men först när smoke-testerna ovan finns. Inte i entry points eller filer som blandar HTML och databasdata.
- [ ] Förtydliga meddelandet när ett databasfel visas i `manage.php`; felets detaljer visas fortfarande för administratören.
- [ ] CORS: ny konstant `constants/allowedOrigins.php` och en hjälpfunktion i `functions/common` (till exempel `sendCorsHeaders()`) som skickar `Access-Control-Allow-Origin` och `Vary: Origin` för tillåtna ursprung. Den levererade filen innehåller `'*'` så att dagens beteende bevaras; README beskriver hur listan begränsas. `mapstate.php` använder hjälpfunktionen och svarar 403 på POST från ett ursprung som inte är tillåtet. Hjälpfunktionen kan senare användas av `updated.php`, `restrictedLayer.php` och `news.php` om de behöver anropas från en separat Origo.
- [ ] `mapstate`: begränsa storleken på request-bodyn i `createMapState()` och använd `random_bytes()` för UUID.
- [ ] Avbildsbygget: `.github/workflows/docker-image.yml` skickar build-argumentet `APP_VERSION`, men `Dockerfile` deklarerar bara `ORIGO_VERSION`. Taggen styrs därför av workflow-inmatningen medan basavbildens version styrs av Dockerfile-standardvärdet. Gör versionen entydig, till exempel genom att låta workflowet skicka `ORIGO_VERSION`.
- [ ] Startskripten: `finalfs/start/stage3/200.php` kontrollerar `/etc/ldap/ldap.conf`, men `createLdapConf` skriver `/etc/openldap/ldap.conf`. Kontrollera att filerna är samma fil i basavbilden och samordna sökvägarna.


## Fas 7. CSRF

- [ ] CSRF-token för skrivande flöden i `manage.php` och `news.php`. Ändrar POST-kontraktet (nytt dolt fält), så designen gås igenom innan implementation.

## Modulbacklog

Åtgärder som tidigare stod i modulreferenserna. Risker och begränsningar beskrivs fortfarande där.

- **manage:** förenkla upprepad schema-/tabellvisning (`printHeadForm` och `printChildSelect` har var sin prefix-strippning av föräldrans id); bryt ut delad QGIS-metadatahantering (`qgisProjectMetadata`) som även `info.php` och `writeTablesForAllLayers.php` behöver; lägg till null-kontroll i `initMessageListener.js`; överväg semantiska kontroller i `validateUpdate()` (PostgreSQL validerar redan kolumntyperna, inklusive `json`); ersätt `exit(1)` utan meddelande i `tableConfigs()`; låt `markMapsChanged()` använda parametrar.
- **writeConfig:** utvärdera `fixDuplicateDeclarations` (radbaserad JavaScript-parser); slå ihop `renderCssTags` och `renderJavaScriptTags`; bryt ut en `buildLegendUrl` utan att ändra värdena (kräver visuell verifiering); städa oanvända delar.
- **info:** bryt ut källdetaljer och AD-användardetaljer ur `info.php`; ersätt upprepad inloggningsräkning med en funktion.
- **forwardauth:** rätta docblock i `getOnPremisesSamAccountName`; ta bort eller styr utkommenterade debugblock.
- **authorization:** ta bort det dolda `call`-fältet i `displayLogin()` om ingen avsändare hittas (inget i repot skickar parametern och `login()` läser den inte); bryt ut dubblerad logik för nyhets-iframens URL; läs in `adldap2` först när LDAP används.
- **multiselect:** använd `$configSchema` i stället för hårdkodat schema; flytta inline-`onclick` till en namngiven funktion.
- **restrictedLayer:** lägg till `isset()`-kontroller för frågeparametrar; använd eller ta bort `$cause` i `finishError500`; överväg tydligare filnamn för `authorizationFilter`.
- **grouplayerfix:** rätta `$DEFAULT_QGIS_SERVER_PATH`/`_URL` och operatorprioritet i `getCachedProjectSettings`; ta bort dubblerade kodblock; harmonisera retry med `fetchWithStatus`; samla curl-inställningar.
- **read_json:** smoke-testa mot en riktig PostgreSQL med specialtecken i titlar, URL:er och beskrivningar samt rollback efter databasfel.
- **read_db_schemas:** utred syftet med `unset($_GET)`.
- **news:** ta bort död kod i `pgNewsArray` och `selectNew`; begränsa `includeDirectory("./functions/common")` till nödvändiga filer.
- **writeTablesForAllLayers:** överväg batchning och framsteg om timeout uppstår vid många lager.
- **export:** städning och väntetider kan bara planeras efter verifiering i den organisationsspecifika implementationen.
