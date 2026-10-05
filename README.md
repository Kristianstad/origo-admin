# Origo-admin

Kristianstads kommuns administrationsverktyg för [Origo](https://github.com/origo-map), levererat som en Docker-avbild. Verktyget hanterar kartor, lager, grupper, metadata, behörighetsklassning och publicering av Origo-konfiguration. Varje skapad karta får en egen HTML-fil.

```
webbläsare ──► nginx (port 8080) ──┬─► Origo och publicerade kartor (/www, /www/maps)
                                   └─► /adm (inloggning) ──► PHP-FPM ──► PostgreSQL
                                       (allt körs inuti containern)
```

**Du behöver inte bygga något själv.** Färdigbyggda avbilder finns på GitHub Container Registry
(`ghcr.io`) och hämtas automatiskt första gången du kör dem, se [Kom igång med Docker](#kom-igång-med-docker).
Avbilden innehåller nginx, PHP-FPM, PostgreSQL, Origo och administrationsverktyget i en container, så det finns bara en sak att starta.
Databasen kan i stället ligga utanför containern, se [Ställningstaganden inför installation](#ställningstaganden-inför-installation).

En svensk användarguide för administrationsverktyget finns i [Origo_admin_tutorial_swedish.pdf](finalfs/www/Origo_admin_tutorial_swedish.pdf).

## Filer i repot

| Fil | Vad den gör |
|---|---|
| `Dockerfile` | Bygger avbilden. Här står standardvärdena för `VAR_*`-variablerna och vilka program som ingår. |
| `finalfs/www/adm/` | Administrationsverktygets webbkod (PHP, CSS, JavaScript) och dess `constants/`. |
| `finalfs/www/` (övrigt) | Loader-filer, demokarta, preview och plugin-filer som nås utanför `/adm`. |
| `finalfs/initdb/` | SQL som körs när databasen skapas första gången (`050.postgres.sql`, `060.origo.sql`). |
| `finalfs/start/` | Startskript som körs vid containerns första start (PostgreSQL, PHP, nginx-inloggning, `constants`). |
| `.github/workflows/docker-image.yml` | GitHub Actions: bygger och publicerar den färdiga avbilden. |
| `docs/` | Teknisk referens för utvecklare, se `docs/www/adm/OVERVIEW.md`. |
| `AGENTS.md` | Instruktioner för utveckling och AI-agenter. |
| `Origo_admin_tutorial_swedish.pptx` | Källa till användarguiden. |
| `LICENSE` | Licens (BSD 2-Clause) för filerna i det här repot. |


## Innehållsförteckning

- [Rekommenderad installation](#rekommenderad-installation)
- [Ställningstaganden inför installation](#ställningstaganden-inför-installation)
  - [Windows eller Linux](#windows-eller-linux)
  - [Docker eller installation helt utan Docker](#docker-eller-installation-helt-utan-docker)
  - [Inbyggd Origo eller separat Origo (vid dockerinstallation)](#inbyggd-origo-eller-separat-origo-vid-dockerinstallation)
  - [Inbyggd PostgreSQL eller separat PostgreSQL (vid dockerinstallation)](#inbyggd-postgresql-eller-separat-postgresql-vid-dockerinstallation)
  - [Lokala admininstallationer med gemensam lagring](#lokala-admininstallationer-med-gemensam-lagring)
- [Installation med Docker](#installation-med-docker)
  - [Förutsättningar](#förutsättningar)
  - [Kom igång med Docker](#kom-igång-med-docker)
  - [Färdigbyggd avbild](#färdigbyggd-avbild)
  - [Hostkataloger och fileshare](#hostkataloger-och-fileshare)
  - [Så fungerar avbilden](#så-fungerar-avbilden)
  - [Kontrollera container och loggar](#kontrollera-container-och-loggar)
  - [Port och reverse proxy](#port-och-reverse-proxy)
  - [Bygga avbilden själv](#bygga-avbilden-själv-valfritt)
- [Installation utan Docker](#installation-utan-docker)
- [Origo-konfiguration och kartor](#origo-konfiguration-och-kartor)
- [Konfiguration och miljövariabler](#konfiguration-och-miljövariabler)
  - [Variabler för avbilden](#variabler-för-avbilden)
  - [Inställningar i `constants`](#inställningar-i-constants)
- [Backup och uppgradering](#backup-och-uppgradering)
  - [Databasschema vid ny version](#databasschema-vid-ny-version)
  - [Uppgradera administrationsverktyget från GitHub](#uppgradera-administrationsverktyget-från-github)
- [Felsökning](#felsökning)
- [Säkerhetschecklista före produktion](#säkerhetschecklista-före-produktion)
- [Licens](#licens)

## Rekommenderad installation

Den rekommenderade installationen använder den publicerade avbilden med administrationsverktyget:

`ghcr.io/kristianstad/origo-admin:2.10.0`

Vill du bara ha Origo utan administrationsverktyget finns avbilden `ghcr.io/kristianstad/origo:2.10.0`.

## Ställningstaganden inför installation

### Windows eller Linux

Linux är ett naturligt val för produktion, men administrationsverktyget har små
resurskrav och kan även köras på Windows när det passar organisationens
förvaltning och kompetens.

Windows passar för utveckling, test och drift. [Docker Desktop](https://www.docker.com/products/docker-desktop/) ger en enkel Docker-/WSL2-miljö. Kontrollera aktuella Docker-villkor före kommersiell användning.

### Docker eller installation helt utan Docker

Docker är rekommenderat eftersom nginx, PHP-FPM, PHP-tillägg, PostgreSQL, Origo och Composer-bibliotek paketeras i en testad kombination. Samma avbild kan användas i utveckling, test och produktion. En docker-installation kan även kombineras med separata del-tjänster som ligger utanför dockermiljön (läs mer nedan).

Installation helt utan Docker ger mer kontroll men kräver egen installation och
samordning av webbserver, PHP, PHP-tillägg, Composer, PostgreSQL,
autentisering, filrättigheter och initierings-SQL.

### Inbyggd Origo eller separat Origo (vid dockerinstallation)

Inbyggd Origo är enklast. Adminverktyg, kartor och preview körs med samma avbild och på samma värd. Det passar särskilt bra för utveckling, test och mindre installationer.

Separat Origo kan ge oberoende uppgraderingar, skalning och tydligare separation mellan admin och publik kartvisning. Det kräver dock egen reverse-proxy-konfiguration, hantering av delad fillagring etc. Om man redan har en väl fungerande Origo-installation kan det vara bra att låta den ligga separat.

**Rekommendation:** om man redan har en väl fungerande Origo-installation kan det vara bra att fortsätta att använda den, annars är det enklare att använda den inbyggda.

### Inbyggd PostgreSQL eller separat PostgreSQL (vid dockerinstallation)

Inbyggd PostgreSQL är standard i avbilden och passar utveckling, test och mindre installationer. Det krävs altså ingen separat databas, men databas och webbapplikation delar container och livscykel. Datan kan läggas på en permanent lagringsyta som återanvänds vid containeruppgradering, men om avbilden byter Postgresql-version kommer det krävas att man gör databas-dump och återställning.

Installation av en separat PostgreSQL-databasserver kräver en hel del arbete, men om man redan har tillgång till en sådan databasserver är en separat databas ofta att föredra. Bland annat blir uppgraderingar smidigare.

**Rekommendation:** inbyggd PostgreSQL för utveckling/test och separat PostgreSQL för produktion med högre driftkrav.

### Lokala admininstallationer med gemensam lagring

Om man vill slippa exponera och säkra adminverktyget på ett gemensamt nätverk,
och organisationen har få administratörer, kan varje administratör köra en egen
lokal installation. Installationerna kan ansluta till samma PostgreSQL-databas
och använda gemensamma `constants`- och `maps`-kataloger. Då behöver endast de
lokala installationerna vara åtkomliga för respektive administratör.

Den gemensamma databasen och fillagringen måste ändå skyddas och säkerhetskopieras.
Säkerställ också att alla installationer använder kompatibla versioner av
adminverktyget och att samtidig redigering av samma karta hanteras enligt
organisationens rutiner.

## Installation med Docker

### Förutsättningar

Installera Docker Engine på Linux eller Docker Desktop på Windows. Kontrollera installationen:

```bash
docker --version
```

På Windows ska Docker Desktop vara startat och använda Linux-containrar.

### Kom igång med Docker

Det finns en färdigbyggd avbild, så det räcker att starta den. Docker hämtar den automatiskt första gången.

**Prova snabbt** (databas och kartor sparas bara inuti containern, så använd det bara för test):

```bash
docker run --name origo-admin -d -p 8080:8080 ghcr.io/kristianstad/origo-admin:2.10.0
```

Öppna sedan <http://localhost:8080/adm/> och logga in med `origo` / `origo`.

**Rekommenderad start** med kataloger på hosten, så att data överlever att containern byts ut:

1. Skapa katalogerna (Windows-exempel finns under [Hostkataloger och fileshare](#hostkataloger-och-fileshare)):

```bash
mkdir -p /srv/origo-admin/pgdata /srv/origo-admin/constants /srv/origo-admin/maps
```

2. Starta containern. Byt lösenordet i `VAR_ADMPASSWORD`:

```bash
docker pull ghcr.io/kristianstad/origo-admin:2.10.0
docker run --name origo-admin \
  --detach \
  --restart unless-stopped \
  --publish 8080:8080 \
  --env VAR_ADMUSER=origo \
  --env VAR_ADMPASSWORD=byt-det-har-losenordet \
  --mount type=bind,source=/srv/origo-admin/pgdata,target=/pgdata \
  --mount type=bind,source=/srv/origo-admin/constants,target=/www/adm/constants \
  --mount type=bind,source=/srv/origo-admin/maps,target=/www/maps \
  ghcr.io/kristianstad/origo-admin:2.10.0
```

3. Testa i webbläsaren:

- <http://localhost:8080/adm/> – inloggningsruta. Logga in med värdena i `VAR_ADMUSER` och `VAR_ADMPASSWORD`.
- <http://localhost:8080/> – demokartan, som visar att Origo fungerar.

Första starten tar längre tid, eftersom PostgreSQL initieras och SQL-filerna körs (följ med `docker logs -f origo-admin`). Den tomma `constants`-katalogen fylls vid första starten med standardfilerna. Använd inte standardlösenordet `origo` i en nätåtkomlig miljö.

**Med Docker Compose** (spara som `docker-compose.yml` och kör `docker compose up -d`):

```yaml
services:
  origo-admin:
    image: ghcr.io/kristianstad/origo-admin:2.10.0
    container_name: origo-admin
    restart: unless-stopped
    ports:
      - "8080:8080"
    environment:
      VAR_ADMUSER: origo
      VAR_ADMPASSWORD: byt-det-har-losenordet
    volumes:
      - ./pgdata:/pgdata
      - ./constants:/www/adm/constants
      - ./maps:/www/maps
```

**Utan egen dator:** testa avbilden i [Iximiuz Labs](https://labs.iximiuz.com/playgrounds):

1. Starta en Docker playground.
2. Kör `docker run -p 8080:8080 ghcr.io/kristianstad/origo-admin:2.10.0` i terminalen.
3. Välj *Expose ports* i menyn, gör port 8080 publik och klicka på adressen.
4. Lägg till `/adm/` i adressen och logga in med `origo` / `origo`.

### Färdigbyggd avbild

Avbilden `ghcr.io/kristianstad/origo-admin` byggs av GitHub Actions (`.github/workflows/docker-image.yml`)
och är öppen att hämta utan inloggning. Bygget startas manuellt. Alla versioner finns på
[paketsidan](https://github.com/Kristianstad/origo-admin/pkgs/container/origo-admin).

| Tagg | Betydelse |
|---|---|
| `latest` | Senaste bygget som kördes från standardbranchen (`main`). Bra för att prova. |
| `2.10.0` (och andra versionsnummer) | Bygget för det versionsnummer som angavs när bygget startades. Använd en sådan tagg i drift, så att du själv väljer när du byter version. |

**Uppdatera till en ny avbild** (databas, kartor och inställningar ligger kvar, eftersom de ligger i dina egna kataloger). Ta backup först, se [Backup och uppgradering](#backup-och-uppgradering):

```bash
docker pull ghcr.io/kristianstad/origo-admin:2.10.0
docker rm -f origo-admin
docker run --name origo-admin ...   # samma kommando som förut, med samma kataloger
```

Med Compose: `docker compose pull && docker compose up -d`

**Bra att veta:**

- Bygget körs på `ubuntu-latest` utan angiven plattform, så avbilden byggs för `amd64` (vanliga Intel/AMD-datorer).
- Dockerfile anger Alpine 3.23, PostgreSQL 18 och PHP 8.5. Byter en ny avbild PostgreSQL-huvudversion krävs databas-dump och återställning.
- Vill du ändra något i avbilden eller bygga den själv, se [Bygga avbilden själv](#bygga-avbilden-själv-valfritt).

### Hostkataloger och fileshare

Exemplet ovan använder bind mounts i stället för namngivna Docker-volymer. En
bind mount kopplar en vanlig katalog på hosten direkt till en katalog i
containern. Det gör backup, inspektion och flytt av data enklare. Katalogerna
som anges som `source` måste redan existera på hosten.

Windows-exempel i PowerShell:

```powershell
docker run --name origo-admin --detach --publish 8080:8080 `
  --env VAR_ADMUSER=origo `
  --env VAR_ADMPASSWORD=byt-det-har-losenordet `
  --mount type=bind,source=C:\origo-admin\pgdata,target=/pgdata `
  --mount type=bind,source=C:\origo-admin\constants,target=/www/adm/constants `
  --mount type=bind,source=C:\origo-admin\maps,target=/www/maps `
  ghcr.io/kristianstad/origo-admin:2.10.0
```

En hostkatalog kan även vara en fileshare som monterats på hosten, till exempel
NFS eller SMB. Montera filesharen först och använd sedan dess lokala mountpoint
som `source` i `--mount`. Fileshare är dock inte optimalt för `/pgdata`, där
lokal lagring med tillförlitlig låsning och filsystemsstöd rekommenderas.
Fileshare passar bättre för `/www/maps` och `/www/adm/constants`.

Montera inte en tom katalog över hela `/www`, eftersom det döljer webbkoden som
finns i avbilden. Montera i stället de avsedda underkatalogerna:

- `/pgdata` för PostgreSQL-data
- `/www/adm/constants` för lokala anslutnings-, cookie-, auth- och proxyinställningar
- `/www/maps` för genererade kartkonfigurationer

### Så fungerar avbilden

- **Program:** `VAR_FINAL_COMMAND` startar PHP-FPM, PostgreSQL och nginx. nginx lyssnar på port 8080 i containern och är det enda som behöver publiceras. PostgreSQL lyssnar på containerns port 5432. Anslutningar från containern själv (`localhost`) litas på; anslutningar från andra adresser kräver lösenord (regler i `VAR_HBA`). Publicera därför inte port 5432 om du inte behöver nå databasen utifrån.
- **Inloggning:** nginx skyddar `/adm` med basic auth. Användarnamn och lösenord kommer från `VAR_ADMUSER` och `VAR_ADMPASSWORD`.
- **Första start** (när containern startas första gången):
  - Om `/pgdata` är tom initieras PostgreSQL och SQL-filerna i `finalfs/initdb/` körs. De skapar databasen `origo`, tabellerna i schemat `map_configs` och den skrivskyddade databasanvändaren `origo_updated_readonly`.
  - Inloggningen för `/adm` skapas från `VAR_ADMUSER` och `VAR_ADMPASSWORD`. Vill du byta lösenord, skapa en ny container med nya värden.
  - PHP-FPM:s `www.conf`, `50-setting.ini` och `ldap.conf` skapas från `VAR_wwwconf_*`, `VAR_phpini_*` och `VAR_ldapconf_*`.
  - Saknade konstantfiler kopieras från avbildens standardvärden till `/www/adm/constants`. Befintliga filer skrivs aldrig över.
- **Efter första start** ändras inget av detta automatiskt: en ny avbild uppdaterar varken den befintliga databasen eller dina konstantfiler. Se [Backup och uppgradering](#backup-och-uppgradering).

### Kontrollera container och loggar

```bash
docker ps
docker logs -f origo-admin
```

Vid första uppstarten initieras PostgreSQL och SQL-filerna i `finalfs/initdb/` körs. Det kan ta en stund innan tjänsten är klar.

```bash
docker stop origo-admin
docker start origo-admin
docker rm -f origo-admin
```

Det sista kommandot tar bort containern men inte hostkatalogerna. Ta inte bort
`/srv/origo-admin/pgdata` eller `C:\origo-admin\pgdata` om databasen ska behållas.

### Port och reverse proxy

För lokal utveckling räcker `--publish 8080:8080`. Den första porten är
hostens port och den andra är containerns interna port. Därför kan tjänsten
publiceras på valfri ledig hostport, till exempel `--publish 9090:8080`, och
nås via `http://localhost:9090/adm/`. I produktion bör containern ligga bakom
en reverse proxy med HTTPS, DNS, brandvägg och begränsad åtkomst till `/adm/`.

Om endast en lokal reverse proxy ska nå containern:

```bash
--publish 127.0.0.1:8080:8080
```

### Bygga avbilden själv (valfritt)

Bara om du vill ändra något i avbilden. Hämta repot (*Code* → *Download ZIP*, eller `git clone https://github.com/Kristianstad/origo-admin`) och kör i repots rot:

```bash
docker build -t origo-admin .
docker run --name origo-admin -d -p 8080:8080 origo-admin
```

Bygget hämtar basavbilderna `ghcr.io/kristianstad/origo` och `ghcr.io/kristianstad/secure_and_minimal` samt Composer-paketen (till exempel `adldap2/adldap2`), så det kräver internetåtkomst. Vilken Origo-version som används styrs av build-argumentet `ORIGO_VERSION` (standard anges i `Dockerfile`):

```bash
docker build --build-arg ORIGO_VERSION=2.10.0-r1 -t origo-admin .
```

Dokumentation, `README.md` och `.github/` tas inte med i avbilden (se `.dockerignore`). Starta sedan din egen avbild med samma `docker run`-kommando som ovan, men med `origo-admin` i stället för `ghcr.io/kristianstad/origo-admin:2.10.0`.

## Installation utan Docker

Välj detta endast om organisationen redan har en plattform för PHP och
PostgreSQL. Microsoft IIS kan användas i stället för nginx, men detta är en
manuell integrationsinstallation och inte den primära, testade
distributionsvägen.

IIS kan köra PHP via FastCGI. Du behöver då själv konfigurera PHP för IIS,
FastCGI-processen, statiska filer, URL-/sökvägshantering, autentisering och
skrivbehörigheter. Nginx-konfigurationen i projektet kan inte användas direkt
i IIS. PHP måste fortfarande ha tillgång till PostgreSQL, Composer-bibliotek,
`/www/adm/constants`, `/www/maps` och eventuella `/services`-filer.

**Rekommendation:** använd IIS endast om organisationen redan har standardiserad
PHP/FastCGI-drift på Windows. Använd annars Docker Desktop för utveckling eller
Linux med Docker Engine för serverdrift.

Installera:

- nginx eller Apache med PHP-FPM, eller IIS med PHP NTS och FastCGI
- PHP med `json`, `pgsql`, `session`, `simplexml`, `openssl` och vid LDAP `ldap`
- PostgreSQL
- Composer och Git
- Composer-paketen `adldap2/adldap2`, `matthiasmullie/minify`, `thenetworg/oauth2-azure` och `league/oauth2-client`

Placera webbkoden så att `finalfs/www/adm/` motsvarar `/www/adm/`. PHP/FastCGI
måste kunna läsa koden och skriva till `$webRoot/maps` när kartkonfigurationer
publiceras.

På samma sätt som i Docker bör `/www/adm/constants` monteras från en katalog
på hosten. Där ligger lokala anslutnings-, cookie-, auth- och proxyinställningar.
Montera även `/pgdata` på hosten om PostgreSQL körs lokalt i den egna
installationen.

Skapa databasen och initiera schemat:

```bash
psql --dbname=origo --file=finalfs/initdb/050.postgres.sql
psql --dbname=origo --file=finalfs/initdb/060.origo.sql
```

Anpassa därefter `finalfs/www/adm/constants/dbhConnectionString.php`. Standardsträngen använder `localhost`, databasen `origo`, användaren `postgres` och lösenordet `postgres`; detta ska inte användas oförändrat i produktion.

Webbservern måste skicka PHP-filer till PHP-FPM eller IIS FastCGI, skydda
`/adm/`, ge PHP tillgång till PostgreSQL och tillåta skrivning av genererade
kartfiler. Studera Dockerfile och `finalfs/start/stage3/100.authnginx` för
nginx-referens; IIS kräver motsvarande manuell FastCGI-konfiguration.

## Origo-konfiguration och kartor

Egna kartor läggs till genom adminverktyget och publiceras med **Skriv kartkonfiguration**. `writeConfig.php` skapar kataloger under `$webRoot/maps/<kartnamn>` rekursivt.

Om extern Origo används ska `/www/maps` monteras från samma gemensamma
lagringsplats som den externa Origo-installationen läser från. Det kan vara en
lokal hostkatalog eller en monterad fileshare. Annars kan adminverktyget skriva
kartkonfigurationen utan att den publika Origo-servern hittar filerna.

Adminverktyget kan också användas helt frikopplat från Origo. I det läget
ansluter verktyget till databasen och kartkonfigurationen exporteras manuellt
som JSON. JSON-filerna kan därefter granskas, versionshanteras och kopieras till
den Origo-installation där kartorna ska publiceras. Detta passar när Origo inte
ska ha direkt åtkomst till adminverktygets databas eller gemensamma filkatalog.

### SQL-import

Under **Verktyg > Importera SQL** kan en SQL-text klistras in eller en `.sql`-
fil laddas upp och köras mot admin-databasen. Kontrollera alltid SQL-filen och
ta backup först; verktyget kör SQL direkt med den databasanslutning som anges i
`dbhConnectionString.php`.

## Konfiguration och miljövariabler

### Variabler för avbilden

Sätts med `--env NAMN=värde` (docker run) eller under `environment:` (compose). Standardvärdena kommer från `Dockerfile` om inget annat anges.

| Variabel | Standard | Betydelse |
|---|---|---|
| `VAR_ADMUSER` | `origo` | Användarnamn för inloggning till `/adm`. |
| `VAR_ADMPASSWORD` | `origo` | Lösenord för inloggning till `/adm`. **Byt det.** |
| `VAR_LINUX_USER` | `postgres` | Linuxanvändare som kör `VAR_FINAL_COMMAND`. |
| `VAR_FINAL_COMMAND` | se `Dockerfile` | Kommandot som startar PHP-FPM, PostgreSQL och nginx. |
| `VAR_ORIGO_CONFIG_DIR` | `/etc/origo` | Katalog med konfigurationsfiler för Origo. Filer och kataloger i den läggs till i Origos webbkatalog vid uppstart. Standardvärdet kommer från basavbilden. |
| `VAR_CONFIG_DIR` | `/etc/nginx` | Katalog med konfigurationsfiler för nginx. Standardvärdet kommer från basavbilden. |
| `VAR_LOG_LEVEL` | `info` | Nginx-loggnivå. Standardvärdet kommer från basavbilden. |
| `VAR_HBA` | `local all all trust, host all all 127.0.0.1/32 trust, host all all ::1/128 trust, host all all all md5` | Regler för PostgreSQL:s `pg_hba.conf`, kommaseparerade. |
| `VAR_param_<parameter>` | – | Inställning i PostgreSQL:s `postgresql.conf`, till exempel `VAR_param_timezone` (standard `'UTC'`). Värden skrivs med enkla citattecken. |
| `VAR_phpini_<parameter>` | – | Parameter i `/etc/php<version>/conf.d/50-setting.ini` (PHP-versionen anges i `Dockerfile`). |
| `VAR_wwwconf_<parameter>` | `pm=dynamic`, `pm.max_children=5`, `pm.min_spare_servers=1`, `pm.max_spare_servers=3` | Parameter i PHP-FPM:s `www.conf`. |
| `VAR_ldapconf_<parameter>` | – | Parameter i `/etc/openldap/ldap.conf`. |

I parameternamn representerar dubbelt understreck (`__`) en punkt, till exempel `VAR_wwwconf_pm__max_children` för `pm.max_children`. PHP-, PHP-FPM-, LDAP- och PostgreSQL-filerna skapas vid första start. Ändra variablerna för en befintlig installation genom att skapa en ny container med samma kataloger.

Avbilden bygger på [Kristianstad/nginx](https://github.com/Kristianstad/nginx/pkgs/container/nginx), som har egna miljövariabler för webbserverinställningar. Se det repositoryt för en fullständig lista.

Verifiera alltid variabler mot den valda avbildsversionen. Lägg inte känsliga värden i Git, publika kommandon eller shellhistorik.

### Inställningar i `constants`

Miljö- och installationsspecifika inställningar för adminverktyget ligger i
`finalfs/www/adm/constants`. Vid Docker-drift kan katalogen monteras från
hosten på `/www/adm/constants`. Vid installation utan Docker redigeras filerna
direkt i motsvarande katalog. Ändra normalt bara de lokala konfigurationsfilerna
och behåll en säkerhetskopia före uppgradering. En teknisk referens över alla
konstantfiler finns i [docs/www/adm/constants.md](docs/www/adm/constants.md).

#### Databas och sökvägar

- `dbhConnectionString.php` innehåller PostgreSQL-anslutningen som används av
  adminverktyget, till exempel host, port, databasnamn, användare, lösenord,
  SSL-läge och anslutningstimeout. Använd inte standardlösenordet i produktion.
- `configSchema.php` anger PostgreSQL-schemat där Origo-konfigurationen finns,
  normalt `map_configs`.
- `webRoot.php` anger den interna sökvägen till webbroten, normalt `/www`.
  Den måste stämma med den katalog där kartor och övriga Origo-filer finns.
- `proxyRoot.php` anger ett extra sökvägsprefix som läggs till i genererade
  kartlänkar när Origo ligger bakom en reverse proxy eller publiceras under en
  underkatalog.
- `previewBase.php` anger bas-URL för HTML-filer som skapas vid förhandsvisning.

#### Inloggning och sessioner

- `authMethod.php` väljer autentiseringsmetod: `azure` för Microsoft Entra ID
  eller `ldap` för Active Directory/LDAP.
- `azureConfig.php` innehåller klient-ID, klienthemlighet, redirect-URI,
  tenant och OAuth-scopes för Microsoft Entra ID. Klienthemligheten ska inte
  lagras i Git.
- `adldapConfig.php` innehåller LDAP/Active Directory-servrar, base DN,
  bind-användare, lösenord, port, SSL/TLS, protokollversion, timeout och
  referral-hantering.
- `adDomain.php` anger den fullständiga Active Directory-domänen.
- `adGroupFilter.php` innehåller AD-grupper som ska döljas från vyer i
  adminverktyget.
- `forwardauthSessionConfig.php` styr sessionens grundlivslängd, förlängning
  vid aktivitet och absoluta maxlivslängd.
- `cookieConfig.php` styr sessionscookie-namn, krypteringsnyckel, livslängd,
  domän, sökväg, `Secure`, `HttpOnly` och `SameSite`. Byt särskilt
  `cookieKey` i en egen installation och använd en unik slumpmässig nyckel.

#### Adminvyer och tabellbeteende

- `views.php` bestämmer vilka konfigurationsfält som visas i vyerna `Origo`,
  `Extra`, `Meta`, `Infoförv` och `Verktyg`. Här kan man även anpassa vilka
  tabeller som hör till respektive vy.
- `keywordCategorized.php` anger vilka tabeller som ska grupperas efter
  nyckelord i adminverktyget.
- `multiselectables.php` anger vilka tabeller och alias som får
  multiselect-kontroller.
- `tableAliases.php` mappar interna alias till riktiga tabeller, till exempel
  `exports` till `layers`.
- `exclusiveOperationGroups.php` anger tabeller som ska behandlas som
  ömsesidigt exklusiva vid vissa operationer.
- `arrayColumns.php` beskriver vilka kolumner som innehåller arraydata och
  därför ska läsas och skrivas som arrayer.
- `sourcesQueryColumns.php` anger vilka kolumner i `sources` som ska läggas
  till som URL-parametrar när källans `base_url` byggs.

Dessa filer påverkar hur adminverktyget tolkar databasschemat. Ändra dem bara
om databasen eller den önskade adminvyn faktiskt ska anpassas, och kontrollera
alltid att namnen motsvarar tabeller och kolumner i `060.origo.sql`.

#### Kartor, tjänster och metadata

- `restrictedServiceUrl.php` anger intern adress till begränsade karttjänster
  som ska kunna nås av Origo-servern men inte vara publikt åtkomliga.
- `iconTtl.php` styr TTL-parametern för URL:er till lagerikoner. Värdet `-1`
  stänger av TTL-parametern.
- `mapstateMaxUnused.php` anger efter hur många dagar oanvända mapstates tas
  bort automatiskt.
- `searchEngineMeta.php` innehåller geografiska metadata och utgivarinformation
  som används vid sökmotorindexering.
- `swedishDic.php` innehåller svensk terminologi som används av verktyget.

Filer som `dbhConnectionString.php`, `azureConfig.php`, `adldapConfig.php`,
`cookieConfig.php`, `webRoot.php`, `proxyRoot.php` och
`restrictedServiceUrl.php` är normalt miljöspecifika. De bör därför hanteras
som lokal konfiguration och inte ersättas slentrianmässigt vid en uppgradering.

## Backup och uppgradering

Säkerhetskopiera minst hostkatalogen som monteras på `/pgdata`, hostkatalogen
som monteras på `/www/maps`, samt `/www/adm/constants` och egna proxy-
konfigurationer. En logisk dump kan skapas så här:

```bash
docker exec origo-admin pg_dump --username postgres --dbname origo > origo.sql
```

Testa återställning i en separat databas.

Vid uppgradering: ta backup, hämta en ny avbild, stoppa och ta bort containern utan att ta bort hostkatalogerna, starta med samma bind mounts och kontrollera loggar, admin och publicerade kartor. Kör inte om initierings-SQL manuellt mot en befintlig databas utan att först kontrollera om tabeller och data redan finns.

### Databasschema vid ny version

En ny Docker-avbild uppdaterar inte automatiskt en redan initierad PostgreSQL-
databas. `finalfs/initdb/060.origo.sql` används framför allt när databasen
skapas första gången. Om en ny version innehåller nya tabeller, kolumner, index,
constraints eller grunddata i den filen måste databasschemat därför kontrolleras
och uppdateras separat.

Jämför den gamla och den nya versionens initierings-SQL innan uppgraderingen:

```bash
git diff OLD_VERSION..NEW_VERSION -- finalfs/initdb/060.origo.sql
```

Testa schemaändringen på en återställd kopia av produktionsdatabasen. Kontrollera
bland annat att befintliga data passar nya `NOT NULL`-kolumner, att nya index och
constraints kan skapas och att eventuella nya standardrader inte krockar med
lokalt ändrade rader. `060.origo.sql` kan köras om mot ett kompatibelt befintligt
schema: tabeller skapas bara om de saknas och seed-rader med befintlig
primärnyckel lämnas orörda.

Detta gör inte filen till en fullständig databas-migrering. Nya kolumner,
datatyper, constraints och ändringar av befintliga tabeller måste fortfarande
hanteras med separata, kontrollerade `ALTER TABLE`- eller andra
migrationssatser. Ta en ny backup och verifiera att adminverktyget kan läsa och
skriva alla berörda tabeller innan den nya versionen öppnas för användare.
Samma schemaarbete krävs oavsett om adminverktyget körs med eller utan Docker.

### Uppgradera administrationsverktyget från GitHub

Källkoden för administrationsverktyget finns i branchen `main` i
[Kristianstad/origo-admin](https://github.com/Kristianstad/origo-admin/tree/main). Säkerhetskopiera
databasen, `/www/maps` och `/www/adm/constants` före uppgradering. Kontrollera
även ändringar i initierings-SQL och databasschema innan en ny version tas i
drift.

Med Docker rekommenderas normalt den publicerade avbilden. Hämta den nya
avbildsversionen med `docker pull` och skapa om containern med samma bind mounts.
Senaste bygget finns som `ghcr.io/kristianstad/origo-admin:latest`, se
[Färdigbyggd avbild](#färdigbyggd-avbild).

Stoppa därefter den gamla containern och starta den nya avbilden med samma
miljövariabler, portar och bind mounts. Ta inte bort hostkatalogerna.

Utan Docker uppdateras en befintlig checkout i stället för en container:

```bash
cd /sökväg/till/origo-admin
git fetch origin
git checkout main
git pull --ff-only origin main
```

Stoppa webbservern eller PHP-processen under filuppdateringen, installera eller
uppdatera de Composer-paket som installationen använder, och publicera de nya
filerna från `finalfs/www/adm/`. Starta sedan webbservern/PHP igen och kontrollera
inloggning, kartskrivning, loggar och eventuella schemaändringar. Behåll lokala
filer i `/www/adm/constants` och kartdata i `/www/maps`.

## Felsökning

### Adminsidan går inte att öppna

```bash
docker ps
docker logs origo-admin
```

Kontrollera därefter port, brandvägg och URL:en `/adm/`.

### Containern startar inte

Läs sista raderna i `docker logs origo-admin`. Om Docker svarar att en bind mount-sökväg inte finns, skapa katalogen på hosten och starta igen. Katalogerna efter `source` i `--mount` måste redan existera. Första starten tar längre tid än senare starter (PostgreSQL initieras), så vänta tills loggen slutar ändras innan du drar slutsatsen att något är fel.

### Inloggningen fungerar inte

Kontrollera `VAR_ADMUSER`, `VAR_ADMPASSWORD`, auth-konfiguration, cookies, HTTPS och reverse-proxy-sökväg.

### Kartkonfigurationen skrivs inte

Kontrollera att kartan är markerad som ändrad, att PHP-FPM eller IIS FastCGI
kan skriva `$webRoot/maps`, att PostgreSQL fungerar och att kartan finns i
`map_configs.maps`.

### PostgreSQL startar inte

Kontrollera lagringskatalogens rättigheter, `docker logs` och att `/pgdata` inte används med en inkompatibel PostgreSQL-version.

## Säkerhetschecklista före produktion

- Byt standardlösenordet.
- Använd HTTPS.
- Begränsa åtkomsten till `/adm/`.
- Publicera inte PostgreSQL-porten 5432 om den inte behövs.
- Ordna med regelbundna backuper.
- Tidigare dokumentation anger att avbilden kan köras med alla Linux-capabilities borttagna utom `CHOWN`, `SETPCAP`, `SETGID` och `SETUID`. Det har inte verifierats för administrationsavbilden; testa i så fall `--cap-drop ALL` med dessa `--cap-add` innan du använder det i drift.

## Licens

Filerna i det här repot är utgivna under BSD 2-Clause-licensen, se [LICENSE](LICENSE).

Licensen gäller bara filerna i repot. Den byggda avbilden innehåller även bland annat Origo, nginx, PHP, PostgreSQL och Composer-paket, som har sina egna licenser.
