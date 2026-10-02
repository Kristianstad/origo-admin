# Mapstate-modul (mapstate)

**Entry point:** `adm/mapstate.php`
**Funktionsfiler:** `adm/functions/mapstate/*.php`

## Syfte
Ett stateless JSON-API för att spara och återläsa karttillstånd (state) i en
Origo-karta, identifierat med ett genererat UUID. Tillståndet gör det möjligt
att dela länkar till en specifik kartvy (kolumnen `mapurl`). Gamla, oanvända
tillstånd rensas automatiskt vid varje anrop.

**OBS:** till skillnad från news-modulen finns **ingen inloggnings- eller
sessionskontroll** i denna fil. Endpointen är öppen för alla och CORS tillåter
`*`, så den publika kartan kan spara och läsa tillstånd utan inloggning.

## Anropas med
Rent REST-liknande API, ingen `action`-parameter:

| Metod | Parametrar | Beskrivning | Svar |
|---|---|---|---|
| `POST` | JSON body (godtyckligt state-objekt) | Skapar ett nytt karttillstånd | `{"mapStateId": "<uuid>"}` |
| `GET`  | `?mapStateId=<uuid>` | Hämtar ett sparat karttillstånd | Rått JSON-innehåll (state) |
| `OPTIONS` | – | CORS-preflight | Tom 200 |
| annat | – | – | 405 `{"error": "Metod ej tillåten"}` |

Felsvar (400/404/500) returneras alltid som `{"error": "..."}`.

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()` – öppnar databasanslutning (enda common-funktionen som används här)

**Konstanter:**
- `constants/mapstateMaxUnused.php` → `$mapstateMaxUnused` (antal dagar innan
  ett oanvänt state städas bort, se `cleanupOldMapStates`)
- `constants/configSchema.php` → `$configSchema` (läses internt av
  `getMapStatesTable()`, med fallback till `'public'` om ej satt)

**Databas:** tabellen `<configSchema>.mapstates` med kolumner
`mapstate_id, state, created, lastuse, mapurl, preserve`.
(`preserve`-kolumnen förhindrar att en post städas bort av cleanup. Den
satts inte i denna modul utan i manage-modulen via fältet "Rensas ej" i
`printMapstateForm()`.)

## Filer och funktioner

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `cleanupOldMapStates.php` | `cleanupOldMapStates($dbh, int $days): void` | Tar bort mapstates som är äldre än `$days` och oanvända, eller >30 dagar gamla och aldrig använda – om inte `preserve` är satt. Körs på **varje** request |
| `createMapState.php` | `createMapState($dbh): never` | Läser JSON från request body, genererar ett UUID v4, sparar i databasen, svarar med `mapStateId`. Avslutar alltid med `exit` |
| `getMapStatesTable.php` | `getMapStatesTable(): string` | Bygger (och cachar statiskt) det fullt kvalificerade, escapade tabellnamnet `schema.mapstates`. Delas av övriga funktioner i modulen |
| `retrieveMapState.php` | `retrieveMapState($dbh): never` | Validerar `mapStateId`, uppdaterar `lastuse`, hämtar och returnerar sparat state. Avslutar alltid med `exit` |
| `updateLastUse.php` | `updateLastUse($dbh, string $id): void` | Sätter `lastuse = NOW()` för ett givet id |
| `validateMapStateId.php` | `validateMapStateId(string $id): bool` | Validerar att id matchar UUID-formatet via regex |

## Begränsningar och risker

- **Ingen inloggningskontroll och öppen CORS** (`Access-Control-Allow-Origin: *`)
  är avsiktligt: den publika kartan ska kunna spara och läsa tillstånd utan
  inloggning. Anrop tillåts från vilken domän som helst.
- `retrieveMapState.php`, `updateLastUse.php` och `createMapState.php`
  binder värden med `pg_query_params()`. `retrieveMapState()` validerar id:t
  med `validateMapStateId()` före anropet; `updateLastUse()` validerar inte
  själv och ska därför bara anropas med ett validerat id. `cleanupOldMapStates()`
  binder även åldersgränserna.
- UUID genereras i PHP med `mt_rand()`, inte med en kryptografiskt säker
  källa.
- `mapstate.php` anropar `pg_close($dbh)` efter OPTIONS-svar även om
  `dbh()` skulle ha misslyckats.
- Filerna saknar `declare(strict_types=1)`; typdeklarationerna är därför
  inte strikta.
