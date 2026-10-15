# Konventioner för UI, JavaScript och stilmallar

Gäller vid ändringar av knappar, formulär, stilmallar och JavaScript i `finalfs/www/adm/`. Se `AGENTS.md` för övriga arbetssätt.

## Komponenter och stilmallar

- Följ befintliga komponent- och stylesheetmönster, inklusive tillgängliga namn och etiketter. Kontrollera aktuell CSS innan du ändrar placering eller mått; exakta UI-detaljer hör hemma i modulens dokumentation eller i den aktuella uppgiften.
- Gemensamma regler ligger i `styles/common.css`, som laddas före sidans egen stilmall så att sidspecifika regler kan åsidosätta dem. Se `docs/www/adm/common.md`.

## JavaScript

- Lägg inte längre eller återanvändbar JavaScript-logik i PHP-strängar, heredocs eller HTML-attribut. Placera den i `js-functions/<modul>/` och ladda den via modulens etablerade `includeDirectory()`-flöde; håll eventuella inline-anrop korta.
- Filer i `js-functions/<modul>/` ska i första hand deklarera namngivna JavaScript-funktioner, normalt en per fil med samma namn som filen. Lägg inte eventregistrering eller annan körbar initiering på toppnivå; anropa initieringsfunktioner uttryckligen från modulens bootstrap efter att katalogen inkluderats.

## iframe-flöden (`topFrame`)

Vid ändringar i iframe-flöden, läs `docs/www/adm/manage.md` och kontrollera huvudsida, barnvy, meddelandevalidering och berörda stilmallar tillsammans. Håll aktuella beteendedetaljer i den modulreferensen.
