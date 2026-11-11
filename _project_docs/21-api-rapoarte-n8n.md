# 21. API de rapoarte vulcanizare pentru n8n

## Stare și domeniu

**Implementat în cod**: API JSON numai pentru citire, protejat prin token separat pe instalare; rapoarte zilnice și lunare cu toate lucrările, încasările, lucrările pe cont client, sumele nefacturate și facturile înregistrate. Aceeași logică din `ServiceReportService` alimentează rapoartele admin, print/PDF și raportul zilnic al operatorului. Tokenul poate fi generat, înlocuit și activat/dezactivat din pagina admin `Configurare → API notificări`.

**De configurat la instalare**: tokenul, domeniul WPA și conexiunea n8n. Programarea și expedierea notificărilor vor fi implementate separat în n8n. Utilizatorul a configurat deja SMTP-ul; conectivitatea lui nu a fost verificată în această lucrare. Nu au fost modificate sau activate workflow-uri n8n și nu s-au trimis e-mailuri. `notificari@daserdesign.ro` este expeditorul avut în vedere pentru workflow.

API-ul este disponibil numai dacă `company_settings.enable_service_module` este activ. aBistro poate primi același cod fără să activeze vulcanizarea sau API-ul. Fiecare instalare folosește propria bază de date și propriul token.

## Configurare

### Configurare din admin, fără terminal sau editarea `.env`

După actualizarea codului și rularea migrărilor prin acțiunea admin existentă, deschide `Admin → Configurare → API notificări` (`/admin/service-api-settings`). Pagina este disponibilă pentru utilizatorii autentificați în panoul Filament, când modulul Service este activ. Aplicația nu definește un rol separat de superadmin. Sesiunea PIN a operatorului nu acordă acces la această pagină. Permisiunea este verificată și la generare/salvare, inclusiv dacă modulul este dezactivat între deschiderea formularului și acțiune.

1. Activează accesul API și apasă butonul de generare de lângă `Token nou`. Generatorul folosește `random_bytes(32)` și produce 64 de caractere hexazecimale.
2. Folosește controlul de afișare al câmpului pentru a copia tokenul în credențialul HTTP Header Auth din n8n. Generarea completează formularul și nu modifică încă tokenul folosit de API.
3. Apasă `Salvează configurația API`. Schimbarea devine efectivă imediat, fără curățarea cache-ului. Câmpul se golește după salvare; tokenul curent nu este reîncărcat la deschiderea paginii.
4. La o salvare ulterioară, lasă câmpul gol pentru a păstra tokenul curent. Pentru rotire, generează/copiază/salvează un token nou și actualizează credențialul n8n. Tokenul anterior nu mai este acceptat după salvare.

Formularul validează tokenurile introduse la 32–255 de caractere, fără spații sau caractere albe. Activarea fără un token valid generează eroare de validare. Dezactivarea API-ului este păstrată în baza de date și blochează inclusiv tokenul existent în `.env`, cu răspuns HTTP 503. Reactivarea poate reutiliza tokenul păstrat.

Pagina afișează URL-urile rutelor zilnice/lunare, antetul necesar, fusul orar și indicațiile pentru paginare. Nu conține configurarea SMTP sau destinatarul notificărilor; acestea țin de implementarea n8n.

### Configurarea existentă prin mediu

Instalările configurate anterior pot continua să folosească variabilele din `.env`:

```dotenv
SERVICE_REPORT_API_TOKEN=
SERVICE_REPORT_TIMEZONE=Europe/Bucharest
```

Valoarea din mediu este folosită când nu există un token salvat din admin. Tokenul salvat în baza de date are prioritate. Starea de activare din admin are prioritate și poate bloca accesul indiferent de sursa tokenului. Pentru instalările existente, valorile noi sunt inițial `null`, astfel încât accesul configurat prin `.env` continuă să funcționeze.

Tokenul trebuie generat aleator și să aibă minimum 32 de caractere. Valoarea goală sau prea scurtă dezactivează accesul cu HTTP 503. Nu pune tokenul în URL, fișiere versionate, exemple sau documentație. După modificarea `.env`, acțiunea `Admin → Configurare Platformă → Aplică actualizarea` curăță cache-ul configurației. Salvarea din noua pagină API nu necesită această operație.

Se folosește HTTPS și headerul `Authorization: Bearer <TOKEN_CONFIGURAT>`. API-ul nu folosește sesiunea admin/PIN-ul operatorului și nu necesită CSRF. Compararea tokenului folosește `hash_equals`. Există limită de 30 de cereri pe minut pentru cererile autentificate și antete `Cache-Control: no-store, private`.

Tokenul poate fi generat pe orice dispozitiv cu un generator criptografic și introdus în formularul admin sau în `.env`. Autorizează citirea rapoartelor. Operațiile de facturare, editare clienți și finalizare fișe au propriile fluxuri autentificate.

### Stocare și migrare

Migrarea `2026_11_11_000002_add_service_report_api_settings_to_company_settings_table.php` adaugă în `company_settings` câmpurile nullable `service_report_api_token` (text) și `service_report_api_enabled` (boolean). Nu modifică relațiile existente. Tokenul folosește cast-ul Laravel `encrypted`, cu cheia aplicației `APP_KEY`, și este exclus din serializarea modelului, inclusiv din datele încărcate în configurarea generală a platformei. Formularul dedicat primește numai tokenul nou introdus/generat.

Rollback-ul elimină cele două câmpuri și revine la configurația din mediu. O instalare care dezactivase API-ul din admin poate redeveni accesibilă prin tokenul `.env` după rollback. Cheia `APP_KEY` trebuie păstrată pentru decriptarea valorilor deja salvate, conform mecanismului Laravel de criptare.

## Rute și parametri

| Metodă/rută | Parametri | Comportament implicit |
|---|---|---|
| `GET /api/v1/service/reports/daily` | `date=YYYY-MM-DD`, `page`, `per_page` | ziua curentă în fusul raportului |
| `GET /api/v1/service/reports/monthly` | `month=YYYY-MM`, `page`, `per_page` | luna curentă în fusul raportului |

Exemple fără secrete: `/api/v1/service/reports/daily?date=2026-10-30` și `/api/v1/service/reports/monthly?month=2026-10`.

`page` este un întreg de minimum 1. `per_page` este între 1 și 100, implicit 100. Paginarea se aplică listei `orders.data`; totalurile și listele de clienți/facturi/servicii/operatori reprezintă întregul raport și se repetă pe fiecare pagină. Pentru un mesaj doar cu sumarul este suficientă prima pagină. Pentru toate lucrările, n8n citește paginile până la `orders.last_page` și adaugă **doar** `orders.data`; nu însumează din nou sumarul fiecărei pagini.

Erori JSON: 401 pentru token lipsă/incorect, 404 pentru modul dezactivat, 422 pentru dată/lună/paginare invalidă, 429 la depășirea limitei, 503 pentru API neconfigurat. Nici cererile fără `Accept: application/json` nu sunt redirecționate la login.

## Structura răspunsului

- `meta`: perioada, limitele cu offset, fusul orar, moneda instalării, momentul generării; `uninvoiced_basis=current_state` marchează explicit că soldul nefacturat este starea din momentul interogării.
- `summary`: număr de fișe, finalizate/deschise/anulate, `completed_value` (valoarea tuturor lucrărilor finalizate), `total_revenue` (numerar + card + mixt achitate), defalcarea metodelor și media valorii lucrărilor.
- `summary.client_account_revenue`: valoarea lucrărilor pe cont finalizate în perioadă, indiferent dacă între timp au fost facturate; denumirea tehnică păstrează convenția rapoartelor, dar suma **nu este o încasare**.
- `summary.protocol_revenue`: valoarea protocolului, separată de încasări.
- `summary.unpaid_revenue`: alte lucrări finalizate care nu sunt încasate, protocol sau cont client; nu se confundă cu soldul de facturat.
- `uninvoiced.period`: numărul și suma lucrărilor finalizate în perioada cerută care sunt **încă** nefacturate.
- `uninvoiced.current`: toate lucrările pe cont client încă nefacturate, inclusiv perioade mai vechi.
- `uninvoiced.clients`: soldul curent și numărul fișelor nefacturate, grupate pe client; lista folosește numele actual al clientului.
- `invoices`: numărul, suma și lista facturilor având `issued_on` în perioada cerută. O factură aferentă mai multor fișe este numărată o singură dată. `amount` este suma introdusă de administrator; `orders_total` este valoarea fișelor asociate.
- `services`, `staff`: serviciile prestate și valoarea lucrărilor per operator, inclusiv cele pe cont și protocol. Fiecare element `services` include `name`, `unit`, `quantity_sold` și `revenue`; agregarea se face după nume și unitate, astfel încât cantitățile în m² și bucăți rămân distincte.
- `orders`: informații de paginare și `data` cu fișele perioadei, clientul snapshot, numărul auto, operatorul, valoarea, plata, numărul facturii și pozițiile. Nu expune PIN-uri, credențiale sau datele private complete ale clientului.

Sumele sunt numere JSON rotunjite la două zecimale, exprimate în `meta.currency`. Suma mixtă nu este împărțită între numerar și card deoarece schema existentă nu păstrează această împărțire.

## Reguli de timp și facturare

Ziua/luna se interpretează în `SERVICE_REPORT_TIMEZONE`, implicit `Europe/Bucharest`, inclusiv schimbarea orei de vară. Limitele sunt convertite în `config('app.timezone')` pentru interogarea timestamp-urilor stocate. Nu se schimbă automat `APP_TIMEZONE` și nu se rescriu timestamp-urile istorice.

Fișele finalizate sunt raportate după `completed_at`; pentru fișe istorice finalizate fără acest câmp se folosește `created_at`. Fișele încă deschise și cele anulate sunt raportate după `created_at`. Numărul `total_orders` reprezintă această selecție, nu toate fișele create în perioadă indiferent de finalizare. Același moment este afișat în istoricul admin/PDF în fusul raportului.

Facturile sunt grupate după data calendaristică `issued_on`, nu după momentul introducerii în admin. Starea de facturare și plata sunt independente: marcarea ca facturat nu adaugă suma în numerar/card, iar aplicația nu urmărește în această lucrare încasarea ulterioară a facturii. Rapoartele istorice citite ulterior pot avea un sold nefacturat mai mic după înregistrarea facturilor. Nu există snapshot contabil imutabil al raportului la închiderea zilei/lunii.

Raportul unui operator este filtrat după `staff_member_id`. Facturile colective nu sunt repartizate între operatori, iar raportul său nu include totalul facturilor.

## Conectarea în n8n — pregătită, neactivată

1. Configurează domeniul WPA, tokenul în credențialul HTTP Header Auth și fusul workflow-ului `Europe/Bucharest`.
2. Adaugă un Schedule Trigger zilnic după încheierea zilei (de exemplu dimineața următoare) și un HTTP Request `GET` către ruta zilnică cu **data zilei precedente** calculată în același fus orar.
3. Pentru raportul lunar, programează prima zi a lunii și transmite **luna precedentă**; nu lăsa parametrul implicit dacă vrei luna încheiată.
4. Folosește prima pagină pentru sumar, apoi paginează numai dacă dorești detaliile tuturor lucrărilor. Include distinct în mesaj: încasări efective, protocol, lucrări pe cont, nefacturat din perioadă, nefacturat total și facturi înregistrate.
5. Configurează separat serviciul de e-mail/SMTP care poate trimite cu expeditorul `notificari@daserdesign.ro`, destinatarul WPA și ora exactă dorită. Credențialele și destinatarul nu se inventează și nu se salvează în repo.
6. Verifică manual raportul și livrarea înainte să activezi schedule-ul. Folosește o cheie de deduplicare bazată pe instalare + tip raport + zi/lună pentru a evita retrimiterea mesajului la un retry al workflow-ului. API-ul poate fi reluat fără să modifice datele; deduplicarea e-mailului ține de workflow.

## Fișiere și instalare

- `app/Modules/Service/Services/ServiceReportService.php` — calcule comune;
- `app/Modules/Service/Http/Controllers/ServiceReportApiController.php` — validare și răspuns publicat;
- `app/Http/Middleware/AuthenticateServiceReportApi.php` — autentificare și activarea modulului;
- `app/Filament/Pages/ServiceApiSettings.php` și `resources/views/filament/pages/service-api-settings.blade.php` — configurare admin, generare și validare token, URL-uri pentru conectare;
- `app/Modules/Settings/Models/CompanySetting.php` — token criptat/ascuns, activare și compatibilitate cu configurația din mediu;
- `routes/api.php`, `bootstrap/app.php`, `config/service_reporting.php` — rutare, erori JSON și configurare;
- rapoartele Filament, admin print/PDF și operator folosesc date pregătite în backend; nu adaugă interogări în template-uri.

Se instalează împreună cu migrarea pentru clienți/facturi și cont client descrisă în [06-service-module.md](06-service-module.md), apoi cu migrarea noii configurări API. Rutele API nu depind de loginul web și nu execută middleware-ul web de licențiere; accesul este condiționat de token și activarea modulului. Licențierea existentă a interfețelor web nu este modificată.

`tests/Feature/ServiceApiSettingsTest.php` verifică randarea paginii, accesul admin, blocarea operatorilor/dezactivării modulului, generarea înainte de salvare, criptarea și ascunderea tokenului, păstrarea la salvare goală, rotirea cu respingerea tokenului vechi, dezactivarea și compatibilitatea cu `.env`. Testele folosesc SQLite în memorie și tokenuri sintetice.

## Istoric modificări

- **2026-11-11** — adăugată configurarea API din admin cu token criptat, generare, rotire și dezactivare; păstrată compatibilitatea cu tokenul `.env`; documentată unitatea din agregatele de servicii și responsabilitatea n8n pentru expedierea notificărilor.

- **2026-10-30** — documentată configurarea fără terminal cPanel: generarea tokenului local, editarea `.env` și curățarea cache-ului prin acțiunea admin existentă.

- **2026-10-30** — implementate API-ul zilnic/lunar numai pentru citire și raportarea comună pentru admin/operator/API; separată valoarea pe cont și protocol de încasările efective; documentată conectarea n8n, încă neactivată.
