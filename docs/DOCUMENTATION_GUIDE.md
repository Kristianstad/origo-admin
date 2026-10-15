# Riktlinjer för dokumentation

Gäller när modulreferenser i `docs/www/adm/` eller andra tekniska dokument skrivs eller ändras. Se `AGENTS.md` för övriga arbetssätt.

- Dokumentera funktion, indata, resultat, beroenden och viktiga sidoeffekter där det hjälper någon att ändra modulen. Skriv rakt och konkret på svenska; förklara förkortningar eller projektspecifika begrepp när de först behövs.
- Håll modulreferenser faktiska och navigerbara: kontrollera filträdet och anropare innan en symbol beskrivs som aktiv eller borttagen. Funktions- och konstantlistor ska vara alfabetiskt sorterade.
- Skilj verifierade fakta från hypoteser. Behåll inte ord som "sannolikt" eller "troligen" som om de vore ett verifierat beteende; verifiera påståendet, ta bort det eller markera den konkreta osäkerheten och vad som behöver kontrolleras.
- Modulernas `.md`-filer är teknisk referens, inte arbetslogg. Lägg inte in status för dokumentationsarbetet, granskningsgrad, etapper eller kommentarer om att dokumentationen har rättats. Behåll däremot faktiska runtime-statusar, säkerhetsrisker, begränsningar och relevanta öppna tekniska frågor.
- Ta inte bort en dokumenterad funktion bara för att motsvarande fil inte hittas. Kontrollera om beteendet finns under ett annat namn eller i entry pointen; be användaren bekräfta innan en sådan referens tas bort.
- Om en organisationsspecifik implementation saknas i repot, dokumentera endast verifierad integration och ange tydligt vad som inte kan kontrolleras i arbetsytan.
- Undvik att kopiera långa arkitekturbeskrivningar mellan filer. Använd `OVERVIEW.md` som ingång, `ARCHITECTURE.md` för mönster över flera moduler och modulernas egna dokument för lokala kontrakt.
