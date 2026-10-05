# Read JSON-modul (read_json)

## Syfte

`adm/read_json.php` importerar en Origo-kartkonfiguration från JSON och
skriver valda delar som nya poster i databasen. Formuläret kan importera
lager, grupper, karta, kontroller, sidfötter, proj4defs, källor, tilegrids,
stilar och tjänster.

Alla importerade id:n får suffixet från `importid`, vilket minskar risken
för krockar med befintlig konfiguration. JSON kan till exempel hämtas från
`writeConfig.php?getJson=y`.

## Anrop

GET till `read_json.php` visar importformuläret. POST-formuläret använder:

| Parameter | Beskrivning |
|---|---|
| `json` | Hela Origo-konfigurationen som JSON-text |
| `importid` | Suffix för skapade id:n; bokstäver, siffror, `-` och `_` tillåts |
| `mapid` | Id för den importerade kartan |
| `layers`, `groups`, `map`, `controls`, `footers`, `proj4defs`, `sources`, `tilegrids`, `styles`, `services` | `yes` när datatypen ska importeras |
| `csrf_token` | Sessionsbunden token från importformuläret |

## Flöde

1. Gemensamma funktioner, importhelpers och `$configSchema` laddas.
2. GET visar formuläret och skapar en CSRF-token.
3. POST validerar CSRF-token, import-id, kart-id och JSON-strukturen innan
   någon databasanslutning öppnas.
4. Importen körs i en PostgreSQL-transaktion. Alla INSERT-operationer använder
   `pg_query_params()` via `executeImportQuery()`.
5. Vid fel görs rollback och klienten får ett generiskt felmeddelande.
   Detaljer loggas server-side, inte till webbläsaren.
6. Vid lyckad import görs commit. Den nya kartan skapas med
   `maps.changed = 't'`, så att **Skriv kartkonfiguration**-knappen aktiveras.
   Användaren får därefter ett skinat lyckatmeddelande med en **Stäng**-knapp
   som stänger iframe-vyn.

Rå JSON-regex används inte längre. Källor, resolutionslistor och
`tileGridOptions` läses från den avkodade PHP-arrayen.

I `manage.php` visas formuläret och resultatet i `topFrame`. Höjden anpassas
automatiskt när innehållet växer eller minskar; iframe-handtaget för manuell
höjdändring är avstängt för den här vyn.

## Funktioner

| Fil | Funktion | Ansvar |
|---|---|---|
| `functions/common/generateCsrfToken.php` | `generateCsrfToken()` | Skapar eller återanvänder sessionsbunden CSRF-token |
| `functions/common/validateCsrfToken.php` | `validateCsrfToken()` | Validerar sessionsbunden CSRF-token |
| `functions/common/toPgArrayLiteral.php` | `toPgArrayLiteral(array $items)` | Escapad PostgreSQL-array som bunden parameter |
| `functions/read_json/executeImportQuery.php` | `executeImportQuery($dbh, string $sql, array $params = array())` | Kör parameteriserad query och kastar fel |
| `functions/read_json/buildLayerInsert.php` | `buildLayerInsert(...)` | Bygger layer-query och parameterlista |
| `functions/read_json/extractLayerStyleConfig.php` | `extractLayerStyleConfig(array $layerStyle)` | Extraherar ikon, extended ikon, filter och style-config |
| `functions/read_json/flattenGroupLayers.php` | `flattenGroupLayers(array $jsonLayers)` | Plattar ut GROUP-lager för import |
| `functions/read_json/recursiveGroups.php` | `recursiveGroups($dbh, array $groupsArr, string $importId, array $groupsLayers)` | Läser `$configSchema` från constants och skriver grupphierarkin rekursivt |
| `functions/read_json/renamedup.php` | `renamedup(string $name, array &$uniqueLayers)` | Undviker namnkrockar med explicit namnlista |

`recursiveGroups()` och `renamedup()` får sina beroenden via parametrar i
stället för `GLOBAL`. `ensureSessionWritable()` används för att skapa eller
återöppna sessionen med applikationens cookie-inställningar.

## Säkerhetsåtgärder

- CSRF-token krävs vid varje import-POST.
- Import-id begränsas till en säker teckenmängd och maximal längd.
- JSON måste vara giltig och centrala fält måste ha rätt arraytyp.
- Användarstyrda värden skickas som query-parametrar, inte som SQL-litteraler.
- PostgreSQL-arrayvärden citeras av `toPgArrayLiteral()`.
- Importen är allt-eller-inget genom `BEGIN`, `COMMIT` och `ROLLBACK`.
- SQL-fel och databasdetaljer exponeras inte i HTTP-svaret.
- Databasschemat hämtas från `constants/configSchema.php`.
- Importhelpersen använder explicita parametrar i stället för globala variabler.

## Begränsningar

CSRF-token används i `read_json.php` och `sql_import.php`; övriga admin-sidor
saknar CSRF-skydd. Importen verifieras inte av automatiska tester mot en
riktig PostgreSQL-databas.
