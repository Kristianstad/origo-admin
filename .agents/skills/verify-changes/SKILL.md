---
name: verify-changes
description: 'Verifiera ändringar i Origo-admin-repot innan de avslutas eller committas: PHP-syntax (php -l), LF-radslut, saknad slutradbrytning och git diff --check. Använd när en uppgift ska avslutas, före commit, eller när användaren ber om lint, verifiering eller kontroll av ändringar.'
---

# Verifiera ändringar

Kontrollerar alla ändrade och nya filer i arbetsytan. Ersätter inte körning av gränssnittet eller databasen; redovisa vad som inte kunde kontrolleras.

## Procedur

1. Kör skriptet från repots rot:
   `php .agents/skills/verify-changes/scripts/verifyChanges.php`
   Om `php` inte finns i `PATH`, hitta den körbara filen (på Windows till exempel under `%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.*`) och kör skriptet med den.
2. Rätta alla `FEL`. Skriptet kontrollerar PHP-syntax (och JavaScript om `node` finns), att textfiler har LF-radslut samt `git diff --check` och `git diff --cached --check`.
3. Nya filer som skrivits med CRLF kan normaliseras med `--fix-new-eol`. Befintliga filers radslut ändras aldrig av skriptet; ändra dem inte på annat sätt än via `.gitattributes`.
4. Varningar om saknad slutradbrytning kommer ofta från en formatterare. Återställ dem i filer som annars inte ändras.
5. Redovisa kontroller som hoppades över eller inte gick att köra.

## Tips för PowerShell

Skicka inte PHP-kod med citattecken via `php -r`. Lägg koden i en temporär fil eller kör den base64-kodad: `php -r "eval(base64_decode('...'));"`.
