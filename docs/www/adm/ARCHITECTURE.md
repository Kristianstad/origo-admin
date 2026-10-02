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
  tillgängliga – se `common.md` för referens.
- **Undantag:** `grouplayerfix.php` inkluderar **inte** `functions/common/`
  (medvetet bortkommenterat, modulen behöver inga common-funktioner för
  närvarande).

**Konsekvens för refaktorering:** om funktioner flyttas mellan filer
inom `common/` behöver inget include uppdateras (`includeDirectory()`
laddar redan alla filer i mappen) – men om `common/` bryts upp i
undermappar måste `includeDirectory()`-anropet uppdateras i varje entry
point som behöver den nya mappen.

## Strukturregler

1. **En funktion per fil.** Varje hjälpfunktion ligger i en egen fil med
   samma namn som funktionen (`printHeadForm()` i `printHeadForm.php`),
   i `functions/<huvudfil>/` eller i `functions/common/` om flera
   huvudfiler använder den. Filen definierar bara den funktionen; en
   hjälpfunktion definieras inte inuti en annan funktion.
2. **Begränsade beroenden.** En huvudfil får bara använda hjälpfiler från
   sin egen mapp och från `common/`, samt Composer-tillägg på servern
   (`../../composer/`). Detsamma gäller funktionerna i mappen: de får
   anropa funktioner i sin egen mapp och i `common/`, och `common/` får
   inte anropa modulfunktioner.
3. **En variabel per konstantfil.** Varje fil i `constants/` innehåller
   exakt en variabeldefinition med samma namn som filen
   (`constants/webRoot.php` definierar `$webRoot`). Kommentarer är
   tillåtna. Flera värden samlas i en array.

**Undantag:**

- `functions/includeDirectory.php` ligger direkt i `functions/` eftersom
  den behövs för att ladda mapparna.
- `azure-callback.php` använder mappen `functions/forwardauth/` (samma
  modul som `forwardauth.php`).
- Huvudfiler utan egen funktionsmapp (till exempel `help.php`,
  `read_schema_tables.php` och `writeTablesForAllLayers.php`) använder
  bara `common/`.

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
fullständig referens.

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

## Loader-filer utanför adm/

Mapparna `authorization/`, `forwardauth/`, `grouplayerfix/`, `mapstate/` och
`updated/` under `finalfs/www/` innehåller `*-loader.php`-filer som gör
`chdir('../adm/')` och därefter `require` av motsvarande entry point, så att
entry pointens relativa sökvägar (`./functions`, `./constants`, `./styles`)
fungerar. Loadern innehåller ingen egen logik.

| Loader | Entry point | Beskrivning |
|---|---|---|
| `authorization/authorization-iframe.php` | (egen HTML-sida) | Bäddar in `authorization-loader.php` i en iframe; se `authorization.md` |
| `authorization/authorization-loader.php` | `authorization.php` | Se `authorization.md` |
| `authorization/forwardauth-loader.php` | `forwardauth.php` | Samma innehåll som `forwardauth/forwardauth-loader.php`; se `forwardauth.md` |
| `authorization/news-loader.php` | `news.php` | Se `news.md` |
| `authorization/restrictedLayer-loader.php` | `restrictedLayer.php` | Se `restrictedLayer.md` |
| `forwardauth/azure-callback-loader.php` | `azure-callback.php` | Se `forwardauth.md` |
| `forwardauth/forwardauth-loader.php` | `forwardauth.php` | Se `forwardauth.md` |
| `grouplayerfix/grouplayerfix-loader.php` | `grouplayerfix.php` | Se `grouplayerfix.md` |
| `mapstate/mapstate-loader.php` | `mapstate.php` | Se `mapstate.md` |
| `updated/updated-loader.php` | `updated.php` | Se `updated.md` |

Exempel i koden använder URL-prefixet `/php/` (till exempel
`/php/updated/updated-loader.php`). Hur prefixet mappas till `finalfs/www/`
bestäms av webbserverkonfigurationen utanför det här repot och kan inte
verifieras här.

**Undantag:** modulen `export` har enligt `export.md` en egen
`export/`-mapp på toppnivå med `functions/`/`constants/` som delvis
dubblerar `adm/functions/export/`. Mappen finns inte i den här arbetsytan
och kan därför inte verifieras.

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
(`defineFileConstant()`), som `restrictedLayer.php` läser.

`manage.php` (`markMapsChanged()`) sätter `maps.changed='t'` när något
som påverkar en publicerad karta ändras; `writeConfig.php`
(`markMapUnchanged()`) nollställer flaggan efter lyckad publicering.
`printWriteConfigButton()` visar knappen i "ändrad"-läge när flaggan är satt.

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
adminverktygets förhandsgranskningsfunktion (`printConfigPreviewButton()`,
anropad från grupp- och lagerformulär). `'preview'` hårdkodas som mapId i
dessa anrop; det är avsiktligt.

## SQL-värden och identifierare

SQL-värden skickas med `pg_query_params()` och placeholders. Tabell- och
schemanamn kan inte bindas som värden: de valideras mot databasmetadata och
citeras med `pg_escape_identifier()` innan de sätts in i SQL.

`configTableNames($dbh)` cachar bastabellerna i `$configSchema` som har den
förväntade id-kolumnen. `object_identity` och `edit_cursor` är interna
historiktabeller och ingår inte. `all_from_table()` accepterar bara dessa
tabeller i konfigurationsschemat. `info.php` kontrollerar typen mot samma
lista; `multiselect.php` begränsar tabellerna ytterligare till
`multiselectables.php`, efter aliasöversättning.

För datatabeller använder `qualifiedTableIdentifier()` katalogen i den
aktuella databasanslutningen för att kontrollera `schema.tabell` och citerar
båda delarna. `updated.php` kräver dessutom att tabellen finns registrerad i
`map_configs.tables`. Ogiltig `type` i info ger 404; ogiltiga tabellparametrar
i multiselect och updated ger 400.

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
JSON-arbetsstruktur. Funktionerna listas i `common.md` och `manage.md`; den
fullständiga beskrivningen finns i `manage.md`.

## Historik: ångra/gör om (manage-modulen)

Ett separat historiklager (tabellerna `object_identity`, `edits` och
`edit_cursor` i `map_configs`, se `initdb/060.origo.sql`) låter
administratören ångra/göra om fältuppdateringar (`command == 'update'`)
per objekt, utan att blanda in historikfält i konfigurationstabellerna
själva. `create`/`copy` sparar en historikbaslinje så efterföljande
uppdateringar kan ångras. `delete` sparar objektets snapshot, som kan
återställas från ändringsposten; radering är inte ett Gör om-steg.
Fullständig beskrivning (datamodell, skriv-/läsflöde, UI) finns i
`manage.md` under "Historik: Ångra/Gör om (undo/redo)".
