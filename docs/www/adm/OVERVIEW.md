# Översikt – adm/ (Origo-adminverktyg)

Denna fil är en karta över samtliga moduler i `adm/`. Varje modul har
sin egen `.md`-fil (namn enligt kolumnen "Dokumentation") med fullständig
detaljbeskrivning. Se `ARCHITECTURE.md` för genomgående mönster (include-
system, autentisering, m.m.), `common.md`/`constants.md` för delade
funktioner/konstanter.

## Kärnmoduler (adminfunktionalitet)

| Modul | Entry point(s) | Beskrivning |
|---|---|---|
| manage | `manage.php` | Den centrala CRUD-motorn för all konfiguration: kartor, lager, grupper, källor, tjänster, m.fl., inklusive ångra/gör om för fältuppdateringar (se manage.md) |
| writeConfig | `writeConfig.php` | Genererar Origo-JSON + publicerad HTML-sida från databasen. "Kompileringssteget" |
| read_json | `read_json.php` (**aktiv, ej avstängd**) | Motsatsen till writeConfig: importerar Origo-JSON till databasen. Körs i produktion — känd teknisk skuld (strängbyggd SQL, skör regex-parsning) är alltså en reell risk, inte bara vilande kod |
| sql_import | `sql_import.php` | Kör inklistrad SQL eller uppladdad `.sql`-fil mot admin-databasen via Verktyg-vyn |
| news | `news.php` | Nyheter/meddelanden för inloggade användare |
| mapstate | `mapstate.php` | Stateless JSON-API för att spara/hämta karttillstånd (delbara länkar) |
| info | `info.php` | Detaljvy för ett objekt + "Används av"-lista (iframe-popup) |
| multiselect | `multiselect.php` | Generisk flervalskomponent (iframe-popup) |
| help | `help.php` | Hjälptexter (generella eller fältspecifika), iframe-popup |

## Autentisering (två parallella spår, se ARCHITECTURE.md)

| Modul | Entry point(s) | Beskrivning |
|---|---|---|
| authorization | `authorization.php`, `authorization-iframe.php` | LDAP-baserad inloggning (äldre spår, underhålls men fasas ut) |
| forwardauth | `forwardauth.php`, `azure-callback.php` | Azure AD/Entra ID via OAuth2 + Traefik ForwardAuth (nytt, avsett spår) |

## Karttjänst-proxyer

| Modul | Entry point(s) | Beskrivning |
|---|---|---|
| restrictedLayer | `restrictedLayer.php` | Behörighetsstyrd proxy mot karttjänst; degraderar svar för obehöriga |
| grouplayerfix | `grouplayerfix.php` | QGIS-specifik proxy; expanderar "grupplager" till underlager. Begränsad användning, ingen behörighetskoppling (annan QGIS-instans än restrictedLayer) |

## Databas-/schemaunderhåll

| Modul | Entry point(s) | Beskrivning |
|---|---|---|
| read_db_schemas | `read_db_schemas.php` | Synkar scheman från extern databas till konfig-databasen |
| read_schema_tables | `read_schema_tables.php` | Synkar tabeller från ett schema till konfig-databasen |
| updated | `updated.php` | Slår upp senaste ändringsdatum för databastabeller |
| writeTablesForAllLayers | `writeTablesForAllLayers.php` | Batchscript: fyller i vilka tabeller QGIS-lager bygger på |

## Export (organisationsspecifik)

| Modul | Entry point(s) | Beskrivning |
|---|---|---|
| export | `export.php` | Asynkron export av kartutsnitt via FME Server. **Ej i publikt repo, hårt org-specifik** |

## Ej dokumenterade / lågprioriterade

| Fil | Anteckning |
|---|---|
| `export.old.php`, `read_json.php.off.old`, `restrictedLayer-rancher*.php`, `importmeta.php.old`, diverse `_old`/`_old2`-filer i `info/plan/` | Gamla varianter, kandidater för radering — avsiktligt inte dokumenterade |
| Loader-filer (`authorization/`, `export/`, `forwardauth/`, `grouplayerfix/`, `mapstate/`, `news/`, `updated/` på toppnivå) | Bekräftat aktivt använda (se authorization.md) men saknar ännu egen dokumentation |
| `export/` (toppnivå, med egen `functions/`/`constants/`) | Delvis dubblerad kod jämfört med `adm/functions/export/` – mer än en tunn loader |
