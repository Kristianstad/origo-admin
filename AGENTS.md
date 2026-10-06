# Instruktioner för utveckling och AI-agenter

Den här filen samlar bestående repokontext och arbetssätt för personer och AI-agenter som bidrar till Origo-admin. Den är en startpunkt, inte en ersättning för modulernas dokumentation. Håll den uppdaterad när arkitektur eller arbetsflöden ändras; lägg inte in hemligheter, kompletta samtalsloggar eller tillfälliga felsökningsanteckningar.

## Projektöversikt

- Repot bygger administrationsverktyget för Origo och en Docker-avbild. Runtime-struktur och moduler beskrivs i `docs/www/adm/OVERVIEW.md` och `docs/www/adm/ARCHITECTURE.md`; kontrollera `Dockerfile` för aktuella runtime-beroenden.
- Modulernas egna dokument i `docs/www/adm/` är den kanoniska referensen för lokala flöden och beteenden.
- Håll Docker-buildcontexten begränsad med `.dockerignore`; exkludera utvecklarfiler utan att ignorera nödvändiga byggindata eller runtime-filer.

## Övergripande utvecklingsmål

- Referensdokumentationen ska ge utvecklare och AI en gemensam, korrekt bild av applikationen. En modulbeskrivning ska göra det möjligt att börja arbeta avgränsat i den modulen utan att först läsa hela kodbasen.
- Förenkla och dela upp koden stegvis så att den blir lättare att följa visuellt och språkligt, även för en mindre erfaren utvecklare. Prioritera tydliga flöden och små, sammanhållna funktioner framför mekanisk uppdelning.
- Håll lösningar generiska där möjligt.
- Använd abstraktioner som gör koden mer logisk och lättläst. Abstraktioner ska göra flödet enklare, inte mer komplicerat.
- Undvik nya PHP-globals; skicka beroenden via parametrar, returvärden eller en tydlig kontextarray.
- Gör refaktorering i små, verifierbara steg.

## Avgränsning och källor

- Den här filen innehåller stabila, gemensamma principer och vägvisning. Aktuellt beteende, schemastruktur, säkerhetsobservationer och UI-detaljer hör hemma i respektive modulreferens.
- Verifiera modulbeskrivningar mot koden innan ändring. Om implementationen saknas i repot ska dokumentationen skilja känd integration från sådant som inte kan verifieras här.
- Håll ändringar inom önskat beteende och nödvändig närliggande dokumentation. Utöka inte uppgiften med orelaterad städning; ändra inte publika kontrakt eller schema utanför önskemålet.

## Dokumentationsprinciper

- Dokumentera funktion, indata, resultat, beroenden och viktiga sidoeffekter där det hjälper någon att ändra modulen. Skriv rakt och konkret på svenska; förklara förkortningar eller projektspecifika begrepp när de först behövs.
- Håll modulreferenser faktiska och navigerbara: kontrollera filträdet och anropare innan en symbol beskrivs som aktiv eller borttagen. Funktions- och konstantlistor ska vara alfabetiskt sorterade.
- Skilj verifierade fakta från hypoteser. Behåll inte ord som "sannolikt" eller "troligen" som om de vore ett verifierat beteende; verifiera påståendet, ta bort det eller markera den konkreta osäkerheten och vad som behöver kontrolleras.
- Modulernas `.md`-filer är teknisk referens, inte arbetslogg. Lägg inte in status för dokumentationsarbetet, granskningsgrad, etapper eller kommentarer om att dokumentationen har rättats. Behåll däremot faktiska runtime-statusar, säkerhetsrisker, begränsningar och relevanta öppna tekniska frågor.
- Ta inte bort en dokumenterad funktion bara för att motsvarande fil inte hittas. Kontrollera om beteendet finns under ett annat namn eller i entry pointen; be användaren bekräfta innan en sådan referens tas bort.
- Om en organisationsspecifik implementation saknas i repot, dokumentera endast verifierad integration och ange tydligt vad som inte kan kontrolleras i arbetsytan.
- Undvik att kopiera långa arkitekturbeskrivningar mellan filer. Använd `OVERVIEW.md` som ingång, `ARCHITECTURE.md` för mönster över flera moduler och modulernas egna dokument för lokala kontrakt.

## Börja här

1. Identifiera den kod som faktiskt äger beteendet: entry point, helper, stylesheet eller databasfunktion.
2. Läs närmaste anropare och befintlig modul-dokumentation innan du ändrar ett publikt flöde.
3. När en kodändring ändrar eller förtydligar beteende, uppdatera motsvarande modul-dokumentation i samma ändring. Om en berörd fil har en inledande kommentar som beskriver dess syfte, flöde eller beroenden, håll även den kommentaren uppdaterad. Lägg inte till standardkommentarer i filer som saknar en sådan.
4. Gör den minsta ändring som uppfyller önskemålet. Bevara befintliga API:er, POST-namn, command-värden, hidden fields och bekräftelser om de inte uttryckligen ska ändras.
5. Validera den berörda ytan och granska `git diff --check` innan du avslutar. Commit/push görs bara när användaren uttryckligen ber om det.

## Kodkonventioner och säkerhet

- Följ lokal kodstil och gör minsta ändring som löser uppgiften; undvik bred formattering och orelaterad refaktorering.
- Använd parametriserade SQL-värden. Dynamiska tabell- och kolumnnamn ska väljas eller valideras mot en tillåten uppsättning.
- Nya och ändrade funktioner bör få parameter- och returtyper i vanligt läge, men det är en riktlinje, inte en strikt regel: utelämna typer om de försvårar förenkling eller generalisering av koden. Databasvärden är strängar eller `NULL`, så använd nullbara typer eller `mixed` där det behövs. `declare(strict_types=1)` används inte i befintliga filer utan beslut.
- Filstruktur (detaljer och undantag i `ARCHITECTURE.md`, avsnittet "Strukturregler"): varje hjälpfunktion ligger i en egen fil med samma namn som funktionen, i `functions/<huvudfil>/` eller `functions/common/`. En huvudfil får bara använda hjälpfiler från sin egen mapp, `common` och Composer-tillägg på servern. Varje fil i `constants/` innehåller exakt en variabeldefinition med samma namn som filen (kommentarer är tillåtna).
- Bevara korrekt escaping och samma-origin-kontroller för `postMessage`. Följ befintlig formulärstruktur; HTML-formulär får inte nästlas.
- Föredra `json_encode()` för JSON och återanvänd befintliga helpers. Läs `ARCHITECTURE.md` och modulreferensen för lokala include-, target- och POST-mönster.
- Behandla användartext som UTF-8 och använd multibyte-medvetna funktioner för teckenbaserad textbearbetning. Se `common.md` för gemensamma helpers.
- Textfiler har alltid Unix-radslut (LF), även i arbetskatalogen på Windows; det styrs av `.gitattributes`. Startskripten i `finalfs/start/` körs på Alpine och fungerar inte med CRLF. Ändra inte radslut i befintliga filer annat än via `.gitattributes`.
- Förutsättningen är att administratörer är betrodda och att den som installerar verktyget begränsar åtkomsten till `/adm`. Administratörsskriven HTML (till exempel hjälptexter och nyheter) skrivs därför ut utan escaping, och anslutningssträngar lagras i klartext. Data från andra källor (frågeparametrar, publika endpoints, importerad data) ska ändå escapas och valideras.

## Historik och schemaändringar

Historik, snapshots, restore och kopplingen till konfigurationsschema beskrivs i `docs/www/adm/manage.md`. Läs den referensen innan du ändrar historikfunktioner, target-mappning eller formulärfält som ingår i snapshots; verifiera alltid nuvarande schema och anropsflöde i koden.

## UI- och knappreferenser

Följ befintliga komponent- och stylesheetmönster, inklusive tillgängliga namn och etiketter. Kontrollera aktuell CSS innan du ändrar placering eller mått; exakta UI-detaljer hör hemma i modulens dokumentation eller i den aktuella uppgiften.

## `topFrame` och formulärstorlek

Vid ändringar i iframe-flöden, läs `manage.md` och kontrollera huvudsida, barnvy, meddelandevalidering och berörda stilmallar tillsammans. Håll aktuella beteendedetaljer i den modulreferensen.

## Verifiering

- Kör relevanta tester och lintning för den ändrade ytan; använd `php -l` för ändrade PHP-filer när PHP finns lokalt och `git diff --check` före avslut.
- För UI-ändringar, kontrollera det faktiska browserflödet och relevanta stilmallar. För databasspecifikt beteende krävs en miljö som kan verifiera integrationen.
- Redovisa kontroller som inte gick att köra; editor-diagnostik ersätter inte en saknad runtime-kontroll.

## Dokumentation att läsa vid behov

- `docs/www/adm/OVERVIEW.md` – modulkarta.
- `docs/www/adm/ARCHITECTURE.md` – gemensamma arkitekturmönster och target-modell.
- `docs/www/adm/manage.md` – CRUD-flöden, formulärbyggstenar, POST-kommandon, undo/redo och restore.
- Övriga `docs/www/adm/*.md` – modulernas detaljer, till exempel `authorization.md`, `read_json.md`, `writeConfig.md` och `multiselect.md`.
- `docs/DEVELOPMENT_PLAN.md` – planerat förbättringsarbete. Lägg nya förbättringsidéer och uppskjutna åtgärder där, inte som önskelistor i modulreferenserna; ta bort punkter när de är genomförda.
