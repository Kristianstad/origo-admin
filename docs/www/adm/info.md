# Info-modul (info)

**Entry point:** `adm/info.php`
**Funktionsfiler:** `adm/functions/info/*.php`
**Stilmall:** `adm/styles/info.css`

## Syfte
Visar detaljerad information om ett enskilt objekt i systemet (t.ex. en
karta, ett lager, en källa, en tabell eller en AD-användare) samt en lista
över vilka andra objekt som använder/refererar till det ("Används av",
hämtat rekursivt via `findAllParents`). Tänkt att visas i en iframe – sidan
skickar `postMessage`-anrop (`resize`, `close`) till föräldrafönstret.

Ikonknappen "Administrera" visar samma pil som Hämta följd av en fet
asterisk. Knappens tillgängliga namn är "Administrera"; den skickar
objektets id till `manage.php` för redigering.
"Används av"-listan länkar till `info.php` för varje förälder, vilket gör
sidan rekursivt navigerbar mellan relaterade objekt.

## Anropas med
`info.php?type=<typ>&id=<id>`

| Parameter | Beskrivning |
|---|---|
| `type` | Objekttyp i singularform, t.ex. `map`, `layer`, `source`, `aduser` (motsvarande tabell är `<type>s`) |
| `id` | Objektets primärnyckelvärde |

Inget REST/JSON-API – ren HTML-sida.

När sidan laddas byggs först en basic target från `type` och `id`. Den slås
sedan upp till en full target innan objektets fält och specialfall läses.
Samma basic target skickas vidare till `findAllParents()`, så info-vyn och
manage-vyn använder samma target-kontrakt för objektidentitet.

**Specialfall per typ:**
- `type=source`: om källans tjänst är av typ `qgis`, läses en `.qgs`-fil
  direkt från disk (`/services/<service>/<id-utan-suffix>.qgs`) för att visa
  QGIS-version och senaste uppdateringsinfo.
- `type=aduser`: visar statistik över unika inloggningar (dag/vecka/
  månad/år) via `printUniqueLogins()`.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()` – databasanslutning
- `toSwedish($string)` – översätter interna typnamn till svenska för visning
- `all_from_table($dbh, $schema, $table)` – hämtar alla rader från en tabell
- `array_column_search($value, $column, $rows)` – hittar första raden där
  `$column` matchar `$value`
- `pkColumnOfTable($table)` – returnerar namnet på primärnyckelkolumnen för
  en given tabell
- `findAllParents($dbh, $child)` – hittar (rekursivt) alla objekt som
  refererar till ett givet objekt
- `makeTargetBasic($target)`, `makeBasicTarget()`, `makeFullTarget()`,
  `targetType()`, `targetId()`, `targetTable()`, `targetIdColumn()` och
  `targetConfigParam()` – bygger och läser objekt-targets
- `assoc_array_values($array)` – (används i `printParents`, sannolikt för
  att kontrollera om en nästlad array har några faktiska värden)

**Konstanter:**
- `constants/configSchema.php` → `$configSchema`

**Filsystem:** läser QGIS-projektfiler direkt från `/services/<service>/`
utanför webbroten (för `source`-typer med QGIS-tjänst).

**Databas:** läser från flera olika tabeller beroende på `type`
(`<type>s`), samt `services`-tabellen när `type=source`.

## Filer och funktioner

| Fil | Funktion | Beskrivning |
|---|---|---|
| `printParents.php` | `printParents($allParents)` | Skriver ut en länkad, grupperad lista över objekt som refererar till det aktuella objektet. Grupperas per tabell och "relationstyp" (kolumn). Länkar rekursivt till `info.php` för varje förälder |
| `printUniqueLogins.php` | `printUniqueLogins($lastlogins)` | Beräknar och skriver ut antal unika AD-inloggningar idag/denna vecka/månad/år samt senaste vecka/månad/år, baserat på en lista av `lastlogin`-tidsstämplar |

## Kända begränsningar / observationer (ej åtgärdat ännu)

- **⚠️ Ingen inloggnings-/behörighetskontroll** i `info.php` själv (till
  skillnad från `news.php`) — bör bekräftas om detta är avsiktligt (t.ex.
  om åtkomst styrs på annat sätt, som via nätverk/proxy) eller en lucka.
- **⚠️ `$childId` och `$childType` skrivs ut direkt i HTML utan
  `htmlspecialchars()`** (t.ex. `echo "<h2>$childId</h2>"`), samt `$_GET`-
  värden används för att bygga filsökväg till `.qgs`-filen
  (`'/services/' . $childFull['service'] . '/' . ...`). `$childFull['service']`
  kommer visserligen från databasen (inte direkt från `$_GET`), men
  `$childType` i URL:en (`?type=`) avgör vilken tabell som slås upp –
  värt att dubbelkolla att `all_from_table`/tabellnamnet är skyddat mot
  godtycklig `type`-input (SQL injection eller path traversal via
  tabellnamn).
- **`printUniqueLogins.php` använder `strftime()`**, vilket är
  **deprecated sedan PHP 8.1** och borttaget i PHP 9. Bör ersättas med
  `IntlDateFormatter` eller `DateTime::format()` vid refaktorering –
  detta är den enda platsen hittills i kodbasen vi sett som kommer sluta
  fungera vid PHP-uppgradering.
- **`printUniqueLogins.php` är lång och repetitiv** — samma
  filtrerings-/räknelogik upprepas 7 gånger (idag, vecka, månad, år,
  -1 vecka, -1 månad, -1 år) med bara olika tidsintervall. Bra kandidat
  för att brytas ut till en hjälpfunktion, t.ex.
  `countLoginsSince(DateTime $from, DateTime $to, array $timestamps): int`.
- **Blandat ansvar i `info.php`:** filen blandar routing, datahämtning,
  HTML-generering och en gren av domänlogik (QGIS-filläsning) i en och
  samma fil. Kandidat för att bryta ut till egna funktioner i
  `functions/info/` vid förenkling (t.ex. `renderSourceDetails()`,
  `renderAdUserDetails()`).
- Ingen typning (`strict_types`, parametertyper) i någon av filerna.
