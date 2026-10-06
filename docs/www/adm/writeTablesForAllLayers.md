# Write tables for all layers-modul (writeTablesForAllLayers)

**Entry point:** `adm/writeTablesForAllLayers.php`

## Syfte
Ett **underhålls-/batchscript** snarare än en vanlig sida: går igenom
samtliga QGIS-baserade lager i systemet som saknar en ifylld
`tables`-kolumn, läser motsvarande QGIS-projektfil (`.qgs`) från disk,
och fyller i vilka databastabeller lagret använder (via
`tablesFromQgsXml()`). Det slipper manuell angivelse av vilka tabeller
varje QGIS-lager bygger på: informationen härleds direkt ur
QGIS-projektfilen.

Filen delar den gemensamma QGIS-helpern `tablesFromQgsXml()` med
manage-modulen (se "Beror på" nedan). Resultatet skrivs till
`layers.tables`.

## Anropas med
`writeTablesForAllLayers.php` (inga parametrar) – körs för samtliga
lager i systemet i ett svep.

**Svar:** en HTML-sida med antal uppdaterade lager och en lista över
eventuella fel (QGS-fil som inte kan läsas, SQL-fel).

## Beror på
**Common-funktioner:**
- `dbh()`, `allFromTable()`, `arrayColumnSearch()`, `pkColumnOfTable()`

**Common-funktion:**
- `tablesFromQgsXml($qgsXml, $layerName)` – ligger i
  `functions/common/`. Tolkar en
  QGIS-projektfils XML för att hitta vilka databastabeller ett givet
  lager bygger på. Delas av manage och writeTablesForAllLayers.

**Konstanter:** `constants/configSchema.php` → `$configSchema`

**Databas:** läser `layers`, `sources`, `services`; skriver
`layers.tables` (Postgres-array).

**Filsystem:** läser QGIS-projektfiler direkt från disk,
`/services/<service>/<sourceName>.qgs` – samma mönster som i
`info.php` för `source`-typer.

## Begränsningar och risker
- Skriptet saknar parametrar och körs över alla lager som saknar `tables`.
  Det har ingen batchning, framstegsvisning eller `set_time_limit()`.
- Logiken ligger direkt i entry pointen i stället för i en
  `functions/`-mapp.
