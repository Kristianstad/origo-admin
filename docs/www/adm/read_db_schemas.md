# Read DB schemas-modul (read_db_schemas)

**Entry point:** `adm/read_db_schemas.php`
**Funktionsfiler:** `adm/functions/read_db_schemas/*.php`
**Anropas från:** knappen `printReadDbSchemasButton()` i manage-modulen

## Syfte
Ansluter till en extern, i förväg registrerad databas (identifierad via
`database_id`, med anslutningssträng lagrad i konfigurationsdatabasens
`databases`-tabell) och listar dess Postgres-scheman. Varje hittat schema
registreras i konfigurationsdatabasens `schemas`-tabell (om det inte redan
finns) i formatet `<database_id>.<schema_name>`. Detta gör de externa
schemana valbara i övriga delar av adminpanelen (t.ex. vid konfiguration
av databaskällor för kartlager).

Lyckat resultat ger ett tomt svar: anropet körs i en dold iframe och är
inte avsett att visas för en användare.

## Anropas med
`read_db_schemas.php?database=<database_id>`

| Parameter | Beskrivning |
|---|---|
| `database` | Id för en tidigare registrerad databas (`databases`-tabellens `database_id`) |

**Svar vid fel:**
- 400 om `database`-parametern saknas
- 404 om `database_id` inte finns i `databases`-tabellen
- 500 vid databasfel

**Svar vid lyckat resultat:** inget innehåll (200, tom body) – se ovan.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh($connectionString = null)` – ansluter till standarddatabasen utan
  argument, annars till databasen i anslutningssträngen (här en extern
  databas).
- `all_from_table($dbh, $schema, $table)` – hämtar alla rader ur
  `databases`-tabellen
- `array_column_search($value, $column, $rows)` – slår upp raden för
  angivet `database_id`

**Konstanter:**
- `constants/configSchema.php` → `$configSchema`

**Databas:** läser `<configSchema>.databases` (kolumner inkl.
`database_id`, `connectionstring`), skriver till
`<configSchema>.schemas` (kolumn `schema_id`). Ansluter dessutom
till en **extern databas** vars scheman listas via
`information_schema.schemata`.

## Filer och funktioner

| Fil | Funktion | Beskrivning |
|---|---|---|
| `schemaNamesFromDb.php` | `schemaNamesFromDb(&$dbh): array` | Listar namn på alla scheman i en databas, exkluderar `information_schema` och `pg_%`-scheman (Postgres interna scheman) |

## Begränsningar och risker

- `databases.connectionstring` innehåller anslutningsuppgifter i klartext och
  används direkt. Skyddet bestäms av vem som kan läsa tabellen.
- `schemaNamesFromDb()` använder `die()` vid SQL-fel, så `$dbh_config`
  stängs inte vid det felet.
- `read_db_schemas.php` kör `unset($_GET)` efter att `database` lästs, så
  koden längre ned kan inte läsa fler frågeparametrar.
