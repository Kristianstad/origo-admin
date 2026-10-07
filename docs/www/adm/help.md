# Hjälp-modul (help)

**Entry point:** `adm/help.php`
**Stilmall:** `adm/styles/help.css`
**Gemensamma UI-resurser:** `adm/styles/common.css` och `adm/js-functions/common/*.js`, via `renderUtilityHead()`

## Syfte
Visar en hjälptext i en popup/iframe (samma `postMessage`-mönster som
info- och multiselect-modulerna). Utan parameter visas en generisk lista
med länkar till Origo-dokumentation och ett externt JSON-valideringsverktyg
(för att kontrollera att t.ex. `style_config`/`options`-fält innehåller
giltig JSON innan de sparas). Med en
`id`-parameter visas istället en specifik hjälptext hämtad från databasen,
kopplad till ett formulärfält i `manage.php` (`printHelpButton()`).

## Anropas med
`help.php` eller `help.php?id=<help_id>`

| Parameter | Beskrivning |
|---|---|
| `id` | (valfri) Id för en specifik hjälptext i `helps`-tabellen. Om utelämnad visas generella hjälplänkar |

## Beror på
**Common-funktioner:** `dbh()`, `allFromTable()` och `currentSkin()` används
för skin på varje request. `arrayColumnSearch()` används när `id` anges.

**Konstanter:** `constants/configSchema.php` → `$configSchema`

**Databas:** `<configSchema>.helps` (kolumner inkl. `help_id`, `abstract`)

**Filsystem:** länkar till (men läser inte) `../Origo_admin_tutorial_swedish.pdf`
på toppnivå.

`renderUtilityHead()` skriver sidans skinvariabler, `help.css` följt av
`common.css`, samt de gemensamma `closeTopFrame()`- och
`resizeParentFrame()`-funktionerna. `renderCloseButton()` skriver Stäng-knappen.

## Begränsningar och risker
- `$help['abstract']` skrivs ut utan `htmlspecialchars()` eftersom
  hjälptexterna skrivs av administratörer och innehåller HTML. Samma
  mönster som i `news.php`; texten måste därför komma från betrodda
  källor. Det är avsiktligt: förutsättningen är att administratörer är
  betrodda.
