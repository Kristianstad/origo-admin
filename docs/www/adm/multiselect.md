# Multiselect-modul (multiselect)

**Entry point:** `adm/multiselect.php`
**JS-filer:** `adm/js-functions/multiselect/*.js`
**Stilmall:** `adm/styles/multiselect.css`

## Syfte
Generisk komponent för att välja flera poster ur en databastabell via en
`<select multiple>`, tänkt att öppnas i en iframe/popup från ett annat
formulär (`manage.php`, via `printMultiselectButton()`). Håller reda på urvalsordning (senast
tillagd sist) och skickar tillbaka resultatet som en kommaseparerad sträng
till förälderfönstret via `postMessage`, för visning i ett textarea-fält
där.

All PHP-logik ligger i entry point-filen – ingen egen funktionsmapp i
`functions/`. Klientbeteendet (ordningshantering, toggle-klick,
förval, skicka-tillbaka) sköts av separata JS-filer.

## Anropas med
`multiselect.php?table=<textareaId>::<tabellnamn>:<aktuella värden>`

| Del | Beskrivning |
|---|---|
| `<textareaId>` | Id på textarea-elementet i förälderfönstret som ska ta emot urvalet |
| `<tabellnamn>` | Namnet på tabellen (i `map_configs`-schemat) att välja poster ur, t.ex. `layers`, `proj4defs` |
| `<aktuella värden>` | Kommaseparerad lista med redan valda värden (förifyller urvalet) |

Ingen JSON/REST – ren HTML-sida avsedd för iframe.

**Specialfall:** om `<tabellnamn>` är `proj4defs` används kolumnen `code`
som id-kolumn istället för det generella mönstret `<singularis>_id`.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()` – databasanslutning
- `allFromTable($dbh, $schema, $table)` – hämtar alla rader från en
  tillåten tabell och citerar identifierarna
- `configTableNames($dbh)` – databasbaserad allowlist över objekttabeller
- `pkColumnOfTable($table)` – hittar id-kolumnen, inklusive `proj4defs.code`
- `toSwedish($string)` – översätter tabellnamn till svensk rubrik

**JS-funktioner** (`adm/js-functions/multiselect/`, laddas inline via
`includeDirectory()` i en `<script>`-tagg – se separat avsnitt nedan)

**Databas:** läser valda rader från tabeller angivna i
`constants/multiselectables.php`; alias översätts via
`constants/tableAliases.php`. Schemat hämtas från `configSchema.php`.

## JS-filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `closeTopFrame.js` | `closeTopFrame()` | Skickar bara en `close`-signal (utan värde) till förälderfönstret |
| `getCurrentSelection.js` | `getCurrentSelection()` | Läser och trimmar värdet från textarean `#selection` |
| `makeSelectToggleOnly.js` | `makeSelectToggleOnly(selectId)` | Fångar `mousedown` i capture-fas för att göra vanligt klick till "toggla ett alternativ" istället för webbläsarens standard shift/ctrl-rangebeteende |
| `selectOptionsByValues.js` | `selectOptionsByValues(selectId, optionValues)` | Förvalsmarkerar options baserat på en kommaseparerad sträng, körs vid sidladdning |
| `sendSelectionAndClose.js` | `sendSelectionAndClose(targetId)` | Läser aktuellt urval och skickar det + en `close`-signal till förälderfönstret via `postMessage` |
| `update.js` | `update(menu)` | Körs vid ändring i select-listan. Räknar ut vad som lagts till/tagits bort, håller ordning på urvalsordningen i `data-sorted-values`, uppdaterar textarean |

**Beroendekedja mellan JS-filerna:** `makeSelectToggleOnly` anropar
`update` (global funktion, måste finnas laddad). `sendSelectionAndClose`
anropar `getCurrentSelection`. Eftersom alla filer i mappen laddas
tillsammans inline i samma `<script>`-block spelar filordningen inom
`includeDirectory()` ingen praktisk roll här, men vore det viktigt att
veta om filerna någonsin laddas separat.

## Begränsningar och risker

- `$table` från `$_GET['table']` måste finnas i `multiselectables.php`
  efter aliasöversättning och i databasens allowlist över objekttabeller.
  Ogiltig parameter ger HTTP 400; `allFromTable()` citerar identifierarna.
- `table`-parametern kodar tre värden i en sträng med två separatorer:
  `explode('::', ..., 2)` följt av `explode(':', ..., 2)`.
- All utskrift av användarstyrd data (`$textareaId`, `$currentValue`,
  `$header`, options-värden) går genom `htmlspecialchars()`.
