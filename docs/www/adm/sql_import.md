# SQL-importmodul (sql_import)

**Entry point:** `adm/sql_import.php`
**Funktionsfiler:** `adm/functions/sql_import/*.php`
**CSS:** `adm/styles/sql_import.css`

## Syfte

SQL-importen kör en inklistrad SQL-text eller en uppladdad `.sql`-fil mot
adminverktygets konfigurationsdatabas. Verktyget nås via **Verktyg > Importera
SQL** i `manage.php`.

Detta är ett administrativt verktyg för kontrollerade databasoperationer, inte
ett publikt API. SQL körs med den anslutning som anges i
`constants/dbhConnectionString.php`.

## Flöde

1. GET visar ett skinat formulär med textarea och filuppladdning.
2. Formuläret innehåller en sessionsbunden CSRF-token.
3. SQL kan anges direkt i textarea eller läsas från en uppladdad fil. Om båda
   anges används filens innehåll.
4. Uppladdade filer begränsas till 10 MB.
5. SQL körs med `pg_query()` mot admin-databasen.
6. Nya kartor som tillkommit genom SQL-importen markeras med
  `maps.changed = TRUE`, så att **Skriv kartkonfiguration**-knappen aktiveras.
  Befintliga kartors `changed`-värde lämnas oförändrat.
7. Vid fel visas ett skinat felmeddelande och PostgreSQL-felet exponeras i
   svaret.
8. Vid lyckad körning visas ett skinat lyckat-resultat och en **Stäng**-knapp
   som stänger iframe-vyn.

I `manage.php` visas formuläret och resultatet i `topFrame`. Höjden anpassas
automatiskt när innehållet växer eller minskar; iframe-handtaget för manuell
höjdändring är avstängt för den här vyn.

## Säkerhet och drift

- Säkerhetskopiera databasen innan SQL körs.
- Granska SQL-filen och kontrollera att den riktar sig mot rätt databas/schema.
- Använd transaktion (`BEGIN`/`COMMIT`/`ROLLBACK`) i SQL-filen när flera
  operationer ska lyckas eller misslyckas tillsammans.
- `sql_import.php` lägger inte automatiskt till en transaktion runt hela
  uppladdningen; SQL-filen ansvarar själv för transaktionsgränser.
- Begränsa åtkomsten till adminverktyget. SQL-importen ger den inloggade
  användaren möjlighet att köra godtycklig SQL med databasanslutningens
  behörigheter.

## Hjälpfiler

| Fil | Funktion | Ansvar |
|---|---|---|
| `sqlImportError.php` | `sqlImportError(string $message): void` | Returnerar ett fel och avslutar requesten |
| `sqlImportPage.php` | `sqlImportPage(array $skin, string $message, bool $success): void` | Renderar skinat resultat och stängknapp |
| `markNewMapsChanged.php` | `markNewMapsChanged($dbh, string $configSchema, array $existingMapIds): void` | Markerar nya kartor som ändrade efter SQL-körningen |

