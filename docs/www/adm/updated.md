# Updated-modul (updated)

**Entry point:** `adm/updated.php`
**Funktionsfiler:** `adm/functions/updated/*.php`
**Stilmall:** `adm/styles/updated.css`

## Syfte
Tar reda på **när en eller flera databastabeller senast ändrades**,
genom att fråga Postgres commit-tidsstämpel (`pg_xact_commit_timestamp`)
för varje tabells senaste rad. Returnerar det senaste datumet (endast
datumdel, `YYYY-MM-DD`) bland de angivna tabellerna.

**Förutsättning:** kräver att `track_commit_timestamp` är påslaget i
Postgres-konfigurationen, annars returnerar
`pg_xact_commit_timestamp()` alltid NULL.

Begärda tabeller måste finnas i `map_configs.tables` och som bastabeller i
databasen. Tabellnamnen valideras innan de citeras och används i frågan.
Ogiltig eller oregistrerad `table`-parameter ger HTTP 400.

## Anropas med
`updated.php?table=<schema.tabell1>,<schema.tabell2>,...`

| Parameter | Beskrivning |
|---|---|
| `table` | Kommaseparerad lista med fullt kvalificerade tabellnamn (`schema.tabell`) att kolla senaste ändring för |

**Svar:** ett enkelt textdatum, t.ex. `2025-06-12` (de första 10 tecknen
av tidsstämpeln för den senast ändrade tabellen).

## Loader-filer

`updated/updated-loader.php` gör `chdir('../adm/')` och inkluderar
`updated.php`. `addLayersToJson()` bygger iframe-adressen
`/php/updated/updated-loader.php?table=<tabeller>` för lagrets
"Uppdaterad"-knapp.

## Beror på
**Common-funktioner:** `dbh($connectionString)`

**Konstanter:** `constants/dbhConnectionStringForUpdated.php` →
`$dbhConnectionStringForUpdated` – egen anslutningssträng för denna modul,
skild från standardanslutningen. Den använder rollen
`origo_updated_readonly`, som skapas i `050.postgres.sql`, får
`pg_read_all_data` och har `default_transaction_read_only` aktiverat för
`origo`. Rollen saknar lösenord och ansluter via localhost, som standard
tillåts av containerns loopback-HBA-regler.

## Filer och funktioner

| Fil | Funktion | Beskrivning |
|---|---|---|
| `updatedFromTable2.php` | `updatedFromTable2($dbh, $tableWithSchema): array\|false\|null` | Kontrollerar tabellen mot `information_schema`, citerar identifierarna och returnerar `[tidsstämpel, xmin]`, `null` utan rader eller `false` om tabellen saknas |

## Begränsningar och risker
- `$tableWithSchema` kommer från `$_GET['table']` (kommaseparerad), men
  varje namn måste vara registrerat i `map_configs.tables` och finnas som
  bastabell innan det används. `qualifiedTableIdentifier()` citerar
  schema- och tabellnamn.
- `updatedFromTable2()` returnerar tidsstämpel och `xmin` för den senast
  ändrade raden. `updatedFromTable()` i `functions/manage/` returnerar
  bara tidsstämpeln och används av manage för tabellformuläret.
- SQL-fel avslutar skriptet med `die()`.
