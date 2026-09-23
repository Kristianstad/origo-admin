# Manage-modul (manage)

**Entry point:** `adm/manage.php` (32K, den enskilt största och mest
centrala filen i systemet)
**Funktionsfiler:** `adm/functions/manage/*.php` (target-primitiverna ligger i `adm/functions/common/`)
**JS-filer:** `adm/js-functions/manage/*.js` (6 filer)
**Stilmall:** `adm/styles/manage.css`

## Syfte
Den centrala administrationssidan: en generisk CRUD-motor (skapa, läsa,
uppdatera, radera) för alla konfigurationsobjekt i systemet – kartor,
grupper, lager, källor, tjänster, databaser, scheman, tabeller,
kontroller, plugins, sökmodeller/-tabeller, m.fl. Istället för en separat
sida per objekttyp, hanterar en enda, parametriserad kodväg samtliga
typer, genom att typen (`$type`) och vilken tabell den motsvarar
(`$typeTableName`) räknas ut dynamiskt utifrån vilken knapp som
klickades och vilket fält som postades.

Presenterar en kaskaderande vy: välj en karta → se dess grupper/lager/
kontroller → välj en grupp → se dess undergrupper/lager → välj ett
lager → se och redigera lagrets fullständiga formulär. Motsvarande
kaskad finns för databas → schema → tabell. Vyn Informationsförvaltning
lägger till klass → informationsgrupp → informationsgrupp, med lager och
tabeller som barn på varje nivå.

Skriver ändringar direkt till konfigurationsdatabasen, och markerar
(via `markMapsChanged()`) vilka publicerade kartor som blivit
inaktuella och behöver köras genom `writeConfig.php` igen för att
ändringen ska synas för besökare.

Formulärens renderingsstate skickas explicit genom `inheritPosts`-contexten.
Privata nycklar som `_viewDepth` och `_formChanged` används internt och
filtreras bort av `printHiddenInputs()`, så formulärhelpers behöver inte läsa
globala PHP-variabler för raderings- eller ändringsstatus.

## Datastrukturen "target"

Ett återkommande begrepp genom hela manage-modulen. Ett **target** är en
liten, enhetlig datastruktur som representerar "ett objekt av en viss
typ", i två varianter:

- **Basic target:** `[$type => $id]`, t.ex. `['layer' => 'vagar#1']`
  – bara typen och dess id, inget annat. Skapas av `makeBasicTarget()`.
- **Full target:** `[$type => $config]`, t.ex.
  `['layer' => ['layer_id' => 'vagar#1', 'title' => 'Vägar', ...]]`
  – typen och hela dess konfigurationsrad från databasen. Skapas av
  `makeFullTarget()` (om du redan har `$config`) eller `makeTargetFull()`
  (om du bara har ett basic target och en databaskoppling/configTables,
  och behöver slå upp konfigurationen åt dig).

Skillnaden mellan en "full" och "basic" target känns igen på om värdet
är en array (`is_array(current($target))`) – vilket är precis vad
`isFullTarget()` kontrollerar.

Detta enhetliga format gör att funktioner som `sqlForUpdate()`,
`sqlForOperation()`, `printChildSelect()` m.fl. kan ta emot "vilket
objekt som helst" utan att bry sig om exakt vilken av de många typerna
(map/layer/group/source/...) det handlar om – de läser bara ut typen
via `array_key_first()`/`key()`-mönster och agerar generiskt. Detta är
kärnan i hur manage-modulen kan hantera alla entitetstyper med samma
kodväg istället för att skriva om samma logik för varje typ.

**SQL-generering byggd ovanpå target-infrastrukturen:**
sqlForUpdate($fullTarget, $updatePosts)
├─ targetTable(), targetId()
├─ updatedFullTarget($fullTarget, $updatePosts) → slår ihop nuvarande + nya värden
├─ appendUpdatedColumnsToSql(targetConfig($fullTarget), $sql) → bygger SET-sats + parametrar
└─ targetIdColumn() → bygger WHERE-satsen
→ resultat: `array('sql' => ..., 'params' => ...)` för given target

sqlForOperation($operation, $child, $parent)
→ tar en basic child target och en full parent target, läser förälderns
array-kolumn (t.ex. maps.layers), lägger till/tar bort barnets id och
skriver tillbaka som en ny Postgres-array
→ resultat: `array('sql' => ..., 'params' => ...)` för att koppla/koppla loss två objekt

Target-API:t används också av parent-traverseringen och renderingshjälparna:
`usedInMaps()`, `findParents()` och `findAllParents()` normaliserar inkommande
targets med `makeTargetBasic()` och läser typ med `targetType()`, medan
`printChildSelect()`, `printInfoButton()`, `printDeleteButton()` och
add/remove-operationerna använder `targetType()`, `targetId()`,
`targetConfig()` och `targetConfigParam()` i stället för att läsa targetens
nyckel och värde direkt.

`writeConfig.php` använder däremot kartans konfigurationsrad som en lokal
arbetsstruktur för JSON-genereringen. Den är medvetet inte omskriven till
targets, eftersom target-abstraktionen där inte skulle minska komplexiteten.

## Historik: Ångra/Gör om (undo/redo)

Ett fristående historiklager, separat från själva konfigurationstabellerna,
låter administratören ångra och göra om fältuppdateringar (command update)
per objekt. Modellen bygger på tre tabeller i map_configs (definierade i
initdb/060.origo.sql):

- object_identity - en stabil identitet per objekt: target_key
  (<typ>:<id>, se objectHistoryKey()) som primärnyckel, plus target_table
  och target_id.
- edits - en logg av before/after-snapshots (before_data/after_data, json)
  för varje ändring, en rad per ändring, kopplad till target_key.
- edit_cursor - en rad per target_key som pekar ut vilken edits-rad som
  är det aktuella tillståndet (current_edit_id), plus cachade
  can_undo/can_redo-flaggor (visningsflaggorna räknas dock om från grunden
  av historyStateForTarget() snarare än att läsas direkt).

Skrivsidan: i manage.php:s update-gren sparas konfigurationen som den såg
ut före ändringen ($historyBeforeConfig). Om transaktionen lyckas anropas
recordHistoryEdit() med före/efter-snapshotet, vilket lägger till en ny
edits-rad, flyttar edit_cursor dit, och kastar bort en ev. kvarvarande
"gör om"-gren (rader med edit_id större än den gamla cursorn) - precis som
i en vanlig linjär undo/redo-stack.

Läsidan: knapparna (printUndoButton()/printRedoButton(), samlade via
printHistoryButtons()) postar target_key/target_table/target_id som dolda
fält i objektets egna redigeringsformulär och bekräftar åtgärden via knappens
egen onclick (inte formulärets onsubmit – ett omslutande `<form>` hade
tyst kastats bort av webbläsaren eftersom formulär inte får nästlas, vilket
ursprungligen gjorde att bekräftelsedialogen aldrig visades). manage.php har en
egen dispatch-gren för dessa två kommandon (parallell med
copy/create/delete/update/operation) som anropar applyHistoryNavigation():
den flyttar edit_cursor ett steg i given riktning och skriver tillbaka
before_data (undo) eller after_data (redo) till objektets tabell.

Medvetet avgränsat: endast update-kommandot loggas och kan
ångras/göras om. copy, create och delete ingår inte i historikmodellen
än - de skapar eller tar bort hela rader, vilket kräver att undo/redo även
kan återskapa/ta bort raden (INSERT/DELETE) snarare än bara skriva om
befintliga kolumnvärden (UPDATE), samt hantera eventuella referenser till
raden. Detta är ett medvetet avgränsat första steg, inte en bugg.

UI: printHistoryButtons() döljer bägge knapparna helt om varken ångra
eller gör om är möjligt för objektet (t.ex. inga sparade ändringar än).
Knapparna är just nu inkopplade i printSimpleEntityForm() (alla
"enkla"-formulär) samt i printMapForm.php, printLayerForm.php,
printSourceForm.php, printServiceForm.php och printTableForm.php.

Formulär och åtgärdsknappar: knapphelpers som renderas inuti ett
entitetsformulär får inte skapa egna nästlade `<form>`-element. Sådana
element ignoreras eller omtolkas av webbläsarens HTML-parser och kan göra
att knappar skickar fel formulär eller tappar sin bekräftelse. Submit-
åtgärder som Uppdatera/Kopiera/Radera/Backa/Gör om använder entitetens
befintliga POST-formulär och bekräftar via knappens `onclick`. Fristående
GET-åtgärder (Info, läs in schema/tabeller, publicera, exportera och
förhandsgranska) använder typknappar utan eget formulär och riktar
begäran till `topFrame`, `hiddenFrame` eller ett nytt fönster. Avbryt i
bekräftelserna för Radera, publicera och inläsning stoppar åtgärden.

Bläddringsbar logg: `edits`-tabellen är tillagd som en vanlig, bläddringsbar
entitetstyp ("Ändringar") under vyn "Verktyg" (`constants/views.php`), via
`printEditForm.php` (följer det "enkla"-mönstret). Nästan alla fält är
readonly (systemgenererad revisionsdata) – endast `abstract`/`info` är
redigerbara, så en administratör kan skriva en läsbar förklaring till en
lagrad ändring i efterhand. `object_identity`/`edit_cursor` är medvetet
**inte** exponerade i någon vy – de är intern bokföring, inte något en
administratör behöver bläddra i direkt. Typen `edit`/`edits` har fältnamn
och hjälptexter precis som övriga typer: översättning i
`constants/swedishDic.php` och `help_id`-poster (`edit:<fält>`) i
`initdb/060.origo.sql`.

## Mönster: enkla entitetsformulär (printKeywordForm, printAduserForm, m.fl.)

Flera `print<Typ>Form`-funktioner använder nu den gemensamma
`printSimpleEntityForm()`-funktionen:

```php
function print<Typ>Form($target, $inheritPosts, $helps=array())
{
  printSimpleEntityForm($target, $type, $fields, $inheritPosts, $helps, $extras);
}
```

Varje wrapper deklarerar en ordnad `$fields`-array. Fältdefinitionerna
stöder textarea med valfri `readonly`-flagga och select-fält med sina
optioner. `$extras` stöder inline-knappar (t.ex. databasens
`printReadDbSchemasButton` och gruppens preview) samt sektioner efter
formuläret (kontrollens och gruppens add/remove-operationer).
Funktionsnamnen och deras publika argument är oförändrade eftersom
`manage.php` fortfarande använder dynamisk dispatch.

**Instanser av mönstret (sorterad alfabetiskt efter filnamn):**

| Fil | Typ | Fält utöver id/abstract/info | Extra knappar/sektioner |
|---|---|---|---|
| `printAduserForm.php` | aduser | name, email, company, department, lastlogin, adgroups (samtliga skrivskyddade, se flaggning) | – |
| `printContactForm.php` | contact | name, web, email | – |
| `printControlForm.php` | control | options, css, js, onload | `printAddOperation`/`printRemoveOperation` mot maps |
| `printDatabaseForm.php` | database | connectionstring | `printReadDbSchemasButton` |
| `printEditForm.php` | edit | target_key, target_table, target_id, date, action, changed_by, before_data, after_data (samtliga readonly) | – (bläddringsbar historiklogg, se "Historik: Ångra/Gör om") |
| `printFooterForm.php` | footer | img, url, text | – |
| `printFormatForm.php` | format | (endast format_id, ingen extra) | – |
| `printGroupForm.php` | group | layers, groups, title, expanded (select), show_meta (select), keywords | `printConfigPreviewButton`, `printAddOperation`/`printRemoveOperation` mot maps OCH groups (två par) |
| `printHelpForm.php` | help | (endast help_id/abstract/info, etiketterna är "Verktygsfält"/"Hjälptext" istället för "Id"/"Beskrivning") | – |
| `printExttoolForm.php` | exttool | url | `printUrlButton` |
| `printKeywordForm.php` | keyword | – | – |
| `printMapstateForm.php` | mapstate | mapurl, state, created, lastuse, preserve (select) | – |
| `printNewForm.php` | new | text, date, reads, deletes | – |
| `printOriginForm.php` | origin | name, web, email | – |
| `printPluginForm.php` | plugin | css_files, css, js_files, js, onload | `printAddOperation`/`printRemoveOperation` mot maps |
| `printProj4defForm.php` | proj4def | code, projection, projectionextent, alias | – |
| `printSearchtableForm.php` | searchtable | mode, ttl, limit, usecentroid (select f/t), database (select), schema, table, searchfield, geometryfield, gidfield | – |
| `printSchemaForm.php` | schema | keywords, contact (select), origin (select), updated, update (select) | `printReadSchemaTablesButton` |
| `printTilegridForm.php` | tilegrid | tilesize | – |

## Entitetsformulär (utökade/komplexa varianter)

Till skillnad från "enkla"-mönstret (föregående omgång) har dessa
betydande typspecifik villkorslogik:

| Fil | Typ | Komplexitet |
|---|---|---|
| `printLayerForm.php` | layer | **Den mest komplexa print*Form-funktionen i hela modulen.** Djupt kaskaderande synlighetslogik (nästlade `<span style="display:none">`-block) beroende på lagertyp (WFS/WMS/GROUP/GEOJSON), stilkonfiguration, ikon-inställningar. Använder genomgående ett mönster där dolda fält "bevaras" via `printHiddenInputs()` när motsvarande synliga fält döljs, så att värdet inte går förlorat vid nästa uppdatering trots att fältet inte visas |
| `printMapForm.php` | map | Störst antal fält av alla enkla formulär (30+), unika knappar: `printWriteConfigButton`, `printExportJsonButton`, `printUrlButton` – detta är alltså formuläret som triggar hela writeConfig-publiceringen |
| `printTableForm.php` | table | Gör ett **extra, eget databasanrop** (`dbh($dbhConnectionString)` + `updated_from_table()`) mitt i renderingen för att visa senaste ändringsdatum – enda formuläret som pratar med en annan databas än konfigurationsdatabasen under rendering |
| `printServiceForm.php` | service | Villkorlig visning baserat på tjänstetyp, med `printHiddenInputs()`-bevarande mönster likt layer/source |
| `printSourceForm.php` | source | Villkorlig visning baserat på tjänstetyp (`File`/`OpenStreetMap` döljer flera fält) |

## Formulärbyggstenar

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `printTilegridForm.php` | (enkelt mönster) | tilesize, standardfält i övrigt |
| `printUndoButton.php` | `printUndoButton($target, $visible=true)` | "Backa"-knappen: postar `target_key`/`target_table`/`target_id` och `command=undo`, med JS-bekräftelsedialog. Se "Historik: Ångra/Gör om" ovan |
| `printUpdateButton.php` | `printUpdateButton($type)` | "Uppdatera"-knappen. Läser den globala `$formChangedGlobal`-flaggan (satt i `manage.php` vid failed update, se tidigare) för att visa den redan i "ändrad"-läge om ett sparförsök just misslyckades |
| `printUpdateForm.php` | (enkelt mönster) | Namnet är missvisande – detta gäller entiteten "update" (en uppdateringsrutin/schema för när data anses föråldrad, kopplat till `updated`-modulen), inte formulärets egen uppdateringsknapp. Fält: `interval` (tidsintervall som text, t.ex. "+1 month" – ser ut som PHP:s `strtotime()`-kompatibla format), `method` (manuellt/automatiskt) |
| `printUpdateSelect.php` | `printUpdateSelect($fullTarget, $configParamValues, $class, $label, $help=false, $options=null, $onchange='')` | Motsvarigheten till `printTextarea()` men för `<select>`-fält istället för fritext. Om `$options` inte anges härleds de automatiskt från `$configParamValues` |
| `printUrlButton.php` | `printUrlButton($url, $type)` | Typknapp som öppnar en URL i ny flik utan att skapa ett nästlat formulär; använder typen för knapptexten, exempelvis karta eller externt verktyg |
| `printViewSwitcher.php` | `printViewSwitcher($view)` | Radioknappar för att växla mellan vyer (`constants/views.php`), autopostar vid ändring |
| `printWriteConfigButton.php` | `printWriteConfigButton($mapId, $changed='f')` | Typknapp som efter bekräftelse anropar `writeConfig.php` i `hiddenFrame` (utan nästlat formulär). Visar "ändrad"-styling om `maps.changed = 't'` (kopplingen till `markMapsChanged()` vi identifierade tidigare, nu bekräftad från UI-sidan) |
| `recordHistoryEdit.php` | `recordHistoryEdit($dbh, $target, $action, $beforeConfig, $afterConfig): bool` | Sparar ett before/after-snapshot som en ny `edits`-rad efter en lyckad `update`, flyttar `edit_cursor` dit, och kastar en ev. kvarvarande "gör om"-gren. Se "Historik: Ångra/Gör om" ovan |

## Anropas med
`manage.php?view=<vy>` (GET för vy) + POST med formulärdata

| Parameter | Beskrivning |
|---|---|
| `view` (GET) | Styr vilken uppsättning kolumner/urvalsformulär som visas överst (t.ex. "Origo"-vyn för kartkonfiguration kontra en annan vy för databaskonfiguration – exakt vilka vyer som finns kräver `viewKeywordCategorized`/`views.php`-konstanten) |
| `<typ>Id` | Väljer ett specifikt objekt av given typ (t.ex. `mapId`, `layerId`, `groupId`) |
| `groupId` / `groupIds` | Håller reda på hela kedjan av nästlade grupper från rot till vald undergrupp |
| `infogroupId` / `infogroupIds` | Håller reda på hela kedjan av nästlade informationsgrupper från rot till vald undergrupp |
| `<typ>IdNew` | Nytt id vid skapande av ett objekt |
| `<typ>IdDel` | Id att radera |
| `update<Fält>` | Ett formulärfälts nya värde vid uppdatering (t.ex. `updateTitle`, `updateAbstract`) |
| `<knapp med kommando>` | Formulärknappens `name`/`value` avslöjar `$type` och `$command` (`copy`/`create`/`delete`/`update`/`operation`/`undo`/`redo`), tolkas av `postButton()` |
| `to<Typ>Id` / `from<Typ>Id` | Vid `operation`-kommandot: lägg till/ta bort ett barn-objekt från en förälder (map/group/classe/infogroup) |
| `target_key` / `target_table` / `target_id` | Vid `undo`/`redo`-kommandot: identifierar vilket objekt historiken gäller (se "Historik: Ångra/Gör om" ovan), postas som dolda fält av `printUndoButton()`/`printRedoButton()` |

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()`, `configTables($dbh)`
- `pkColumnOfTable()`, `array_column_search()`, `pgArrayToPhp()`,
  `assoc_array_values()`, `findAllParents()`

## Filer och funktioner (target-hantering och POST-tolkning)

*Sorterad alfabetiskt efter filnamn (samma ordning som i
`functions/manage/`-mappen).*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `appendUpdatedColumnsToSql.php` | `appendUpdatedColumnsToSql($dbColumns, $sql, $params=[]): array` | Bygger vidare på en SQL-sträng med parameteriserade `kolumn = $N`-par för en `UPDATE`-sats. Tomma värden (inkl. `'{}'`/`'{{}}'`, tomma Postgres-arrayer) blir `NULL`; returnerar SQL och parametrar |
| `applyHistoryNavigation.php` | `applyHistoryNavigation($dbh, $targetKey, $direction): array` | Flyttar `edit_cursor` ett steg `undo`/`redo` för given `$targetKey` och skriver tillbaka rätt snapshot (`before_data`/`after_data`) till objektets tabell. Returnerar `['ok', 'target_table', 'target_id', 'error']`. Se "Historik: Ångra/Gör om" ovan |
| `categories.php` | `categories($config, $catParam): array` | Bygger en nyckelordskategorisering: går igenom en hel tabellkonfiguration och grupperar rad-id:n (`$catParam`, primärnyckelkolumnen) efter deras `keywords`-fält. Lägger alltid till en `"Alla"`-kategori med samtliga id:n överst |
| `categoryPosts.php` | `categoryPosts($post): array` | Filtrerar `$post` till fält vars namn slutar på `Category` |
| `deleteIdSql.php` | `deleteIdSql($id, $tableName): array` | Bygger parameteriserad `DELETE`-sats för given tabell och id; returnerar SQL och parametrar |
| `focusTable.php` | `focusTable($idPosts): string\|null` | Avgör vilken tabell som är "i fokus" utifrån vilka `*Id`-fält som postats – prioriterar map/database/schema/group före övriga typer, annars härleds tabellen från det första postade id-fältets namn |
| `hasStringKeys.php` | `hasStringKeys(array $array): bool` | Kontrollerar om en array har minst en textnyckel (dvs. är associativ snarare än numeriskt indexerad). **Ingen användning observerad** – se flaggning |
| `historyStateForTarget.php` | `historyStateForTarget($dbh, $target): array` | Läser `edit_cursor`/`edits` för given target och räknar från grunden ut `['undo', 'redo', 'current_edit_id', 'edits', 'index']`. Används av `printHistoryButtons()` för att avgöra vilka knappar som ska visas |
| `idPosts.php` | `idPosts($post): array` | Filtrerar `$post` till fält vars namn slutar på `Id`, med undantag för operationernas `from/to`-fält för map, group, classe och infogroup |
| `isArrayColumn.php` | `isArrayColumn($column): bool` | Kontrollerar om en given kolumn är en Postgres-array-kolumn, genom att slå upp den mot listan i `constants/arrayColumns.php`. Avslutar programmet (`die()`) om `$column` inte är en icke-tom sträng |
| `makeTargetFull.php` | `makeTargetFull($target, $configTablesOrDbh): array` | Tar en basic (eller full) target och returnerar en full target, genom att slå upp konfigurationen via `targetConfig()` om den saknas. Avslutar programmet om indata inte är en giltig target |
| `markMapsChanged.php` | `markMapsChanged(&$dbh, $mapIds): void` | Sätter `maps.changed = 't'` för samtliga angivna kartor i en enda batch-SQL (flera `UPDATE`-satser konkatenerade med `; `). **Bekräftar tidigare hypotes:** detta är motparten till `markMapUnchanged()` i writeConfig-modulen – manage-modulen flaggar en karta som "ändrad, behöver publiceras om" varje gång något som påverkar den redigeras, och writeConfig-modulen nollställer flaggan efter lyckad publicering |
| `objectHistoryKey.php` | `objectHistoryKey($target): string` | Bygger den stabila historiknyckeln `<typ>:<id>` (`target_key`) för ett objekt, utifrån `targetType()`/`targetId()`. Se "Historik: Ångra/Gör om" ovan |
| `postButton.php` | `postButton($post): string\|null` | Hittar namnet på den POST-parameter vars namn slutar på `Button` – det är detta namn (`<typ>Button`) som `manage.php` sedan bryter isär för att få fram `$type` |
| `printAddOperation.php` | `printAddOperation($target, $addToTable, $buttontext, $inheritPosts)` | Skriver ut ett litet formulär: en dropdown med tillgängliga föräldrar (t.ex. kartor eller grupper) + en knapp som postar `operation`-kommandot för att lägga till `$target` i den valda föräldern |
| `printChildSelect.php` | `printChildSelect($target, $column, &$thClass, $heading, $inheritPosts, $groupLevel=1, $selectedValue=null)` | Den mest komplexa av dessa byggstenar: skriver ut en kolumn i "barn-urvalsraden" (t.ex. vilka lager/grupper/kontroller finns i vald karta). Hanterar specialfall för `schemas`/`tables` och nästlade `groups`/`infogroups`, där respektive id-kedja byggs baserat på djup |
| `printAddRemoveOperations.php` | `printAddRemoveOperations($target, $operationTables, $inheritPosts, $labels=array())` | Gemensam renderer för add/remove-operationer. `exclusiveOperationGroups.php` kan ange singleton-poster eller grupper av ömsesidigt exklusiva föräldratabeller; add-knappen döljs när målet redan finns i någon förälder i gruppen |
| `printConfigPreviewButton.php` | `printConfigPreviewButton($mapId, $group=null, $layer=null)` | Typknapp som öppnar en förhandsgranskning via `writeConfig.php?getHtml=y` i en ny flik, utan att skriva till disk eller skapa ett nästlat formulär. Kan begränsas till en specifik grupp eller ett specifikt lager |
| `printCopyButton.php` | `printCopyButton($type)` | Enkel "Spara kopia"-knapp (`command=copy`) |
| `printDeleteButton.php` | `printDeleteButton($target, $deleteConfirmStr, $inheritPosts)` | Raderaknapp med `onclick`-bekräftelse; skickar delete-kommandot via det omgivande entitetsformuläret, utan att skapa ett eget formulär. **Visas bara om `$viewDepthGlobal == 1`** (se flaggning – innebär att radering bara är möjlig för toppnivåobjekt, inte nästlade) |
| `printExportJsonButton.php` | `printExportJsonButton($mapId)` | Typknapp som laddar ner kartans JSON-konfiguration via `writeConfig.php?getJson=y&download=y` i den dolda iframen (`hiddenFrame`) så sidan inte navigerar bort; inget nästlat formulär |
| `printHeadForm.php` | `printHeadForm($tableConfig, $inheritPosts)` | Skriver ut en enskild kolumn i toppradens urvalsformulär: en dropdown för att välja befintligt objekt (med ev. nyckelordskategorisering) + ett textfält och knapp för att skapa nytt. Dropdownens värde är alltid tabellens id-kolumn, men för `contact`/`origin` visas `name` som etikett och för `edit` visas `target_key` (objektet ändringen gäller) istället för det annars intetsägande `edit_id`:t – ordningen hålls kronologisk (via `preserveOrder` i `printSelectOptions()`) eftersom `all_from_table()` redan läser raderna sorterade på `edit_id` |
| `printHeadForms.php` | `printHeadForms($view, $configTables, $focusTable, $inheritPosts)` | Skriver ut hela toppraden av urvalsformulär, en `printHeadForm()`-kolumn per tabell som ingår i vald `$view` (styrt av `constants/views.php`). Placerar `$focusTable` först och ger den fokus-styling |
| `printHelpButton.php` | `printHelpButton($type, $configParam=null, $buttonText='?', $buttonClass='smallHelpButton')` | Liten "?"-knapp bredvid ett fält, öppnar/togglar hjälptext för just det fältet (`help.php?id=<type>[:<configParam>]`) i topFrame |
| `printHiddenInputs.php` | `printHiddenInputs($inheritPosts)` | Skriver ut ett dolt `<input>` per nyckel/värde i `$inheritPosts`, för att bevara navigeringskontext genom formulärinskick |
| `printHistoryButtons.php` | `printHistoryButtons($target, $dbh=null, $inheritPosts=array())` | Skriver ut "Backa"/"Gör om"-knapparna för given target, efter att ha frågat `historyStateForTarget()` om vilka som är tillgängliga. Öppnar/stänger en egen databaskoppling om ingen skickas in. Se "Historik: Ångra/Gör om" ovan |
| `printInfoButton.php` | `printInfoButton($basicTarget)` | Typknapp som öppnar `info.php` i `topFrame` för given target, utan eget formulär |
| `printMultiselectButton.php` | `printMultiselectButton($configParam, $value=null, $textareaId, $buttonText='+', $buttonClass='smallMultiselectButton')` | Knapp som öppnar multiselect-verktyget i topFrame för ett givet fält, via samma `<textareaId>::<tabell>:<värden>`-kodning vi dokumenterat i `multiselect.md` |
| `printReadDbSchemasButton.php` | `printReadDbSchemasButton($databaseId)` | Typknapp som efter bekräftelse anropar `read_db_schemas.php` i `hiddenFrame` och skickar om databasurvalet efter 1 sekund. Avbryt stoppar båda åtgärderna; inget nästlat formulär |
| `printReadSchemaTablesButton.php` | `printReadSchemaTablesButton($schemaId)` | Motsvarande för `read_schema_tables.php`; Avbryt stoppar anrop och formulärresubmit |
| `printRemoveOperation.php` | (samma mönster som `printAddOperation.php`, se ovan) | Motsatsen till `printAddOperation()` – kräver dessutom `findParents()` [common] för att bara visa de föräldrar objektet faktiskt tillhör (kan inte tas bort från en förälder det inte är kopplat till) |
| `printRedoButton.php` | `printRedoButton($target, $visible=true)` | "Gör om"-knappen: postar `target_key`/`target_table`/`target_id` och `command=redo`, med JS-bekräftelsedialog. Se "Historik: Ångra/Gör om" ovan |
| `printSelectOptions.php` | `printSelectOptions($optionValues, $selectedValue=null, $preserveOrder=false)` | Skriver ut `<option>`-element för en `<select>`. Sorterar alfabetiskt om arrayen är associativ (id→namn), om inte `$preserveOrder` är satt (används av `edit`-dropdownen för att bevara kronologisk ordning trots att etiketten är `target_key`, inte datumet). **Ovanligt val-etikettmönster**, se flaggning |
| `printTextarea.php` | `printTextarea($fullTarget, $configParam, $class, $label, $help=false, $sizePosts=array(), $readonly=false)` | Den mest centrala byggstenen i hela manage-modulen – skriver ut ett enskilt redigerbart fält som ett `<textarea>`. Städar Postgres-arraysyntax för visning, bevarar användarens tidigare valda storlek/scrollposition (via `$sizePosts`, kopplat till `sizePosts.js`-liknande dolda fält), visar en hjälpknapp om hjälptext finns, och visar en multiselect-knapp om fältet är konfigurerat som "multiselectable" |
| `sizePosts.php` | `sizePosts($post): array` | Filtrerar `$post` till bredd-/höjd-/scrollrelaterade fält, och normaliserar `new*`-prefixade nycklar (från senaste formulärinskicket) till samma nyckelformat som de ursprungliga (`width*`/`height*`/`scroll*`) – nyare värden skriver över äldre i sammanslagningen |
| `sqlForOperation.php` | `sqlForOperation($operation, $child, $parent): array` | Bygger en parameteriserad UPDATE-sats som lägger till/tar bort ett barn-id ur förälderns array-kolumn; returnerar SQL och parametrar |
| `sqlForUpdate.php` | `sqlForUpdate($fullTarget, $updatePosts): array` | Bygger en parameteriserad fullständig UPDATE-sats för en target baserat på postat formulärdata; returnerar SQL och parametrar |
| `tableConfigs.php` | `tableConfigs($table, $configTablesOrDbh)` | Hämtar konfigurationen för en tabell antingen från en databaskoppling (färsk fråga) eller från en redan inläst `configTables`-array (cachat) – avgörs via `is_resource()`/`instanceof PgSql\Connection` |
| `targetConfig.php` | `targetConfig($target, $configTablesOrDbh=null)` | Slår upp/returnerar hela konfigurationen för en target, oavsett om den redan är "full" eller bara "basic" |
| `typeHelps.php` | `typeHelps($type, $helps): array` | Filtrerar den globala listan av hjälptext-id:n (`help_id`, format `<typ>:<fält>`) till de som gäller en specifik typ, och returnerar bara fältdelen. Detta är mekaniken bakom `in_array($fältnamn, $helps)`-kontrollerna vi sett i varje `print*Form`-funktion – `$helps` som skickas till de funktionerna är redan filtrerat via denna funktion i `manage.php`s entry point |
| `updatedFullTarget.php` | `updatedFullTarget($fullTarget, $updatePosts): array` | Bygger en ny full target där varje kolumns värde ersätts med motsvarande `update<Kolumn>`-fält från `$updatePosts` (eller tom sträng om inget postades för den kolumnen). Array-kolumner (enligt `isArrayColumn()`/`constants/arrayColumns.php`) omsluts automatiskt med Postgres-array-syntax `{...}`. Detta är steget som förvandlar "vad användaren skrev i formuläret" till "vad som ska stå i databasen", och används av `sqlForUpdate()` innan `appendUpdatedColumnsToSql()` bygger själva SQL-strängen |
| `updated_from_table.php` | `updated_from_table($dbh, $tableWithSchema): array` | Systerfunktion till `updated_from_table2()` (updated-modulen) – hämtar senaste `pg_xact_commit_timestamp` för en tabell, men returnerar **bara tidsstämpeln**, inte `xmin` som `updated_from_table2()` gör. Används av `printTableForm.php` för att visa senast-ändrad-datum direkt i formuläret (till skillnad från `updated.php`-modulens fristående JSON-endpoint) |
| `updatePosts.php` | `updatePosts($post): array` | Filtrerar `$post` till fält vars namn börjar med `update` – detta är alla postade formulärfältvärden redo att skrivas till databasen |
| `validateUpdate.php` | `validateUpdate($updatePosts, $configTables, &$updateValid)` | Validerar **endast** fält som är markerade som "multiselectable" (`constants/multiselectables.php`) – kontrollerar att varje kommaseparerat värde som postats faktiskt existerar som ett giltigt id i motsvarande tabell. Sätter `$updateValid` (skickad by reference) och visar ett JS `alert()` vid fel. **Notera:** fält som inte är multiselectable valideras alltså inte alls av denna funktion – se flaggning nedan |
| `viewKeywordCategorized.php` | `viewKeywordCategorized($view): array` | Filtrerar den globala listan av "tabeller som ska nyckelordskategoriseras" (`constants/keywordCategorized.php`) till bara de tabeller som är relevanta för vald `$view` (`constants/views.php`). Specialfallet `$view == 'Allt'` (eller tom) returnerar hela listan okategoriserat av vy |

**Filsystem:** läser QGIS-projektfiler (`.qgs`) direkt från disk vid
uppdatering av layer/source, samma mönster som i `info.php` och
`writeTablesForAllLayers.php`.

**Databas:** läser och skriver till praktiskt taget samtliga
konfigurationstabeller (via `configTables()` och dynamiskt genererad SQL).

## JS-filer och funktioner

**Plats:** `adm/js-functions/manage/`
**Laddas:** inline i `<script>`-taggen i `<head>`, via samma
`includeDirectory()`-mönstret som används för PHP (se ARCHITECTURE.md)

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `formChangeButton.js` | `formChangeButton()` | Lägger till en CSS-klass (`change`) på ett formulärs "Uppdatera"-knapp så fort något fält i formuläret ändras (utom dolda fält), för att visuellt signalera osparade ändringar |
| `initMessageListener.js` | `initMessageListener()` | Sätter upp en global `postMessage`-lyssnare för hela sidan. Validerar avsändarens origin (måste matcha `window.location.origin`) och att datan har rätt form innan den hanteras. Hanterar tre fall: `action:'close'` utan `targetId` (stäng topFrame, t.ex. från help.php), `action:'resize'` (justera topFrame-höjd), och `targetId`+`value` (fyll i ett textarea-fält med värde från multiselect-verktyget, samt uppdatera den tillhörande multiselect-knappens `value`-attribut så nästa öppning av multiselect visar rätt förval) |
| `preservePageScroll.js` | `preservePageScroll()` | Sparar scrollpositionen i `sessionStorage` vid formulärinskick, och återställer den efter att sidan laddats om – kompenserar för att `manage.php` är en traditionell serverrenderad sida där varje åtgärd (spara, välja ett nytt objekt) innebär en full sidomladdning |
| `resizeIframe.js` | `resizeIframe(iframe)` | Anpassar en iframes höjd efter dess faktiska innehåll, genom att tillfälligt sätta höjden till `1px` och sedan mäta `scrollHeight` i nästa animationsframe |
| `toggleTopFrame.js` | `toggleTopFrame(type)` | Visar/döljer den delade toppmonterade iframen (`#topFrame`). Håller reda på vilken typ av innehåll som visas (global variabel `topFrame`, deklarerad i `manage.php`s inline-script) – klick på samma typ igen döljer den, klick på en annan typ byter innehåll och scrollar upp |
| `updateSelect.js` | `updateSelect(id, array)` | Fyller om en `<select>`-listas alternativ med ett nytt innehåll. Specialhantering för element vars id slutar på `Categories`: värdet sätts till det råa (understreck-separerade) kategorinamnet men visningstexten har understreck ersatta med mellanslag |

## Kommunikationsmönster: iframe ↔ huvudsida

`manage.php` bygger på ett återkommande mönster där verktyg som körs i
en iframe (`help.php`, `multiselect.php`, och potentiellt andra) pratar
med huvudsidan via `postMessage`:

## Checklista: lägga till en ny entitetstyp

En sammanfattning för den som vill lägga till en helt ny, enkel
entitetstyp (t.ex. en ny referenstabell liknande `keyword`/`contact`)
utan att behöva läsa alla 78 filer i `functions/manage/` i detalj:

1. **Databas:** skapa tabellen i `map_configs`-schemat med en
   `<typ>_id`-primärnyckel (se `targetIdColumn()` för specialfall som
   `proj4defs`/`code`), samt gärna `abstract`/`info`-kolumner (mönstret
   alla enkla formulär använder för hjälptexter, se `typeHelps()`).
2. **Konstanter (endast vid behov):** lägg till i `constants/arrayColumns.php`
   om något fält är en Postgres-array, i `constants/multiselectables.php`
   om ett fält ska ha en multiselect-knapp, och i `constants/views.php`/
   `constants/keywordCategorized.php` om typen ska synas i en viss vy
   eller kunna nyckelordskategoriseras.
3. **Formulärfunktion:** skapa `functions/manage/print<Typ>Form.php`.
   Följer typen "enkla mönstret" (se ovan) räcker det att kopiera en
   befintlig enkel `print*Form`-fil (t.ex. `printKeywordForm.php`) och
   byta fältnamn/etiketter. Behövs villkorlig visning (döljda fält
   beroende på annat fältvärde), se `printLayerForm.php`/
   `printServiceForm.php` för det etablerade "dölj men bevara"-mönstret
   med `printHiddenInputs()`.
4. **Koppla in i entry point:** `manage.php` härleder `$type`/
   `$typeTableName` dynamiskt från vilken knapp/vilket fält som
   postades (se `postButton()`/`focusTable()`), så själva
   dispatch-koden i `manage.php` behöver normalt inte ändras – nya
   typer upptäcks automatiskt så länge `print<Typ>Form()` finns och
   namnges enligt konventionen `print` + `ucfirst($type)` + `Form`.
5. **Hjälptexter (valfritt):** lägg till rader i `helps`-tabellen med
   `help_id` på formatet `<typ>` eller `<typ>:<fält>` (se help.md).
6. **Dokumentation:** lägg till en rad i tabellen under "Mönster: enkla
   entitetsformulär" (eller "Entitetsformulär (utökade/komplexa
   varianter)" om typen har villkorslogik) ovan i det här dokumentet.

## Kända begränsningar / observationer

- ~~Genomgående användning av `eval()` för dynamiska funktionsanrop~~ –
  **åtgärdat.** `manage.php` innehöll tidigare fem `eval()`-anrop: tre för
  att bygga dynamiska variabelnamn (`${$table}Categories`) och två för
  `print<Typ>Form()`-dispatchen. Dessa är nu ersatta med:
  - en samlad array `$categoriesByTable[$table]` istället för de
    dynamiska `${$table}Categories`-variablerna (skrivs vid inläsning,
    läses vid formulärbygge och JS-variabelgenerering), och
  - variabel-funktionsanrop (`$formFunction = 'print'.ucfirst($childType).'Form';
    $formFunction(...);`) istället för `eval('print'.ucfirst($childType).'Form(...)');`.

  Funktionaliteten är oförändrad – samma dynamiska, generiska dispatch
  baserat på tabellnamn/typnamn – men koden är nu sökbar och
  verktygsstödd (IDE/statisk analys ser anropen), utan `eval()`s
  kodinjektionsrisk.
- **Extremt hög cyklomatisk komplexitet i en enda fil.** 705 rader med
  djupt nästlade villkor, och minst fem distinkta "typer av objekt som
  kan vara valda" (map/database/schema/group-kedja/övrigt) hanteras i
  sekvens i samma fil, med tydlig `unset()`-städning mellan varje sektion
  för att undvika att variabler läcker mellan grenarna. Det här mönstret
  (en enda lång fil med väldokumenterade kommentarer som beskriver varje
  steg) är faktiskt **ovanligt väl kommenterat** jämfört med resten av
  kodbasen – varje sektion har en förklarande kommentar om vad den gör
  och varför. Det gör filen begriplig trots sin storlek, men den skulle
  sannolikt vinna på att brytas upp i namngivna funktioner (en per FAS
  ovan) även om inga rader ändras i sak – ren extraktion utan
  beteendeändring, vilket är en lågriskrefaktorering.
- **`unset($_POST, $_GET)` överst** – samma försiktighetsmönster vi sett
  i read_db_schemas/read_schema_tables, konsekvent tillämpat.
- **`array_filter($_POST, ...)` behåller `"0"` som värde men filtrerar
  bort tomma strängar** – ett medvetet, korrekt hanterat specialfall
  (PHP:s `empty("0")` är sant, vilket annars skulle förlora legitima
  "0"-värden i formulär, t.ex. en opacitet eller skala satt till 0).
  Bra exempel på uppmärksamhet mot en klassisk PHP-fallgrop.
- **Delete-skyddet (`findAllParents` + `assoc_array_values`) återanvänder
  exakt samma mönster som `info.php`s "Används av"-funktion** – bra
  konsekvens, och bekräftar att `findAllParents`/`assoc_array_values` är
  kärnfunktioner värda extra uppmärksamhet vid eventuell framtida
  ändring, eftersom de skyddar mot dataförlust på minst två ställen.
- **QGIS-autoifyllnadslogiken vid update (rad ~150–170 i del 1) dupliceras
  konceptuellt med `writeTablesForAllLayers.php` och delar av `info.php`**
  (alla tre läser `.qgs`-filer för att extrahera information). Om denna
  logik någonsin behöver ändras (t.ex. ny QGIS-version med annat
  XML-format) måste tre olika ställen uppdateras. Kandidat för att
  bryta ut till en delad common-funktion, t.ex. `qgisProjectMetadata($service,
  $sourceId)`, vid framtida förenkling.
- **Global användning av variabler som `$viewDepthGlobal`,
  `$formChangedGlobal`** (namngivna med `Global`-suffix, till skillnad
  från writeConfig-modulens råa `GLOBAL`-nyckelord utan
  namnkonvention) – en medveten, mer läsbar konvention för globala
  variabler jämfört med writeConfig. Värt att notera som en god
  praxis-skillnad mellan de två stora modulerna.
- **Inkonsekvent felhantering vid databasfel:** vid `pg_query()`-fel
  byggs ett JS `alert()` med rått `pg_last_error()`-innehåll
  (escapat för JS-strängen, men inte HTML-escapat) som visas direkt för
  administratören. Detta exponerar interna databasfelmeddelanden
  (kan innehålla tabell-/kolumnnamn, SQL-fragment) till den inloggade
  administratören – rimligt för en intern adminpanel med betrodda
  användare, men värt att notera som en skillnad mot vad man skulle
  acceptera i en publik felhantering.
- Ingen `strict_types` eller parametertypning (gäller hela filen,
  konsekvent med övriga äldre delar av kodbasen).
- **⚠️ `initMessageListener.js` saknar null-kontroll på
  `multiselectButton`:**
```js
  const multiselectButton = document.getElementById(targetId + ":multiselect");
  let multiselectButtonValue = multiselectButton.getAttribute('value');
```
  Om inget element med id `<targetId>:multiselect` finns i DOM:en (t.ex.
  om ett fält kan fyllas via multiselect-verktyget utan att ha en
  tillhörande multiselect-knapp, eller om knappens id-konvention någon
  gång avviker), kastar detta ett `TypeError: Cannot read properties of
  null` och stoppar resten av händelsehanteraren. Värt att lägga till
  samma typ av null-kontroll som redan finns för `textarea` några rader
  ovanför.
- **Skört strängmönster för att uppdatera multiselect-knappens
  `value`-attribut:**
```js
  multiselectButtonValue.replace(/^([^:]*::[^:]*).*$/, '$1:' + value);
```
  Denna regex förutsätter exakt samma `<textareaId>::<tabell>:<värden>`-
  format som vi dokumenterade i `multiselect.md` (se
  `multiselect.php`s query-parameterparsning). De två platserna – här
  och i `multiselect.php` – måste hållas i synk manuellt; om formatet
  någonsin ändras på ena stället måste det ändras på båda. Ytterligare
  ett skäl (utöver läsbarhetsargumentet vi redan noterat i
  `multiselect.md`) att överväga att ersätta den hopkodade strängen med
  separata, tydligt namngivna data-attribut.
- **Två funktioner med samma namn (`updateSelect`) i olika moduler:**
  denna fils `updateSelect(id, array)` (manage) skiljer sig i
  **parameterordning och beteende** från `update(menu)` i
  multiselect-modulen (som vi dokumenterade tidigare som `update.js` –
  notera att den filen faktiskt exporterar en funktion vid namn
  `update`, inte `updateSelect`, så namnkonflikten är mindre akut än
  den såg ut vid första anblick, men värt att dubbelkolla att inga
  andra js-mappar har en `updateSelect`-funktion med annan signatur,
  eftersom alla js-filer i en mapp laddas globalt utan namnrymder).
- **`formChangeButton()` letar bara efter en knapp med exakt
  `value="update"`** – om ett formulär har flera submit-knappar (t.ex.
  separata knappar för "spara" och "kopiera" som vi sett i
  `manage.php`s `$command`-hantering: `copy`/`create`/`delete`/`update`/
  `operation`), får bara `update`-knappen den visuella
  ändrings-markeringen. Rimligt om det är den enda knappen som ska
  visa "osparade ändringar", men värt att bekräfta att det är avsiktligt
  och inte ett förbiseende för de andra kommandona.
- **Konsekvent, modern JS-stil** (`const`/`let`, arrow functions,
  destrukturering, `querySelectorAll`/`forEach`) genomgående i samtliga
  sex filer – till skillnad från flera äldre PHP-delar av kodbasen.
  Bekräftar att JS-lagret överlag är nyare/mer omsorgsfullt underhållet
  än en del av den äldre PHP-koden (t.ex. news/authorization).
- Ingen av filerna har enhetstester eller motsvarande, men koden är
  tillräckligt enkel och fri från globala sidoeffekter (förutom delade
  DOM-element och den globala `topFrame`-variabeln) att den skulle vara
  relativt lätt att testa isolerat om det blir aktuellt.
- **✅ SQL-parametrisering i manage.php:s CRUD-flöde:** `insertIdSql.php`,
  `deleteIdSql.php`, `sqlForUpdate.php` och `sqlForOperation.php` returnerar
  nu SQL med placeholders samt separata parametrar. `manage.php` kör satserna
  med `pg_query_params()` i en explicit transaktion. `read_json.php` och
  övriga importrelaterade INSERT-satser ingår inte i denna ändring.
- **Flera funktioner avslutar hela programmet med `die()` vid ogiltiga
  argument** (`makeBasicTarget`, `makeFullTarget`, `makeTargetFull`,
  `isArrayColumn`). Detta är ett medvetet "fail fast"-mönster för
  interna programmeringsfel (fel typ av argument skickat av misstag),
  snarare än för förväntade felsituationer med användarinput – rimligt
  för hjälpfunktioner som bara anropas internt med redan kontrollerad
  data, men det gör dem svåra att återanvända i sammanhang där ett
  ogiltigt anrop bör hanteras mjukare (t.ex. return `false`/kasta ett
  exception som kan fångas). Genomgående mönster värt att känna till
  innan man refaktorerar kring dessa funktioner.
- **`hasStringKeys.php` har ingen synlig användning** i någon av de 77
  filerna i `functions/manage/` eller i `manage.php` självt (bekräftat
  efter fullständig genomgång). Sannolikt kvarlämnad död kod snarare än
  en funktion som används längre fram – kandidat för borttagning vid
  framtida städning, om inget nytt användningsställe dyker upp.
- **`isArrayColumn()` läser sin konstant med ett funktionslokalt
  `require()`** (`require("./constants/arrayColumns.php");` inuti
  funktionskroppen) snarare än att konstanten skickas in som parameter
  eller läses en gång centralt. Fungerar (PHP cachar inte `require` per
  session, men körs bara en gång per anrop av funktionen så
  prestandapåverkan är minimal), men avviker från mönstret i t.ex.
  `deleteIdSql.php`/`markMapsChanged.php` som också gör motsvarande
  lokala `require` av `configSchema.php` – **detta är alltså ett
  konsekvent mönster i manage-modulen** (till skillnad från
  writeConfig-modulen där konstanter oftast lästes högre upp), värt att
  notera som en skillnad i kodstil mellan de två stora modulerna snarare
  än en bugg i endera.
- **`categories()`s namn `"Alla"` är hårdkodat på svenska** direkt i
  logiken (inte via någon översättningsfunktion som `toSwedish()`) –
  konsekvent med att UI-text genomgående är på svenska i hela
  kodbasen, men värt att notera som en skillnad mot `toSwedish()`-
  mönstret som annars använts för att översätta interna namn.
- Ingen av filerna har `strict_types` eller fullständig parametertypning
  (returtyper anges ibland i kommentarer men inte i kod), konsekvent
  med övriga äldre delar av kodbasen.
- ~~⚠️ Trolig bugg i printGroupForm.php~~ **KORRIGERAT:** `'preview'` är
  ett medvetet, dedikerat Origo-karta-id som enbart används av
  adminverktyget för förhandsgranskning (inte kartans faktiska
  `mapId`). Samma mönster används konsekvent i `printLayerForm.php`
  (`printConfigPreviewButton('preview', null, targetId($layer))`).
  Ingen bugg. Fler detaljer om detta koncept väntas.
- **⚠️ Möjlig bugg/skräpvärde i `printHiddenInputs.php`:**
```php
  if ($idKey != 'layerCategory')
```
  Detta undantag stavas `layerCategory` (singular, ingen "s"), men de
  faktiska category-fälten som genereras av `categoryPosts()` (se
  tidigare omgång) namnges efter tabellnamnet, t.ex. `layersCategory`
  (plural, matchar tabellnamnet `layers`). Om `layerCategory` (singular)
  aldrig faktiskt förekommer som nyckel i `$inheritPosts`, gör detta
  undantag **ingenting** i praktiken – filtret matchar aldrig. Antingen
  är detta en stavningsbugg (borde vara `layersCategory`) som gör att ett
  fält läcker igenom som en dold input när det borde exkluderas, eller
  så är exkluderingen överflödig kvarleva. **Bör verifieras** mot vad
  som faktiskt är avsett att filtreras bort.
- **`printAduserForm.php` skickar `true` som sjätte argument till
  `printTextarea()`** för samtliga fält utom `abstract`/`info` (t.ex.
  `printTextarea($aduser, 'name', ..., $sizePosts, true)`) – detta är
  sannolikt en "read-only"-flagga (rimligt för AD-användardata som
  synkas in automatiskt och inte ska redigeras manuellt i adminverktyget),
  men den exakta innebörden bekräftas först när `printTextarea.php`
  granskas.
- **Kommentar-död kod i `printDeleteButton.php`:** ett helt block (att
  trimma `$inheritPosts` baserat på objektets typ innan radering) är
  utkommenterat. Ofarligt men gör filen svårare att läsa – kandidat för
  borttagning om logiken verkligen inte längre behövs, eller
  återinförande med förklaring om den faktiskt saknas.
- **`printDeleteButton()`s villkor `$viewDepthGlobal == 1`** betyder att
  raderaknappen bara visas för det **första** nivån av vald hierarki
  (t.ex. den valda kartan, men inte en nästlad grupp längre ner, eller
  ett valt lager om det nås via flera kaskaderande urval). Det är
  oklart om detta är en avsiktlig begränsning (för att undvika
  oavsiktlig radering djupt ner i en hierarki utan tydlig kontext) eller
  en ofullständig implementation. Given hur central raderingsfunktionen
  är, **rekommenderas att bekräfta avsikten** med denna begränsning.
- **`printFormatForm.php` saknar en tydlig extra beskrivning** – till
  skillnad från övriga i mönstret har den bara `format_id`/`abstract`/
  `info`, inga typ-specifika fält alls. Bekräftar att `format` är en
  mycket enkel referenstabell (troligen bara en lista över tillåtna
  bildformat, jämför `layer['format']`-fältet vi sett i writeConfig).
- **`printHeadForm.php` och `printChildSelect.php` innehåller djup,
  delvis duplicerad specialfallslogik** för hur `schemas`/`tables`-
  kolumner ska visas med förkortade etiketter (prefix-strippning av
  förälderns id) jämfört med övriga kolumntyper. Detta är samma
  database→schema→table-kaskad vi såg i `manage.php`s entry point,
  implementerad på liknande sätt på två separata ställen. Kandidat för
  att brytas ut till en delad hjälpfunktion (t.ex. `stripParentPrefix
  ($ids, $parentId)`) om dessa filer någonsin refaktoreras.
- **Inkonsekvent stil i `printHeadForm.php`:** blandar `<<<HERE`-heredoc-
  block med vanlig `echo '...'`/`.`-konkatenering inom samma funktion,
  samt använder `require()` för en konstant (`keywordCategorized.php`)
  mitt i funktionskroppen (samma mönster som redan noterat för andra
  manage-filer).
- Ingen `strict_types` eller parametertypning i någon av filerna,
  konsekvent med resten av manage-modulen.
- **`printSelectOptions.php`s etikettmönster är svårtytt:**
```php
  $selectOption = "$selectOption>".ltrim(substr($label, strrpos($label,',')), ',')."</option>";
```
  Detta tar **allt efter sista kommatecknet** i `$label` som visningstext
  (eller hela `$label` om inget kommatecken finns, då `strrpos` returnerar
  `false` och `substr($label, false)` blir hela strängen). Oklart utan
  mer kontext varför – möjligen ett sätt att visa bara "sista delen" av
  ett sammansatt namn (t.ex. om `$label` någon gång innehåller
  "Kommun, Förvaltning, Namn" och bara "Namn" ska visas)? Detta är en
  icke uppenbar detalj värd att fråga om, eftersom den påverkar hur
  **alla** dropdown-menyer i hela manage-modulen visar sina etiketter.
- **Nästlade formulär i knapphelpers:** tidigare skrev flera helpers ut
  egna formulär inuti entitetsformuläret. Webbläsaren ignorerade då vissa
  `<form>`-taggar och bekräftelser/submit kunde bli beroende av renderings-
  ordningen (bl.a. en tom `<form>` i `printInfoButton()`). **Åtgärdat:**
  `printDeleteButton()` skickar via det befintliga entitetsformuläret;
  `printInfoButton()`, `printWriteConfigButton()`,
  `printReadDbSchemasButton()`, `printReadSchemaTablesButton()`,
  `printConfigPreviewButton()`, `printExportJsonButton()` och
  `printUrlButton()` skriver inga formulär. Bekräftelserna använder
  knappens `onclick` och Avbryt stoppar åtgärden.
- **`printTableForm.php` anropar `updated_from_table()`** (utan `2`-
  suffix, till skillnad från `updated_from_table2()` vi dokumenterade i
  `updated.md`) – detta bekräftar att båda varianterna faktiskt används,
  på olika ställen i kodbasen. `updated_from_table()` (utan `2`) finns i
  `functions/common/` enligt filträdet men är ännu inte granskad.
  Kandidat att jämföra de två när den filen ses, för att förstå om
  skillnaden är meningsfull eller historisk.
- **God konsekvens i "dölj men bevara"-mönstret** (`printHiddenInputs()`
  med tidigare värden när ett fält döljs pga villkorslogik) i
  `printLayerForm.php` och `printServiceForm.php` – detta är ett
  genomtänkt sätt att undvika att data går förlorad när administratören
  växlar mellan lägen (t.ex. byter tjänstetyp och byter sedan tillbaka)
  utan att spara emellan. Bra mönster värt att bevara vid eventuell
  förenkling av dessa formulär.
- Fortsatt ingen `strict_types`/parametertypning, konsekvent med resten
  av manage-modulen.
- **God arkitektur i target-infrastrukturen:** trots den genomgående
  avsaknaden av typning är detta faktiskt ett väldesignat, konsekvent
  abstraktionslager – varje funktion har ett tydligt, smalt ansvar, och
  `tableConfigs()`s "färskt eller cachat"-abstraktion är ett elegant sätt
  att återanvända samma kod oavsett om man har en databaskoppling eller
  en redan inläst konfiguration. Detta är sannolikt den mest
  välstrukturerade delen av hela manage-modulen och en bra förebild för
  hur övriga delar (t.ex. `printLayerForm.php`s djupa villkorslogik)
  skulle kunna struktureras om vid framtida förenkling.
- **`tableConfigs()` avslutar processen helt (`exit(1)`)** om varken en
  databaskoppling eller en configTables-array med den efterfrågade
  tabellen ges. Samma "fail fast för programmeringsfel"-mönster som
  övriga target-funktioner, men `exit(1)` istället för `die("meddelande")`
  – ger alltså ingen förklarande text till skillnad från systerfunktionerna,
  vilket gör felsökning svårare om detta någonsin triggas oväntat.
- **`printUpdateForm.php`s namnkrock med begreppet "update"** (en
  databasentitet för uppdateringsscheman, kontra `printUpdateButton()`
  för formulärets spara-knapp, kontra `sqlForUpdate()` för SQL-generering)
  är rent namnmässigt förvirrande vid en första anblick men fullt
  logisk vid närmare granskning – värt att notera i dokumentationen
  (görs härmed) så framtida läsare inte blandar ihop de tre helt
  orelaterade "update"-koncepten.
- Fortsatt konsekvent avsaknad av `strict_types`/parametertypning.
- **⚠️ Ofullständig validering – `validateUpdate()` kontrollerar bara
  multiselectable-fält.** Detta är värt att lyfta fram tydligt: den
  enda serversidesvalideringen som sker innan ett `UPDATE` körs mot
  databasen är kontrollen att kommaseparerade referens-id:n (för
  multiselect-fält som `adusers`, `adgroups`, m.fl.) faktiskt existerar.
  **Alla andra fält** (fritext, siffror, ja/nej-val som `visible`/
  `queryable`, URL:er, JSON-liknande fält som `style_config`/`options`,
  etc.) skrivs till databasen **utan någon validering av innehåll,
  format, eller ens att de är syntaktiskt giltiga** för sitt avsedda
  ändamål. Detta förklarar sannolikt varför `writeConfig.php` har sin
  egen `json_decode($json) === null`-kontroll som sista skyddsnät – en
  administratör kan mycket väl spara ogiltig data i `manage.php` som
  först upptäcks långt senare, vid publicering. Om ni någon gång vill
  stärka datakvaliteten är detta den mest centrala platsen att lägga
  till fler kontroller (t.ex. att `style_config`/`options`/
  `clusteroptions` är giltig JSON redan vid spara-tillfället, inte
  först vid publicering).
- **Namnkonsekvens `updated_from_table` vs `updated_from_table2`
  bekräftad som meningsfull, inte en bugg:** de två funktionerna har
  olika returstruktur (bara tidsstämpel kontra tidsstämpel+xmin) och
  används i olika sammanhang (formulärvisning direkt i manage.php kontra
  JSON-endpointen i updated.php som jämför flera tabeller och behöver
  `xmin` som tie-breaker/sorteringsnyckel). Ingen åtgärd behövs, men bra
  att detta nu är verifierat snarare än antaget.
- **`updatedFullTarget()`s hantering av saknade fält är trubbig:** om
  ett fält inte postats alls (`$updatePosts['update'.ucfirst($column)]`
  inte satt), sätts kolumnens nya värde till **tom sträng**, inte till
  dess tidigare värde. Det betyder att `sqlForUpdate()` i praktiken
  **skriver över alla kolumner** i tabellraden vid varje uppdatering –
  inte bara de som faktiskt ändrades i formuläret – med tomma strängar
  för allt som av någon anledning inte postades. Detta är sannolikt
  ofarligt i praktiken *om* varje formulär alltid postar samtliga sina
  fält (vilket verkar vara fallet, eftersom varje `print*Form`-funktion
  konsekvent skriver ut alla relevanta `printTextarea`/`printUpdateSelect`-
  anrop varje gång, inklusive dolda `printHiddenInputs()`-bevarade värden
  när ett fält är villkorligt dolt) – men det är en skör design: om ett
  fält någonsin glöms bort i ett formulär, eller om ett formulär skickas
  in ofullständigt (t.ex. via ett anpassat/framtida API-anrop som inte
  går via de befintliga `print*Form`-funktionerna), riskerar det att
  tysta radera data i den kolumnen. Värt att känna till som en
  bakomliggande skörhet i hela uppdateringsflödet, även om den inte
  manifesterar sig som ett synligt problem idag.
- **`viewKeywordCategorized()`s specialfall `'Allt'` är hårdkodat på
  svenska** – konsekvent med övriga svenska UI-strängar i kodbasen, men
  värt att notera tillsammans med `categories()`s `"Alla"` som ett annat
  exempel på samma mönster (hårdkodade svenska nyckelord i logiken,
  inte bara i visningstext).
- Fortsatt konsekvent avsaknad av `strict_types`/parametertypning genom
  hela filuppsättningen.
