# Konstanter (constants)

**Plats:** `adm/constants/` (samt vissa på toppnivå, utanför `adm/`)
**Laddas av:** enskilda `require`/`require_once`-satser där de behövs
(se ARCHITECTURE.md – *inte* automatiskt via `includeDirectory()`).

> Tabellen är sorterad alfabetiskt efter filnamn (samma ordning som i
> `constants/`-mappen) för att göra det lätt att slå upp en specifik
> konstant.

| Konstant | Fil | Beskrivning | Används av |
|---|---|---|---|
| `$adDomain` | `adDomain.php` | AD-domännamn för LDAP-autentisering | authorization |
| `$adGroupFilter` | `adGroupFilter.php` | Lista över AD-grupper som ska filtreras bort ur vyn i manage-verktyget (tom array som standard) | manage |
| `$adldapConfig` | `adldapConfig.php` | LDAP-anslutningsinställningar (adldap2) | authorization |
| `$arrayColumns` | `arrayColumns.php` | Lista över kolumner som är Postgres-arrayer | manage (isArrayColumn) |
| `$authMethod` | `authMethod.php` | Styr autentiseringsmetod (`'ldap'` eller annat) | news, restrictedLayer, export, authorization, forwardauth (indirekt) |
| `$azureConfig` | `azureConfig.php` | Azure AD-appregistrering: clientId, clientSecret, redirectUri, tenant, scopes | forwardauth |
| `$configSchema` | `configSchema.php` | Postgres-schemanamn för appens tabeller | news, mapstate, info, read_db_schemas, export, manage (flera filer), read_schema_tables |
| `$cookieConfig` | `cookieConfig.php` | Cookieinställningar: namn, krypteringsnyckel, livslängd | authorization |
| `$dbhConnectionString` | `dbhConnectionString.php` | Anslutningssträng (`pg_connect`-format) till Origos konfigurationsdatabas — standardanslutningen som `dbh()` faller tillbaka på utan argument | samtliga moduler (indirekt via `dbh()`) |
| `$dbhConnectionStringForUpdated` | `dbhConnectionStringForUpdated.php` | Anslutningssträng för updated-modulen med användaren `origo_updated_readonly`, skapad i `050.postgres.sql` | updated |
| `$forwardauthSessionConfig` | `forwardauthSessionConfig.php` | Sessionsinställningar för forward-auth: slideExtension, absoluteMax, baseLifetime | forwardauth |
| `$iconTtl` | `iconTtl.php` | TTL-parameter (sekunder) som läggs till på lagerträdets ikon-URL:er, för cache-brytning. `'-1'` stänger av parametern helt | writeConfig (addLayersToJson) |
| `$keywordCategorized` | `keywordCategorized.php` | Vilka tabeller som ska nyckelordskategoriseras | manage (printHeadForm; jfr `viewKeywordCategorized()`) |
| `$mapstateMaxUnused` | `mapstateMaxUnused.php` | Dagar innan oanvänt mapstate städas bort | mapstate |
| `$multiselectables` | `multiselectables.php` | Vilka konfigurationsfält som ska ha en multiselect-knapp | manage (printTextarea) |
| `$previewBase` | `previewBase.php` | Bas-URL för förhandsgranskningsläge | writeConfig |
| `$proxyRoot` | `proxyRoot.php` | Bas-URL-prefix, för korrekta länkar bakom proxy | news, authorization, writeConfig, renderCssTags/renderJavaScriptTags |
| `$restrictedServiceUrl` | `restrictedServiceUrl.php` | Bas-URL till bakomliggande karttjänst för restrictedLayer | restrictedLayer |
| `$searchEngineMeta` | `searchEngineMeta.php` | Metadata för strukturerad SEO-data: array med `geo` (`latitude`, `longitude`), `contentLocation` (`name`, `addressLocality`, `addressCountry`) och `publisher` (`name`, `url`) | writeConfig |
| `$sourcesQueryColumns` | `sourcesQueryColumns.php` | Vilka kolumner som ska med som query-parametrar för en källas URL | writeConfig (addSourcesToJson) |
| `$swedishDic` | `swedishDic.php` | Ordlista engelska→svenska för interna typ-/kolumnnamn (map, layer, group, m.fl.), används av `toSwedish()` | manage, info (indirekt via `toSwedish()`) |
| `$tableAliases` | `tableAliases.php` | Alias-mappning för tabellnamn använda av multiselect-knappar | manage (printMultiselectButton) |
| `$views` | `views.php` | Definierar vilka tabeller/kolumner som visas i respektive vy | manage (printHeadForms) |
| `$webRoot` | `webRoot.php` | Rotkatalog dit publicerade kartor skrivs | writeConfig |
| `RESTRICTEDLAYERS` (fil-konstant) | `RESTRICTEDLAYERS.php` (toppnivå, ej i `constants/` — placerad sist eftersom den fysiskt hör hemma på en annan plats) | Lista över skyddade lager m. behöriga användare/grupper. Genereras av writeConfig | restrictedLayer |
