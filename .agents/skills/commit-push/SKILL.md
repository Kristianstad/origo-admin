---
name: commit-push
description: 'Committa och pusha ändringar i Origo-admin-repot. Använd när användaren ber om commit, push eller "commit och push". Avgränsar vad som stage:as, kontrollerar radslut och diff och pushar till origin.'
---

# Commit och push

Kör bara när användaren uttryckligen ber om commit och/eller push.

## Procedur

1. Kör `git status --short`, `git status -sb` och `git diff --stat`. Läs diffen för filer som ser orelaterade ut (till exempel enbart ändrade radslut eller avslutande radbrytning).
2. Stage:a endast filer som hör till uppgiften, med uttryckliga sökvägar. Använd inte `git add -A` eller `git add .`. Nämn orelaterade ändringar för användaren och lämna dem ostage:ade om användaren inte ber om dem.
3. Kör skillen `.agents/skills/verify-changes/SKILL.md` (PHP-syntax, LF-radslut, `git diff --check`). På Windows kan nya filer skrivas med CRLF; normalisera dem innan stage med `--fix-new-eol`.
4. Kör `git diff --cached --check` efter stage och redovisa kontroller som inte kunde köras.
5. Skriv ett kort commit-meddelande på svenska i imperativ eller beskrivande form, i samma stil som `git log` (till exempel "Samla gemensamma CSS-regler").
6. `git commit -m "<meddelande>"`, sedan `git push origin <aktuell branch>`. Använd inte `--force` eller `--no-verify`.
7. Bekräfta med `git status -sb` att branchen är synkad och redovisa commit-hash samt vad som lämnades ostage:at.
