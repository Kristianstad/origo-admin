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

## Anropas med
`updated.php?table=<schema.tabell1>,<schema.tabell2>,...`

| Parameter | Beskrivning |
|---|---|
| `table` | Kommaseparerad lista med fullt kvalificerade tabellnamn (`schema.tabell`) att kolla senaste ändring för |

**Svar:** ett enkelt textdatum, t.ex. `2025-06-12` (de första 10 tecknen
av tidsstämpeln för den senast ändrade tabellen).

## Beror på
**Common-funktioner:** `dbh($connectionString)`

**Konstanter:** `constants/dbhConnectionStringForUpdated.php` →
`$dbhConnectionStringForUpdated` – egen anslutningssträng för denna modul,
skild från standardanslutningen och enligt kommentaren i konstantfilen
skrivskyddad.

## Filer och funktioner

| Fil | Funktion | Beskrivning |
|---|---|---|
| `updated_from_table2.php` | `updated_from_table2($dbh, $tableWithSchema): array\|null` | Kör `pg_xact_commit_timestamp`-frågan för en tabell, returnerar `[tidsstämpel, xmin]` för senast ändrade rad |

## Begränsningar och risker
- **SQL-injektionsrisk:** `$tableWithSchema` kommer från `$_GET['table']`
  (kommaseparerad) och byggs in direkt i SQL utan validering mot tillåtna
  tabeller. Tabellnamn kan inte bindas med `pg_query_params()`.
- `updated_from_table2()` returnerar tidsstämpel och `xmin` för den senast
  ändrade raden. `updated_from_table()` i `functions/manage/` returnerar
  bara tidsstämpeln och används av manage för tabellformuläret.
- SQL-fel avslutar skriptet med `die()`.
