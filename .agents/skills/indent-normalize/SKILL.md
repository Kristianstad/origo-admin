---
name: indent-normalize
description: 'Kontrollera och normalisera indentering i Origo-admin-repot till 4 mellanslag i PHP, JS och CSS. Använd när användaren ber om att städa upp eller göra stilen enhetlig, när inledande tabbar och mellanslag blandas, efter att nya filer lagts till, eller före en formateringscommit. Skriptet är skrivskyddat utan --fix, rör inte innehåll i strängar, heredocs eller inline-HTML och kontrollerar att PHP-koden är oförändrad.'
---

# Normalisera indentering

Standard: 4 mellanslag per nivå i PHP, JS och CSS (se `.editorconfig`). Skriptet ersätter inledande tabbar med mellanslag; det ändrar inte radlängd, radslut eller annan formatering.

## Regler

- Kör alltid utan `--fix` först och redovisa omfattningen. En ändring av indentering berör många rader och förstör `git blame` om den blandas med annat.
- Lägg formateringsändringar i en egen commit, utan funktionsändringar. Stage:a explicita sökvägar enligt `.agents/skills/commit-push/SKILL.md`.
- Normalisera bara filer som inte har pågående, orelaterade ändringar du inte avser att ta med. Kontrollera `git status --short` först.
- Tredjepartskod och demofiler (`plugins`, `demokarta`, `preview`, `img`) hoppas över; ändra dem inte.
- Indentering inne i strängar, heredocs och inline-HTML är innehåll och lämnas orörd. Skriptet redovisar dem som skyddade rader. Ändra dem bara för hand och bara om utdata inte påverkas.

## Procedur

1. Kontrollera: `php .agents/skills/indent-normalize/scripts/normalizeIndent.php [sökväg ...]`
   Utan sökvägar granskas `finalfs/www` och `.agents`. Om `php` saknas i `PATH`, se `.agents/skills/verify-changes/SKILL.md`.
2. Normalisera: samma kommando med `--fix`. För PHP jämförs tokenströmmen före och efter (utom blanksteg och kommentarer); filen skrivs inte om koden skulle ändras eller inte går att tolka.
3. Kör `.agents/skills/verify-changes/SKILL.md` och kontrollera med `git diff -w --stat` att inget annat än blanksteg ändrats (`git diff -w` ska vara tom för de normaliserade filerna).
4. Kontrollera det faktiska flödet i webbläsare om miljö finns; skriptet kan inte verifiera utskriftens utseende.

## Begränsningar

- JavaScript med flerradiga mallsträngar (backtick över radbrytning) hoppas över och redovisas.
- Skriptet ändrar inte filer som redan använder mellanslag, och rättar inte avvikande bredd (till exempel 2 mellanslag) eller justering av fortsättningsrader.
- Skyddade rader i strängar och heredocs kan behålla tabbar; `.editorconfig` påverkar dem inte.
