# 10. Actualizare și deployment în cPanel

## Structura recomandată

Aplicația Laravel trebuie păstrată într-un director privat al contului cPanel, iar document root-ul domeniului trebuie să indice către directorul `public/`. Fișierul `.env`, repository-ul `.git`, sursele și directoarele `storage/` nu trebuie expuse public.

Versiunea PHP recomandată este 8.2 sau 8.3. Sunt necesare extensiile uzuale Laravel, inclusiv `pdo_mysql`, `mbstring`, `xml`, `dom`, `intl`, `bcmath`, `fileinfo`, `openssl`, `tokenizer`, `ctype`, `json` și o extensie pentru imagini.

## Actualizare completă din panoul admin

În `Admin → Configurare Platformă → Actualizare platformă` există acțiunea **Actualizează din Git și aplică**.

Fluxul executat:

1. verifică existența directorului `.git`;
2. rulează `git status --porcelain --untracked-files=no`;
3. oprește actualizarea dacă există fișiere versionate modificate local;
4. citește revizia curentă cu `git rev-parse HEAD`;
5. rulează `git pull --ff-only`;
6. citește noua revizie;
7. rulează `php artisan migrate --force`;
8. rulează `php artisan optimize:clear`;
9. scrie rezultatul și reviziile în logul Laravel;
10. afișează în Filament succesul sau motivul opririi.

Comanda Git este executată prin Symfony Process ca listă de argumente, fără shell. Nu se folosesc `reset`, `checkout`, `clean`, force-push sau alte operații care pot suprascrie schimbări.

## Cerințe cPanel pentru actualizarea Git

- directorul aplicației trebuie să fie un checkout Git complet, nu doar o arhivă de fișiere;
- utilizatorul sub care rulează PHP trebuie să poată citi și scrie în repository;
- comanda `git` trebuie să fie disponibilă în `PATH` pentru PHP;
- remote-ul și upstream-ul ramurii trebuie să fie configurate;
- pentru repository privat, autentificarea Git trebuie configurată la nivelul contului cPanel;
- funcția PHP `proc_open` trebuie să fie permisă, deoarece Symfony Process o folosește.

Dacă executabilul Git nu este disponibil sub numele `git`, se poate configura în `.env`:

```env
GIT_BINARY=/usr/bin/git
```

Nu se păstrează parole, tokenuri Git sau alte secrete în cod ori în documentație.

## Aplicarea exclusivă a migrărilor

Acțiunea **Aplică actualizarea** rămâne disponibilă ca variantă de recuperare atunci când codul a fost actualizat deja manual din cPanel. Ea rulează `migrate --force` și `optimize:clear`.

Ambele acțiuni încearcă să curețe cache-ul inclusiv după un eșec de migrare. Acest lucru previne situația în care fișierele noi sunt prezente, dar Laravel folosește în continuare un cache vechi de rute.

## Endpoint-ul securizat de migrare

Pentru recuperare fără acces la pagina Filament există:

```text
https://domeniu-client.ro/__deploy/migrate?token=VALOAREA_DEPLOY_TOKEN
```

Endpoint-ul rulează numai `migrate --force` și `optimize:clear`. Tokenul este citit din `DEPLOY_TOKEN` din `.env`; valoarea reală nu trebuie salvată în repository sau documentație.

Endpoint-ul `fresh` este distructiv și nu trebuie folosit pentru actualizări normale.

## Probleme și recuperare

### Rută Filament inexistentă după actualizarea codului

Cauza tipică este un cache vechi de rute. Dacă nici endpoint-ul de deployment nu apare, se poate șterge manual numai fișierul `bootstrap/cache/routes-*.php`, după care se rulează acțiunea de actualizare.

### Fișiere modificate local

Actualizarea completă se oprește înainte de `git pull`. Modificările trebuie verificate și păstrate separat sau comise prin fluxul normal; butonul nu le șterge.

### Git indisponibil pentru PHP

Se verifică `GIT_BINARY`, permisiunea `proc_open`, drepturile repository-ului și configurarea remote-ului. Varianta de rezervă este actualizarea din cPanel Git Version Control urmată de **Aplică actualizarea**.

### Migrare eșuată după pull

Codul poate fi deja actualizat, dar schema nu. Mesajul este păstrat în `storage/logs/laravel.log`. Nu se rulează `migrate:fresh`; se repară migrarea și se reia actualizarea.

## Istoric modificări

- **2026-10-30** — adăugate condițiile de instalare pentru conturi clienți, facturi și API-ul de rapoarte n8n.

- **2026-08-10** — adăugat endpoint-ul securizat pentru migrare și curățarea cache-ului.
- **2026-08-10** — adăugată acțiunea admin pentru migrări și cache.
- **2026-10-29** — adăugată actualizarea completă `git pull --ff-only` → migrări → cache din panoul admin.
- **2026-10-29** — acțiunile de deployment încearcă recuperarea cache-ului și după eșec.
- **2026-10-29** — eliminată documentația veche care conținea date sensibile și instrucțiuni neactuale.

## Actualizarea din 30 octombrie: conturi clienți și n8n

Codul comun poate fi actualizat pe ambele instalări. WPA folosește `enable_service_module = true`; pe instalarea care folosește numai meniul modulul rămâne dezactivat. Noile resurse/widgetul și API-ul verifică această setare; nu există condiționare de numele unui client în cod.

1. Faceți backup bazei de date și aplicați actualizarea obișnuită din admin sau comenzile `php artisan migrate --force` și `php artisan optimize:clear` după aducerea codului.
2. Migrarea `2026_10_30_090000_add_service_customer_accounts.php` este aditivă și nu modifică fișele vechi. Creează tabelele și pe instalarea cu modulul dezactivat, fără a activa funcția.
3. În WPA completați registrul de clienți. Numele libere din fișele vechi nu sunt convertite automat în identități de client.
4. Pentru n8n configurați un token separat și `SERVICE_REPORT_TIMEZONE`, conform [documentului API](21-api-rapoarte-n8n.md). Tokenul gol dezactivează API-ul; nu folosiți tokenul de deployment.
5. Configurați separat în n8n autentificarea SMTP pentru `notificari@daserdesign.ro`, destinatarul și orele dorite. Codul aplicației nu trimite automat e-mailuri și nu conține credențiale SMTP noi.

Nu a fost efectuat deployment pe serverele clienților în această lucrare. Rollback-ul noii migrări este refuzat dacă au fost introduse date operaționale de clienți/facturi; folosiți corecții înainte, nu ștergerea evidenței.
