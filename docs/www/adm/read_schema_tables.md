# Read schema tables-modul (read_schema_tables)

**Entry point:** `adm/read_schema_tables.php`

## Syfte
Systerfunktion till `read_db_schemas.php`, men ett steg djupare: där
`read_db_schemas.php` listar **scheman** i en extern databas, listar
denna modul **tabeller** i ett specifikt schema och registrerar dem i
konfigurationsdatabasens `tables`-tabell. Anropas av knappen
`printReadSchemaTablesButton()` i `manage.php` för det valda schemat.

Lyckade anrop ger ett tomt svar; felkoder beskrivs under "Svar och fel".

## Anropas med
`read_schema_tables.php?schema=<database_id>.<schema_name>`

| Parameter | Beskrivning |
|---|---|
| `schema` | Kombinerat `database_id.schema_name`, delas upp med `explode('.', ..., 2)` |

## Beror på
**Common-funktioner:**
- `dbh($connectionString)` – ansluter både till konfigurationsdatabasen
  och till den externa databasen (samma mönster som read_db_schemas)
- `allFromTable()`, `arrayColumnSearch()` – slår upp anslutningssträng
- `tableNamesFromSchema($dbh, $schema)` – listar tabellnamn i ett givet
  schema, dokumenterad i common.md

**Konstanter:** `constants/configSchema.php` → `$configSchema`

**Databas:** läser `<configSchema>.databases`, skriver till
`<configSchema>.tables` (kolumn `table_id`, format `db.schema.tabell`).

## Svar och fel
- Saknad `schema`-parameter ger HTTP 400, okänd databas 404 och SQL-fel 500.
- Vid SQL-fel loggas felet med `error_log()` och båda databasanslutningarna
  stängs.
- INSERT-satsen använder `pg_query_params()` med platshållare.
- `read_schema_tables.php` kör `unset($_GET)` efter att `schema` lästs, som
  `read_db_schemas.php`.
