# Arkitektur – adm/ (Origo-adminverktyg)

Genomgående mönster som gäller flera moduler. Modul-specifika detaljer
finns i respektive `<modul>.md`.

## Include-mönster

Samtliga huvudfiler (entry points) i `adm/` följer i stort sett samma
inledande mönster:

```php
require_once("./functions/includeDirectory.php");
includeDirectory("./functions/common");
includeDirectory("./functions/<modulnamn>");
```

`includeDirectory()` (i `adm/functions/includeDirectory.php`) laddar
**samtliga** `.php`-filer i en given mapp med `require_once`. Det betyder:

- Alla filer i `functions/common/` laddas alltid, oavsett modul, även om
  modulen bara använder ett fåtal av dem.
- Ingen modul-fil visar i sig själv exakt vilka common-funktioner som är
  tillgängliga – se `common.md` för en växande referens baserad på
  faktiskt observerad användning.
- **Undantag:** `grouplayerfix.php` inkluderar **inte** `functions/common/`
  (medvetet bortkommenterat, modulen behöver inga common-funktioner för
  närvarande).

**Konsekvens för refaktorering:** om funktioner flyttas mellan filer
inom `common/` behöver inget include uppdateras (`includeDirectory()`
laddar redan alla filer i mappen) – men om `common/` bryts upp i
undermappar måste `includeDirectory()`-anropet uppdateras i varje entry
point som behöver den nya mappen.

## Konstanter kontra common-funktioner – laddningssätt

Till skillnad från `functions/common/`, laddas filer i `adm/constants/`
**individuellt** med explicit `require`/`require_once` där just den
konstanten behövs:

```php
require("./constants/configSchema.php");
```

En konstant är alltså **inte** automatiskt tillgänglig bara för att den
finns i mappen. Vid dokumentation av en modul listas därför bara de
konstanter modulen faktiskt inkluderar – se `constants.md` för
fullständig, växande referens.

## JS-filer

Vissa moduler har klientlogik i `adm/js-functions/<modul>/`, laddad
inline i en `<script>`-tagg via samma `includeDirectory()`-mekanism som
PHP – alla `.js`-filer i mappen klistras in i sidans HTML vid varje
sidladdning, inte som separata `<script src="...">`-taggar. JS-funktioner
dokumenteras i respektive modul-`.md`, i ett avsnitt "JS-filer och
funktioner".

### Kommunikationsmönster: iframe ↔ huvudsida

Ett återkommande mönster: verktyg som körs i en iframe (`help.php`,
`info.php`, `multiselect.php`) pratar med sin förälder via `postMessage`:
Iframe-innehåll → window.parent.postMessage({ action: 'resize'|'close', ... }, origin)
Huvudsida → tar emot, agerar (döljer/ändrar storlek på iframen, fyller i fält)

I `manage.php` hanteras detta av `initMessageListener()` +
`toggleTopFrame()`/`resizeIframe()` (se manage.md), som fungerar som den
gemensamma mottagaren för samtliga dessa verktygsfönster via en delad
iframe (`#topFrame`).

## Två parallella autentiseringssystem

Applikationen har två separata sätt att autentisera användare, där ett
är på väg att fasas ut:

1. **`authorization.php`** – LDAP-baserad inloggning (äldre spår,
   underhålls men prioriteras lägre). Sätter `$_SESSION['user']['id']`
   (enkel struktur) samt en egen krypterad cookie. Se `authorization.md`.
2. **`forwardauth.php`/`azure-callback.php`** – Azure AD/Entra ID via
   OAuth2 (nytt, avsett spår), anropad av Traefik som en
   åtkomstkontroll före övrig trafik. Sätter `$_SESSION['user']` med en
   rikare struktur (`mail`, `name`, `groups`, `expires_at` för sliding
   expiration). Se `forwardauth.md`.

**Status:** används inte samtidigt – styrs av `$authMethod`. Azure/
forwardauth är den långsiktiga riktningen; LDAP finns kvar som alternativ.
`restrictedLayer.php` fungerar med båda spåren utan att bry sig om
vilket som satt `$_SESSION['user']`.

## Loader-filer utanför adm/ (dokumenteras separat)

Toppnivåmapparna `authorization/`, `export/`, `forwardauth/`,
`grouplayerfix/`, `mapstate/`, `news/`, `updated/` innehåller
`*-loader.php`-filer som speglar entry points i `adm/`. Bekräftat aktivt
använda (se `authorization.md`: `login.php`/`displayLogout.php` anpassar
beteende baserat på om anropet kom via en loader). De saknar ännu egen
dokumentation – se OVERVIEW.md, "Ej dokumenterade / lågprioriterade".

**Undantag:** `export/` (toppnivå) har egen `functions/`/`constants/`-mapp
med kod som delvis dubblerar `adm/functions/export/` – mer än en tunn
loader, extra uppmärksamhet vid loader-genomgången.

## Extern exportpipeline (FME Server)

`export.php` lämnar över tunga formatkonverteringsjobb till en extern
**FME Server**-instans via REST-API, asynkront
(`fastcgi_finish_request()`). Se `export.md`. Denna modul är
organisationsspecifik och ingår inte i det publika GitHub-repot.

## Publiceringskedjan (writeConfig → disk) och "changed"-flaggan

`writeConfig.php` genererar JSON + HTML och skriver till disk via
`publishMapFiles()` (okomprimerat + Brotli + gzip + publika symlänkar).
Den fysiska kartkatalogen ligger under `<webRoot>/maps/<kartnamn>`.
`<webRoot>/<kartnamn>` är en symlink till samma katalog, så webbroten och
`maps` använder en enda uppsättning filer. Befintliga mål ersätts rekursivt
innan symlänken skapas.
Samma fil skriver även `constants/RESTRICTEDLAYERS.php`
(`defineFileConstant()`), vilket är den bekräftade källan till
konstanten `restrictedLayer.php` läser.

`manage.php` (`markMapsChanged()`) sätter `maps.changed='t'` när något
som påverkar en publicerad karta ändras; `writeConfig.php`
(`markMapUnchanged()`) nollställer flaggan efter lyckad publicering.
Detta ger sannolikt underlag för en "osparade ändringar"-indikator i
manage-gränssnittet.

WriteConfig-funktionerna använder ett explicit context-array med referenser
till kartans konfigurationsdata. Manage- och news-helpers tar motsvarande
state som parametrar; funktionerna använder inte PHP:s `GLOBAL`-deklaration
för moduldata eller renderingsstate.

## SQL-importverktyget

`sql_import.php` nås via **Verktyg > Importera SQL**. Verktyget laddar
`functions/sql_import/*.php`, använder samma skinvariabler som övriga
iframe-verktyg och accepterar antingen SQL-text eller en uppladdad `.sql`-fil.
SQL körs direkt mot admin-databasen; transaktionsgränser måste därför anges i
SQL-filen när flera satser ska köras som en enhet. CSRF-token och filstorleks-
begränsning används, men åtkomsten ska fortfarande begränsas till betrodda
administratörer.

## Den dedikerade "preview"-kartan

En Origo-karta med id `'preview'` existerar specifikt för
adminverktygets förhandsgranskningsfunktion (`printConfigPreviewButton()`
i manage.md, anropad från grupp- och lagerformulär). Detta är alltså
inte ett användarfel eller en bugg när `'preview'` hårdkodas som mapId i
dessa anrop, utan en avsiktlig, dedikerad resurs.

## Datastrukturen "target" (manage-modulen)

Ett centralt begrepp i manage-modulen: en enhetlig representation av
"ett objekt av viss typ" i två varianter:

- **Basic:** `[$type => $id]`, t.ex. `['layer' => 'vagar#1']`
- **Full:** `[$type => $config]`, t.ex.
  `['layer' => ['layer_id' => 'vagar#1', 'title' => 'Vägar', ...]]`

Gör att samma kod (SQL-generering, formulärrendering, parent-traversering
och info-vyn) kan hantera alla entitetstyper (map/layer/group/source/...)
generiskt utan att skriva om samma logik för varje typ. `manage.php` och
`info.php` delar därför basic/full target-kontraktet, medan
`writeConfig.php` medvetet behåller kartans konfigurationsrad som en lokal
JSON-arbetsstruktur.

Target-kärnans rena representationer och accessorer ligger i
`functions/common/`: `isTarget()`, `isBasicTarget()`, `isFullTarget()`,
`targetType()`, `targetId()`, `targetTable()`, `targetIdColumn()`,
`typeTableName()`, `makeBasicTarget()`, `makeFullTarget()`,
`makeTargetBasic()`, `targetConfigParam()` och
`setTargetConfigParam()`. Manage-lagret behåller de config-/databasberoende
funktionerna `targetConfig()`, `makeTargetFull()`, `tableConfigs()` och
`updatedFullTarget()`. Fullständig beskrivning finns i `manage.md`.
