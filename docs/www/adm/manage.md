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
  `makeFullTarget()` (om du redan har `$config`) eller `toFullTarget()`
  (om du bara har ett basic target och en databaskoppling/configTables,
  och behöver slå upp konfigurationen åt dig).

Ett target är en array med exakt ett nyckel/värde-par och en icke-tom
strängnyckel. `isTarget()` kontrollerar formen, `isBasicTarget()` känner
igen ett strängvärde och `isFullTarget()` känner igen en konfigurations-array.
Target-id måste vara en icke-tom sträng; `'0'` är giltigt.

Detta enhetliga format gör att funktioner som `sqlForUpdate()`,
`sqlForOperation()`, `printChildSelect()` m.fl. kan ta emot "vilket
objekt som helst" utan att bry sig om exakt vilken av de många typerna
(map/layer/group/source/...) det handlar om – de läser typ, id och
konfiguration via target-helpers i stället för att indexera target-arrayen
direkt. Detta är
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
targets med `toBasicTarget()` och läser typ med `targetType()`, medan
`printChildSelect()`, `printInfoButton()`, `printDeleteButton()` och
add/remove-operationerna använder `targetType()`, `targetId()`,
`targetConfig()` och `targetConfigParam()` i stället för att läsa targetens
nyckel och värde direkt.

`writeConfig.php` använder däremot kartans konfigurationsrad som en lokal
arbetsstruktur för JSON-genereringen. Den är medvetet inte omskriven till
targets, eftersom target-abstraktionen där inte skulle minska komplexiteten.

## Historik: Ångra/Gör om (undo/redo)

Ett fristående historiklager, separat från själva konfigurationstabellerna,
lagrar konfigurationsuppdateringar per objekt och snapshots för objektens
livscykel. Uppdateringar kan ångras/göras om; en radering kan återställas från
sin snapshot. Modellen bygger på tre tabeller i map_configs (definierade i
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
ut före ändringen ($historyBeforeConfig). recordHistoryEdit() lägger till
före/efter-snapshotet, flyttar edit_cursor dit och kastar bort en ev.
kvarvarande "gör om"-gren. Om historik saknas skapas först en baslinje, så
även objekt som fanns före historikfunktionen får en ångringsbar första
uppdatering. Äldre historik utan baslinje kan också ångra sin första
update-post. Skapa- och kopiera-kommandon sparar en baslinje med
objektets initiala snapshot; baslinjen i sig är inte ångringsbar, men gör
senare uppdateringar ångringsbara, precis som i en vanlig linjär
undo/redo-stack.

Vid radering sparas objektets snapshot i en edit med action `delete`. När en
sådan edit visas finns knappen Återställ. Den återinfogar objektet från det
lagrade snapshotet, markerar raderingshändelsen som `restored` och flyttar
historikmarkören tillbaka till föregående giltiga edit. Raderingen blir inte
ett Gör om-steg; om objektet raderas på nytt skapas en ny raderings-edit.
Delete- och restored-posterna finns kvar i ändringslistan men ingår inte i
undo/redo-kedjan. Återställning är begränsad till den senaste raderingen och
misslyckas om samma id redan används. Knappen ligger först i ändringsformulärets
knapprad, före Backa/Gör om, och använder samma symbol som Backa.

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
Restore har en separat dispatch-gren som anropar `restoreDeletedEdit()` med
`restoreEditId` och validerar snapshotet innan raden återinfogas.

Medvetet avgränsat: create/copy ger en baslinje men går inte själva att
ångra. Radering kan återställas från edit-vyn, men ingår inte som ett steg
i Gör om. Återställning misslyckas om samma id redan finns eller snapshotet
inte längre är giltigt.

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
lagrad ändring i efterhand. En `delete`-post har dessutom knappen Återställ.
Radera och Skapa kopia är dolda för edits, liksom skapa-fältet i toppraden.
`object_identity`/`edit_cursor` är medvetet
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
optioner. `$extras` stöder ledande knappar före historikknapparna,
inline-knappar (t.ex. databasens `printReadDbSchemasButton` och gruppens
preview), valfri visning av Kopiera/Radera samt sektioner efter formuläret
(kontrollens och gruppens add/remove-operationer).
Funktionsnamnen och deras publika argument är oförändrade eftersom
`manage.php` fortfarande använder dynamisk dispatch. Före anropet kontrolleras
att target-tabellen finns i den laddade konfigurationen och att motsvarande
`print<Typ>Form()` finns; annars avvisas targeten via `invalidTarget()`.

**Instanser av mönstret (sorterad alfabetiskt efter filnamn):**

| Fil | Typ | Fält utöver id/abstract/info | Extra knappar/sektioner |
|---|---|---|---|
| `printAduserForm.php` | aduser | name, email, company, department, lastlogin, adgroups (samtliga skrivskyddade utom abstract/info) | – |
| `printContactForm.php` | contact | name, web, email | – |
| `printControlForm.php` | control | options, css, js, onload | `printAddOperation`/`printRemoveOperation` mot maps |
| `printDatabaseForm.php` | database | connectionstring | `printReadDbSchemasButton` |
| `printEditForm.php` | edit | target_key, target_table, target_id, date, action, changed_by, before_data, after_data (samtliga readonly) | Återställ för delete-poster; Skapa kopia/Radera döljs |
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
| `printTableForm.php` | table | Gör ett **extra, eget databasanrop** (`dbh($dbhConnectionString)` + `updatedFromTable()`) mitt i renderingen för att visa senaste ändringsdatum – enda formuläret som pratar med en annan databas än konfigurationsdatabasen under rendering |
| `printServiceForm.php` | service | Villkorlig visning baserat på tjänstetyp, med `printHiddenInputs()`-bevarande mönster likt layer/source |
| `printSourceForm.php` | source | Villkorlig visning baserat på tjänstetyp (`File`/`OpenStreetMap` döljer flera fält) |

## Formulärbyggstenar

*Sorterad alfabetiskt efter filnamn.*

| Fil | Funktion | Beskrivning |
|---|---|---|
| `printTilegridForm.php` | (enkelt mönster) | tilesize, standardfält i övrigt |
| `printUndoButton.php` | `printUndoButton($target, $visible=true)` | "Backa"-knappen: postar `target_key`/`target_table`/`target_id` och `command=undo`, med JS-bekräftelsedialog. Se "Historik: Ångra/Gör om" ovan |
| `printUpdateButton.php` | `printUpdateButton($type, $formChanged=false)` | "Uppdatera"-knappen. Får ändringsstatus som argument; `manage.php` skickar statusen via `_formChanged` i `inheritPosts` efter ett misslyckat sparförsök |
| `printUpdateForm.php` | (enkelt mönster) | Namnet är missvisande – detta gäller entiteten "update" (en uppdateringsrutin/schema för när data anses föråldrad, kopplat till `updated`-modulen), inte formulärets egen uppdateringsknapp. Fält: `interval` (tidsintervall som text, t.ex. "+1 month" – ser ut som PHP:s `strtotime()`-kompatibla format), `method` (manuellt/automatiskt) |
| `printUpdateSelect.php` | `printUpdateSelect($fullTarget, $configParamValues, $class, $label, $help=false, $options=null, $onchange='')` | Motsvarigheten till `printTextarea()` men för `<select>`-fält istället för fritext. Om `$options` inte anges härleds de automatiskt från `$configParamValues` |
| `printUrlButton.php` | `printUrlButton($url, $type)` | Typknapp som öppnar en URL i ny flik utan att skapa ett nästlat formulär; använder typen för knapptexten, exempelvis karta eller externt verktyg |
| `printViewSwitcher.php` | `printViewSwitcher($view)` | Radioknappar för att växla mellan vyer (`constants/views.php`), autopostar vid ändring |
| `printWriteConfigButton.php` | `printWriteConfigButton($mapId, $changed='f')` | Typknapp som efter bekräftelse anropar `writeConfig.php` i `hiddenFrame` (utan nästlat formulär). Visar "ändrad"-styling om `maps.changed = 't'` (satt av `markMapsChanged()`) |
| `recordHistoryEdit.php` | `recordHistoryEdit($dbh, $target, $action, $beforeConfig, $afterConfig): bool` | Sparar update/create/copy/delete-snapshots, skapar baslinje vid behov (även vid restore utan föregående giltig edit) och flyttar `edit_cursor`; update kastar en kvarvarande gör-om-gren. Se "Historik: Ångra/Gör om" ovan |
| `restoreDeletedEdit.php` | `restoreDeletedEdit($dbh, $editId): array` | Validerar och återställer objektet från senaste delete-editens snapshot i en transaktion; markerar posten som restored och flyttar historikmarkören tillbaka utan redo-radering |

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
| `<knapp med kommando>` | Formulärknappens `name`/`value` avslöjar `$type` och `$command` (`copy`/`create`/`delete`/`update`/`operation`/`undo`/`redo`/`restore`), tolkas av `postButton()` |
| `to<Typ>Id` / `from<Typ>Id` | Vid `operation`-kommandot: lägg till/ta bort ett barn-objekt från en förälder (map/group/classe/infogroup) |
| `target_key` / `target_table` / `target_id` | Vid `undo`/`redo`-kommandot: identifierar vilket objekt historiken gäller (se "Historik: Ångra/Gör om" ovan), postas som dolda fält av `printUndoButton()`/`printRedoButton()` |
| `restoreEditId` | Vid `restore`-kommandot: id för delete-edit-posten vars snapshot ska återställas; posten och snapshotet hämtas och valideras på serversidan |

## Beror på
**Common-funktioner** (`adm/functions/common/`):
- `dbh()`, `configTables($dbh)`
- `pkColumnOfTable()`, `arrayColumnSearch()`, `pgArrayToPhp()`,
  `assocArrayValues()`, `findAllParents()`

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
| `hasStringKeys.php` | `hasStringKeys(array $array): bool` | Kontrollerar om en array har minst en textnyckel (dvs. är associativ snarare än numeriskt indexerad). Används av `printSelectOptions()` |
| `historyStateForTarget.php` | `historyStateForTarget($dbh, $target): array` | Läser `edit_cursor`/`edits` för given target och räknar från grunden ut `['undo', 'redo', 'current_edit_id', 'edits', 'index']`. Används av `printHistoryButtons()` för att avgöra vilka knappar som ska visas |
| `idPosts.php` | `idPosts($post): array` | Filtrerar `$post` till fält vars namn slutar på `Id`, med undantag för operationernas `from/to`-fält för map, group, classe och infogroup |
| `isArrayColumn.php` | `isArrayColumn($column): bool` | Kontrollerar om en given kolumn är en Postgres-array-kolumn, genom att slå upp den mot listan i `constants/arrayColumns.php`. Avslutar programmet (`die()`) om `$column` inte är en icke-tom sträng |
| `markMapsChanged.php` | `markMapsChanged(&$dbh, $mapIds): void` | Sätter `maps.changed = 't'` för samtliga angivna kartor i en enda batch-SQL (flera `UPDATE`-satser konkatenerade med `; `). Detta är motparten till `markMapUnchanged()` i writeConfig-modulen – manage-modulen flaggar en karta som "ändrad, behöver publiceras om" varje gång något som påverkar den redigeras, och writeConfig-modulen nollställer flaggan efter lyckad publicering |
| `objectHistoryKey.php` | `objectHistoryKey($target): string` | Bygger den stabila historiknyckeln `<typ>:<id>` (`target_key`) för ett objekt, utifrån `targetType()`/`targetId()`. Se "Historik: Ångra/Gör om" ovan |
| `postButton.php` | `postButton($post): string\|null` | Hittar namnet på den POST-parameter vars namn slutar på `Button` – det är detta namn (`<typ>Button`) som `manage.php` sedan bryter isär för att få fram `$type` |
| `printAddOperation.php` | `printAddOperation($target, $addToTable, $buttontext, $inheritPosts)` | Skriver ut ett litet formulär: en dropdown med tillgängliga föräldrar (t.ex. kartor eller grupper) + en knapp som postar `operation`-kommandot för att lägga till `$target` i den valda föräldern |
| `printChildSelect.php` | `printChildSelect($target, $column, &$thClass, $heading, $inheritPosts, $groupLevel=1, $selectedValue=null)` | Den mest komplexa av dessa byggstenar: skriver ut en kolumn i "barn-urvalsraden" (t.ex. vilka lager/grupper/kontroller finns i vald karta). Hanterar specialfall för `schemas`/`tables` och nästlade `groups`/`infogroups`, där respektive id-kedja byggs baserat på djup |
| `printAddRemoveOperations.php` | `printAddRemoveOperations($target, $operationTables, $inheritPosts, $labels=array())` | Gemensam renderer för add/remove-operationer. `exclusiveOperationGroups.php` kan ange singleton-poster eller grupper av ömsesidigt exklusiva föräldratabeller; add-knappen döljs när målet redan finns i någon förälder i gruppen |
| `printConfigPreviewButton.php` | `printConfigPreviewButton($mapId, $group=null, $layer=null)` | Typknapp som öppnar en förhandsgranskning via `writeConfig.php?getHtml=y` i en ny flik, utan att skriva till disk eller skapa ett nästlat formulär. Kan begränsas till en specifik grupp eller ett specifikt lager |
| `printCopyButton.php` | `printCopyButton($type)` | Enkel "Spara kopia"-knapp (`command=copy`) |
| `printDeleteButton.php` | `printDeleteButton($target, $deleteConfirmStr, $inheritPosts)` | Raderaknapp med `onclick`-bekräftelse; skickar delete-kommandot via det omgivande entitetsformuläret, utan att skapa ett eget formulär. Visas bara när `_viewDepth` i `inheritPosts` är `1` |
| `printExportJsonButton.php` | `printExportJsonButton($mapId)` | Typknapp som laddar ner kartans JSON-konfiguration via `writeConfig.php?getJson=y&download=y` i den dolda iframen (`hiddenFrame`) så sidan inte navigerar bort; inget nästlat formulär |
| `printHeadForm.php` | `printHeadForm($tableConfig, $inheritPosts)` | Skriver ut en enskild kolumn i toppradens urvalsformulär: en dropdown för att välja befintligt objekt (med ev. nyckelordskategorisering) + normalt ett textfält och en knapp för att skapa nytt. Skapadelen döljs för `edits`. Dropdownens värde är alltid tabellens id-kolumn, men för `contact`/`origin` visas `name` som etikett och för `edit` visas `target_key` (objektet ändringen gäller) istället för det annars intetsägande `edit_id`:t – ordningen hålls kronologisk (via `preserveOrder` i `printSelectOptions()`) eftersom `allFromTable()` redan läser raderna sorterade på `edit_id` |
| `printHeadForms.php` | `printHeadForms($view, $configTables, $focusTable, $inheritPosts)` | Skriver ut hela toppraden av urvalsformulär, en `printHeadForm()`-kolumn per tabell som ingår i vald `$view` (styrt av `constants/views.php`). Placerar `$focusTable` först och ger den fokus-styling |
| `printHelpButton.php` | `printHelpButton($type, $configParam=null, $buttonText='?', $buttonClass='smallHelpButton')` | Liten "?"-knapp bredvid ett fält, öppnar/togglar hjälptext för just det fältet (`help.php?id=<type>[:<configParam>]`) i topFrame |
| `printHiddenInputs.php` | `printHiddenInputs($inheritPosts)` | Skriver ut ett dolt `<input>` per nyckel/värde i `$inheritPosts`, för att bevara navigeringskontext genom formulärinskick |
| `printHistoryButtons.php` | `printHistoryButtons($target, $dbh=null, $inheritPosts=array())` | Skriver ut "Backa"/"Gör om"-knapparna för given target, efter att ha frågat `historyStateForTarget()` om vilka som är tillgängliga. Öppnar/stänger en egen databaskoppling om ingen skickas in. Se "Historik: Ångra/Gör om" ovan |
| `printInfoButton.php` | `printInfoButton($basicTarget)` | Typknapp som öppnar `info.php` i `topFrame` för given target, utan eget formulär |
| `printMultiselectButton.php` | `printMultiselectButton($configParam, $value=null, $textareaId, $buttonText='+', $buttonClass='smallMultiselectButton')` | Knapp som öppnar multiselect-verktyget i topFrame för ett givet fält, via samma `<textareaId>::<tabell>:<värden>`-kodning som beskrivs i `multiselect.md` |
| `printReadDbSchemasButton.php` | `printReadDbSchemasButton($databaseId)` | Typknapp som efter bekräftelse anropar `read_db_schemas.php` i `hiddenFrame` och skickar om databasurvalet efter 1 sekund. Avbryt stoppar båda åtgärderna; inget nästlat formulär |
| `printReadSchemaTablesButton.php` | `printReadSchemaTablesButton($schemaId)` | Motsvarande för `read_schema_tables.php`; Avbryt stoppar anrop och formulärresubmit |
| `printRemoveOperation.php` | (samma mönster som `printAddOperation.php`, se ovan) | Motsatsen till `printAddOperation()` – kräver dessutom `findParents()` [common] för att bara visa de föräldrar objektet faktiskt tillhör (kan inte tas bort från en förälder det inte är kopplat till) |
| `printRedoButton.php` | `printRedoButton($target, $visible=true)` | "Gör om"-knappen: postar `target_key`/`target_table`/`target_id` och `command=redo`, med JS-bekräftelsedialog. Se "Historik: Ångra/Gör om" ovan |
| `printRestoreEditButton.php` | `printRestoreEditButton($editId, $targetId)` | Skriver ut Återställ-knappen för en delete-edit; postar edit-id:t och ber om bekräftelse |
| `printSelectOptions.php` | `printSelectOptions($optionValues, $selectedValue=null, $preserveOrder=false)` | Skriver ut `<option>`-element för en `<select>`. Sorterar alfabetiskt om arrayen är associativ (id→namn), om inte `$preserveOrder` är satt (används av `edit`-dropdownen för att bevara kronologisk ordning trots att etiketten är `target_key`, inte datumet). **Visar texten efter sista kommatecknet i etiketten**, se begränsningar |
| `printTextarea.php` | `printTextarea($fullTarget, $configParam, $class, $label, $help=false, $sizePosts=array(), $readonly=false)` | Den mest centrala byggstenen i hela manage-modulen – skriver ut ett enskilt redigerbart fält som ett `<textarea>`. Städar Postgres-arraysyntax för visning, bevarar användarens tidigare valda storlek/scrollposition (via `$sizePosts` och dolda fält), visar en hjälpknapp om hjälptext finns, och visar en multiselect-knapp om fältet är konfigurerat som "multiselectable" |
| `sizePosts.php` | `sizePosts($post): array` | Filtrerar `$post` till bredd-/höjd-/scrollrelaterade fält, och normaliserar `new*`-prefixade nycklar (från senaste formulärinskicket) till samma nyckelformat som de ursprungliga (`width*`/`height*`/`scroll*`) – nyare värden skriver över äldre i sammanslagningen |
| `sqlForOperation.php` | `sqlForOperation($operation, $child, $parent): array` | Bygger en parameteriserad UPDATE-sats som lägger till/tar bort ett barn-id ur förälderns array-kolumn; returnerar SQL och parametrar |
| `sqlForUpdate.php` | `sqlForUpdate($fullTarget, $updatePosts): array` | Bygger en parameteriserad fullständig UPDATE-sats för en target baserat på postat formulärdata; returnerar SQL och parametrar |
| `typeHelps.php` | `typeHelps($type, $helps): array` | Filtrerar den globala listan av hjälptext-id:n (`help_id`, format `<typ>:<fält>`) till de som gäller en specifik typ, och returnerar bara fältdelen. Detta är mekaniken bakom `in_array($fältnamn, $helps)`-kontrollerna i varje `print*Form`-funktion – `$helps` som skickas till de funktionerna är redan filtrerat via denna funktion i `manage.php`s entry point |
| `updatedFullTarget.php` | `updatedFullTarget($fullTarget, $updatePosts): array` | Bygger en ny full target där postade `update<Kolumn>`-fält ersätter befintliga värden. Fält som saknas i POST behåller tidigare värde; ett postat tomt värde kan rensa fältet. Array-kolumner (enligt `isArrayColumn()`/`constants/arrayColumns.php`) omsluts automatiskt med Postgres-array-syntax `{...}`. Används av `sqlForUpdate()` innan `appendUpdatedColumnsToSql()` bygger SQL-strängen |
| `updatedFromTable.php` | `updatedFromTable($dbh, $tableWithSchema): array\|false\|null` | Kontrollerar och citerar `schema.tabell`, hämtar senaste `pg_xact_commit_timestamp` och returnerar bara tidsstämpeln. `false` betyder att tabellen saknas; `null` att den saknar rader. Används av `printTableForm.php` |
| `updatePosts.php` | `updatePosts($post): array` | Filtrerar `$post` till fält vars namn börjar med `update` – detta är alla postade formulärfältvärden redo att skrivas till databasen |
| `validateUpdate.php` | `validateUpdate($updatePosts, $configTables, &$updateValid)` | Validerar **endast** fält som är markerade som "multiselectable" (`constants/multiselectables.php`) – kontrollerar att varje kommaseparerat värde som postats faktiskt existerar som ett giltigt id i motsvarande tabell. Sätter `$updateValid` (skickad by reference) och visar ett JS `alert()` vid fel. **Notera:** fält som inte är multiselectable valideras alltså inte alls av denna funktion – se begränsningar nedan |
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
| `initMessageListener.js` | `initMessageListener()` | Sätter upp en global `postMessage`-lyssnare för hela sidan. Validerar avsändarens origin (måste matcha `window.location.origin`) och att datan har rätt form innan den hanteras. Hanterar tre fall: `action:'close'` utan `targetId` (stäng topFrame, t.ex. från help.php), `action:'resize'` (justera topFrame-höjd; `read_json` och `sql_import` kan även krympa), och `targetId`+`value` (fyll i ett textarea-fält med värde från multiselect-verktyget, samt uppdatera den tillhörande multiselect-knappens `value`-attribut så nästa öppning av multiselect visar rätt förval) |
| `preservePageScroll.js` | `preservePageScroll()` | Initieras i `<head>` och sparar scrollpositionen vid formulärinskick via en delegerad `submit`-lyssnare. Om en positiv position väntar döljs dokumentet tills `DOMContentLoaded`, då sidan scrollas till positionen och visas; formulär riktade mot en annan browsing context sparar inte positionen |
| `resizeIframe.js` | `resizeIframe(iframe, autoResize=false)` | Anpassar iframens höjd efter innehållet. Standardläget växer bara och behåller det manuella resize-handtaget; `autoResize=true` följer både ökning och minskning och döljer handtaget |
| `toggleTopFrame.js` | `toggleTopFrame(type)` | Visar/döljer den delade toppmonterade iframen (`#topFrame`). Håller reda på vilken typ av innehåll som visas (global variabel `topFrame`, deklarerad i `manage.php`s inline-script) – klick på samma typ igen döljer den, klick på en annan typ byter innehåll och scrollar upp |
| `updateSelect.js` | `updateSelect(id, array)` | Fyller om en `<select>`-listas alternativ med ett nytt innehåll. Specialhantering för element vars id slutar på `Categories`: värdet sätts till det råa (understreck-separerade) kategorinamnet men visningstexten har understreck ersatta med mellanslag |

## Kommunikationsmönster: iframe ↔ huvudsida

`manage.php` bygger på ett återkommande mönster där verktyg som körs i en
iframe pratar med huvudsidan via `postMessage`. Help, info och multiselect
behåller manuell höjdreglering och växer automatiskt vid innehållsändringar.
`read_json` och `sql_import` följer innehållets höjd både uppåt och nedåt och
har inget manuellt resize-handtag.

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

## Begränsningar och risker

- `manage.php` hanterar fem slags valda objekt (map, database, schema,
  group-kedja, övrigt) i sekvens i samma fil, med `unset()` mellan
  sektionerna så att variabler inte läcker mellan grenarna. Varje sektion har
  en förklarande kommentar.
- `unset($_POST, $_GET)` står överst i `manage.php`.
- `array_filter($_POST, ...)` behåller värdet `"0"` men filtrerar bort tomma
  strängar, så legitima nollvärden (till exempel opacitet eller skala) går
  inte förlorade.
- Raderingsskyddet (`findAllParents` + `assocArrayValues`) återanvänder
  samma mönster som "Används av" i `info.php`.
- Den QGIS-baserade autoifyllnaden vid update läser `.qgs`-filer, liksom
  `writeTablesForAllLayers.php` och `info.php`. Ett ändrat QGIS-format
  kräver därför ändring på tre ställen.
- Vid `pg_query()`-fel byggs ett JS `alert()` med rått
  `pg_last_error()`-innehåll (escapat för JS-strängen, men inte
  HTML-escapat) som visas för administratören. Det exponerar interna
  databasfelmeddelanden (tabell-/kolumnnamn, SQL-fragment) för den
  inloggade administratören.
- `initMessageListener.js` saknar null-kontroll på `multiselectButton`
  (`document.getElementById(targetId + ":multiselect")`). Saknas elementet
  kastar `getAttribute` ett `TypeError` som stoppar resten av
  händelsehanteraren.
- `initMessageListener.js` uppdaterar knappens `value` med regexen
  `/^([^:]*::[^:]*).*$/`, som förutsätter formatet
  `<textareaId>::<tabell>:<värden>`. Samma format tolkas i
  `multiselect.php`; ändras det på ena stället måste det ändras på båda.
- `formChangeButton()` letar bara efter en knapp med exakt
  `value="update"`; endast den knappen får "ändrad"-markeringen.
- Alla JavaScript-filer i en mapp laddas globalt utan namnrymder, och
  `topFrame` är en delad global variabel.
- SQL i CRUD-flödet byggs av `insertIdSql.php`, `deleteIdSql.php`,
  `sqlForUpdate.php` och `sqlForOperation.php`, som returnerar SQL med
  platshållare och separata parametrar. `manage.php` kör dem med
  `pg_query_params()` i en explicit transaktion. `markMapsChanged()` bygger
  fortfarande en sammanslagen SQL-sträng med kart-id:n.
- Target-funktioner avslutar med det gemensamma `invalidTarget()`-felet när
  target-form eller id är ogiltigt. `tableConfigs()` avslutar fortfarande med
  `exit(1)` utan meddelande för en saknad tabell i en cachad config-array.
- `hasStringKeys()` används av `printSelectOptions()` för att avgöra om
  `$optionValues` är associativ.
- `isArrayColumn()` läser `constants/arrayColumns.php` med ett
  funktionslokalt `require()`. `deleteIdSql.php` och `markMapsChanged.php`
  gör motsvarande lokala `require` av `configSchema.php`.
- `categories()` använder `"Alla"` och `viewKeywordCategorized()` använder
  `'Allt'` som hårdkodade svenska nycklar i logiken.
- `'preview'` i `printGroupForm.php` och `printLayerForm.php` är ett
  dedikerat Origo-karta-id som bara används av adminverktyget för
  förhandsgranskning, inte kartans `mapId`.
- `printHiddenInputs.php` undantar nyckeln `layerCategory` (singular) och
  nycklar som börjar med `_`. Category-fält från `categoryPosts()` namnges
  efter tabellnamnet, till exempel `layersCategory`, så undantaget
  `layerCategory` matchar inga sådana nycklar.
- `printDeleteButton.php` har ett utkommenterat block som trimmade
  `$inheritPosts` per objekttyp. Raderaknappen visas bara när
  `inheritPosts['_viewDepth'] == 1`, alltså för den första nivån av vald
  hierarki (till exempel den valda kartan, men inte en nästlad grupp längre
  ner). Det är avsiktligt: raderingskontrollen (`findAllParents()`) blockerar
  objekt som används av andra objekt, och objekt under första nivån används
  alltid av sitt överordnade objekt.
- `printFormatForm.php` har bara fälten `format_id`, `abstract` och `info`.
- `printHeadForm.php` och `printChildSelect.php` har var sin implementation
  av förkortade etiketter (prefix-strippning av föräldrans id) för
  `schemas`- och `tables`-kolumner i database-schema-table-kaskaden.
- `printHeadForm.php` blandar `<<<HERE`-block med `echo`-konkatenering och
  läser `keywordCategorized.php` med `require()` i funktionskroppen.
- `printSelectOptions.php` visar texten efter sista kommatecknet i `$label`
  (`ltrim(substr($label, strrpos($label,',')), ',')`), eller hela `$label`
  om inget kommatecken finns. Det påverkar etiketterna i alla urvalslistor
  i manage-modulen.
- Knapphelpers inuti entitetsformuläret skriver inga egna formulär:
  `printDeleteButton()` skickar via det omgivande entitetsformuläret, och
  `printInfoButton()`, `printWriteConfigButton()`,
  `printReadDbSchemasButton()`, `printReadSchemaTablesButton()`,
  `printConfigPreviewButton()`, `printExportJsonButton()` och
  `printUrlButton()` skriver inga formulär. Bekräftelserna använder
  knappens `onclick` och Avbryt stoppar åtgärden.
- `printTableForm.php` anropar `updatedFromTable()` (i `functions/manage/`,
  returnerar bara tidsstämpeln). `updated.php` använder
  `updatedFromTable2()`, som också returnerar `xmin` som
  sorteringsnyckel när flera tabeller jämförs.
- `printLayerForm.php` och `printServiceForm.php` bevarar värdet för
  villkorligt dolda fält med `printHiddenInputs()`, så att data inte går
  förlorad när administratören växlar mellan lägen (till exempel byter
  tjänstetyp) utan att spara emellan.
- `printUpdateForm.php` gäller entiteten "update" (uppdateringsscheman) och
  är inte kopplad till `printUpdateButton()` (spara-knappen) eller
  `sqlForUpdate()` (SQL-generering).
- **`validateUpdate()` validerar bara multiselect-fält.** Den enda
  serversidesvalideringen före ett `UPDATE` är kontrollen att kommaseparerade
  referens-id:n (för multiselect-fält som `adusers` och `adgroups`) finns.
  Alla andra fält (fritext, siffror, ja/nej-val och URL:er) skrivs utan
  kontroll av innehåll utöver kolumntypen. Kolumner av typen `json` (till
  exempel `style_config`, `options` och `clusteroptions`) valideras av
  PostgreSQL, som avvisar ogiltig JSON med ett databasfel.
- `updatedFullTarget()` bevarar lagrade värden för fält som saknas i POST.
  Ett fält som skickas med tom sträng kan fortfarande rensas.
