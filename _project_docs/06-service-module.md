# 06. Modulul de vulcanizare și servicii auto

## Scop și stare

Modulul `Service` este implementat ca terminal simplificat pentru vulcanizare și spălătorie auto. El are catalog, fișe de lucru, încasare și rapoarte proprii și nu folosește comenzile restaurantului.

Stare la 11 noiembrie 2026. Sunt implementate ierarhia categorie–subcategorie, catalogul clientului cu extinderea din noiembrie, introducerea suprafețelor pentru covoare, reluarea fișei salvate după refresh, conturile clienților și evidența facturilor emise extern. Aplicarea migrărilor și configurarea API-ului pe instalările clienților sunt operații de deployment separate.

## Activare și acces

- Activarea se face prin `company_settings.enable_service_module` din Configurare Platformă.
- Loginul operatorului rămâne la `/staff`, prin PIN.
- Rolul `service` este redirecționat automat către `/service`.
- Rutele `/service/*` sunt protejate de `StaffAuthMiddleware`, `ServiceStaffMiddleware` și `module.enabled:service`.
- Un utilizator staff cu alt rol nu poate apela direct operațiile modulului.
- PIN-ul introdus nu mai este scris în log și nu mai apare în mesajul de eroare.

## Schema și relațiile

### `service_categories`

- `parent_id` este opțional și referă aceeași tabelă.
- `parent_id = null` înseamnă categorie principală.
- un `parent_id` setat înseamnă subcategorie.
- ștergerea părintelui setează `parent_id` la `null`, fără ștergerea subcategoriei.

Relații Eloquent:

- `ServiceCategory::parent()` — categoria principală;
- `ServiceCategory::children()` — subcategoriile ordonate;
- `ServiceCategory::items()` — serviciile categoriei.

### `service_items`

Serviciul conține categoria/subcategoria, denumirea, descrierea, prețul, unitatea, ordinea și starea activă.

### `service_orders`

Fișa păstrează operatorul, datele opționale ale clientului și mașinii, statusul, plata, totalul și momentul finalizării.

Din 30 octombrie, `service_customer_id` leagă fișa de registrul de clienți, iar `service_invoice_id` o leagă de o factură înregistrată. Ambele sunt opționale pentru compatibilitate cu fișele existente. Cheile externe împiedică ștergerea clientului/facturii referite. Relațiile Eloquent sunt `customer()` și `invoice()`.

### Clienți și facturi

- `service_customers`: nume, cod fiscal opțional, telefon, e-mail, adresă și `is_active`. Un client dezactivat rămâne în istoricul fișelor și poate fi facturat, dar nu mai poate fi ales la o încasare nouă.
- `service_invoices`: client, `number` unic pe instalare (se introduce seria împreună cu numărul), `amount` suma facturată, `orders_total` suma originală a fișelor selectate, `issued_on`, `recorded_at`, administratorul `created_by` și snapshot pentru numele/codul fiscal al clientului.
- O factură poate acoperi mai multe fișe ale aceluiași client. Suma facturii este stocată o singură dată și poate diferi de suma lucrărilor, de exemplu pentru o reducere operată în programul extern de facturare.
- Migrarea `2026_10_30_090000_add_service_customer_accounts.php` creează registrul și facturile, adaugă relațiile și indexurile și extinde `payment_method` cu `client_account`. Nu schimbă fișele istorice și nu transformă automat numele libere în clienți: identitatea unei firme nu poate fi dedusă sigur numai din nume.
- Rollback-ul este refuzat dacă există clienți, fișe pe cont sau facturi. După folosirea funcției se aplică migrări corective, fără ștergerea evidenței.

### `service_order_items`

Poziția păstrează snapshot-ul denumirii, unității și prețului. `line_total` este recalculat și rotunjit la bani la salvare, iar comanda își recalculează totalul după salvarea sau ștergerea unei poziții. Cantitatea și prețul au maximum două zecimale. Terminalul și modelul folosesc aceeași rotunjire pentru cantitățile fracționare.

Migrații:

- `2026_10_29_090000_add_hierarchy_and_unit_to_service_module.php` — adaugă `parent_id` și snapshot-ul `unit`;
- `2026_10_29_091000_seed_vulcanizare_service_catalog.php` — încarcă catalogul inițial; rollback-ul nu șterge catalogul, deoarece acesta poate deveni dată operațională editată de client.

## Catalogul inițial

### Vulcanizare

- Autoturisme — 8 tarife după rază și tipul operației;
- Camioane — 9 servicii pentru montaj, dejantare, rezervă, valve, timp suplimentar și presiune;
- Petice — 9 tarife după număr/dimensiune și extra petec;
- Extra auto — 6 servicii pentru șnur, presiune, valvă, ventil și extra.

### Spălătorie

- Spălare interior / exterior — 6 tarife pentru autoturism, SUV, interior, exterior și grade de murdărie.

Datele sunt introduse cu `updateOrInsert`, pe slug/categorie și denumirea serviciului. Rularea migrării pe o instalare existentă nu dublează catalogul seeduit.

### Extinderea din noiembrie 2026

Migrarea `2026_11_11_100000_extend_vulcanizare_and_carpet_catalog.php` adaugă următoarele poziții.

| Categorie | Serviciu | Tarif |
|---|---|---|
| Vulcanizare → Autoturisme | Demontat + montat + echilibrat SUV-4x4 | 50 lei/buc. |
| Vulcanizare → Autoturisme | Curățat jantă | 10 lei/buc. |
| Vulcanizare → Autoturisme | ANVELOPE SH 50 | 50 lei/buc. |
| Vulcanizare → Autoturisme | ANVELOPE SH 70 | 70 lei/buc. |
| Vulcanizare → Autoturisme | ANVELOPE SH 100 | 100 lei/buc. |
| Spălătorie → Covoare | Covoare MP 5 | 5 lei/m² |
| Spălătorie → Covoare | Covoare MP 10 | 10 lei/m² |
| Spălătorie → Covoare | Covoare MP 50 | 50 lei/m² |
| Spălătorie → Covoare | Covoare MP 100 | 100 lei/m² |

SUV-4x4 este încadrat la Autoturisme. Covoarele primesc o subcategorie proprie, cu slug `spalatorie-covoare`. Cele patru tarife sunt poziții distincte selectabile. Operatorul alege tariful și introduce suprafața, de exemplu `2,50` m² la 5 lei/m² produce 12,50 lei.

Migrarea este aditivă și tranzacțională. Refolosește categoria identificată prin slug sau prin numele de sub același părinte, introduce numai serviciile lipsă după categorie și denumire și le ordonează după cele existente. Păstrează prețul, descrierea, starea activă și ordinea unui serviciu deja creat cu același nume în categoria țintă. Reexecutarea păstrează aceleași date, iar rollback-ul păstrează catalogul operațional. Fișele existente își păstrează snapshot-urile. Extinderea folosește schema existentă.

Aplicarea se face prin `Admin → Configurare Platformă → Actualizează din Git și aplică`, sau prin `Aplică actualizarea` după ce codul a fost actualizat separat. Același cod și migrările pot fi instalate la aBistro. Vizibilitatea modulului și API-ului respectă setarea `enable_service_module` a fiecărei instalări.

## Interfața operatorului

View: `resources/views/service/index.blade.php`.

Principii implementate:

- două butoane mari pentru categoriile principale;
- subcategorii mari și ușor de apăsat;
- serviciile apar ca butoane cu denumire și preț;
- apăsarea repetată mărește cantitatea;
- coșul are butoane mari `+` și `−` pentru pozițiile obișnuite;
- pozițiile cu unitatea `m²` afișează câmpul „Suprafață (m²)” și acțiunea „Elimină”, iar alegerea tarifului afișează lei/m²;
- suprafața acceptă separatorul virgulă sau punct și cel mult două zecimale; valorile invalide blochează salvarea și deschiderea încasării și afișează o eroare;
- numărul mașinii este vizibil, dar opțional;
- numele, telefonul și observațiile sunt ascunse sub „Detalii client (opțional)”;
- acțiunea principală este butonul verde `ÎNCASEAZĂ`;
- metodele Numerar, Card, Mixt, Protocol și **Pe cont client** sunt afișate ca butoane mari;
- „Pe cont client” deschide un al doilea pop-up cu clienții activi salvați, căutare după nume/cod fiscal/telefon și confirmare explicită; lista este pregătită în controller;
- fișele deschise sunt într-un panou separat și pot fi reluate;
- interfața este responsive pentru telefon, tabletă și desktop.

Datele și regulile sunt încărcate de controller și modele. View-ul nu interoghează baza de date și nu leagă schema de tema vizuală.

### Reluarea fișei după refresh

După salvarea sau selectarea unei fișe deschise, terminalul păstrează ID-ul ei în adresa paginii (`/service?order=ID`), separat pentru fiecare tab. La refresh reia exclusiv acea fișă din lista de fișe încă deschise pregătită de controller; nu creează un rând nou și nu alege automat altă fișă. O referință către o fișă închisă, anulată sau inexistentă este eliminată din adresă.

„Lasă deschisă” salvează și păstrează fișa curentă pentru continuare. „Fișă nouă” golește explicit formularul și referința din adresă, păstrând fișa anterioară în lista deschiselor. Finalizarea/anularea elimină referința numai după confirmarea serverului; erorile o păstrează pentru retry. Lista locală de fișe deschise se actualizează imediat după salvare. Schimbarea fișei este blocată cât timp se execută o cerere de salvare/plată/anulare.

Se reia **ultima versiune salvată în baza de date**. Nu există autosalvare a modificărilor locale sau garanție de reluare dacă prima salvare este întreruptă înainte să ajungă răspunsul cu ID-ul. Fișele create înainte de această corecție se reiau o dată din „Fișe deschise”; după selectare adresa păstrează ID-ul. Nu sunt necesare migrare nouă, configurare suplimentară sau modificări în admin pentru această corecție.

## Validare și reguli server-side

- este necesară minimum o poziție;
- serviciul trebuie să existe și să fie activ;
- cantitatea trebuie să fie numerică, între 0,01 și 99.999.999,99, cu maximum două zecimale; validarea se aplică și cererilor trimise direct către server;
- valoarea unei poziții și totalul fișei trebuie să se încadreze în limita de 99.999.999,99 lei a coloanelor `DECIMAL(10,2)`; depășirea întoarce HTTP 422 înainte de înlocuirea pozițiilor existente;
- prețul trimis de browser nu este acceptat;
- denumirea, unitatea și prețul sunt recitite din `service_items`;
- la salvarea unei fișe deschise se aplică tarifele curente din catalog, inclusiv la reluarea unui draft vechi; după succes terminalul preia pozițiile confirmate de server, astfel încât modalul de încasare afișează valoarea salvată. Coșul este blocat pe durata cererii, iar un eșec păstrează datele introduse;
- numai fișele cu status `open` pot fi modificate, finalizate sau anulate prin terminal; operațiile recitesc și blochează rândul în tranzacție;
- finalizarea obișnuită setează `completed`, `paid`, metoda de plată și `completed_at`;
- finalizarea pe cont client cere un client activ existent, copiază numele/telefonul acestuia și setează `completed`, `unpaid`, `client_account`, clientul și `completed_at`;
- o fișă pe cont client reprezintă o lucrare finalizată, nu numerar/card încasat;
- nu se poate finaliza o fișă fără servicii și nu se poate finaliza de două ori;
- datele financiare și pozițiile unei fișe pe cont client finalizate sunt blocate, iar fișa nu poate fi ștearsă din administrare;
- clienții folosiți se dezactivează, nu se șterg; facturile înregistrate sunt numai pentru consultare.

## Administrare

- `ServiceCategoryResource` permite categorii principale și subcategorii;
- `ServiceItemResource` afișează selecția ierarhică `Categorie → Subcategorie`;
- `ServiceOrderResource` permite administrarea fișelor și păstrează unitatea în snapshot;
- toate resursele sunt vizibile numai dacă modulul este activ.

### Lucrări nefacturate în dashboard

Widgetul din dashboardul admin afișează fișele `completed` cu `payment_method = client_account` și fără `service_invoice_id`, inclusiv fișe din lunile precedente. Sunt disponibile filtrarea după client și înregistrarea unei facturi pentru o fișă sau pentru selecția multiplă.

Fluxul de lucru:

1. Administratorul salvează clienții în registrul „Clienți vulcanizare”.
2. Operatorul închide lucrarea pe contul clientului ales.
3. Administratorul emite factura în programul propriu de facturare.
4. În dashboard selectează lucrările acelui client și folosește „Marchează facturat”.
5. Introduce seria/numărul, data emiterii și suma facturată; suma lucrărilor selectate este propusă inițial.
6. După salvare, fișele dispar din lista nefacturatelor și factura rămâne în registrul admin, cu lucrările asociate și valoarea lor originală.

`ServiceBillingService::recordInvoice()` revalidează întreaga selecție în tranzacție, cu blocare de rânduri. Respinge selecții goale, clienți diferiți, fișe deja facturate, inexistente, anulate sau cu altă metodă de plată. Numărul facturii este obligatoriu și unic, suma este pozitivă cu maximum două zecimale, iar data nu poate fi în viitor. Un eșec nu facturează parțial selecția. Operația reverifică activarea modulului.

**Marcarea facturată nu înregistrează încasarea facturii** și nu schimbă `payment_status` în `paid`. Funcția nu emite PDF fiscal, nu transmite e-Factura și nu integrează un furnizor de facturare. Corecțiile/stornarea și evidența plăților ulterioare ale facturilor nu sunt implementate.

## Rapoarte

Rapoartele existente oferă totaluri, metode de plată, servicii vândute, performanță operator și istoric. Raportul zilnic al operatorului este disponibil pentru tipărire și PDF.

`ServiceReportService` este sursa comună pentru rapoartele admin, imprimare/PDF, raportul operatorului și API. Lucrările finalizate sunt încadrate după `completed_at` (cu fallback `created_at` pentru date istorice incomplete), iar fișele deschise/anulate după `created_at`. Intervalele folosesc `SERVICE_REPORT_TIMEZONE`, implicit `Europe/Bucharest`, și sunt convertite în fusul aplicației pentru interogare.

Valoarea lucrărilor finalizate este separată de încasări. Încasările includ numai numerar/card/mixt cu `paid`; protocolul și contul client sunt separate. Mixtul nu are defalcare internă cash/card deoarece schema existentă nu stochează acele componente. Nefacturatele includ atât perioada selectată, cât și totalul curent din toate perioadele, grupat pe client. O factură cu mai multe fișe este numărată o singură dată, după data emiterii.

API-ul zilnic/lunar și configurarea n8n sunt descrise în [21-api-rapoarte-n8n.md](21-api-rapoarte-n8n.md). Trimiterea efectivă a e-mailurilor și programarea workflow-urilor n8n rămân de configurat; nu sunt active automat după instalarea codului.

Serviciile prestate sunt grupate după denumire și unitatea salvată pe fișă. Aceeași denumire cu unități diferite produce rânduri distincte, pentru a păstra cantitățile în m² și bucăți. Unitatea apare în agregatele API, raportul admin și printurile admin/operator. Detaliile unei fișe din raportul admin afișează `line_total` și totalul salvate, cu aceeași rotunjire ca în fișă.

## Verificare automată

Verificat local cu PHP 8.4, folosind SQLite în memorie și întregul istoric real de migrări. Datele configurate în `.env` nu sunt folosite sau resetate de testele modulului.

```powershell
php -d extension=pdo_sqlite -d extension=intl vendor/bin/phpunit tests/Feature/ServiceAccountBillingTest.php tests/Feature/ServiceReportApiTest.php tests/Feature/ServiceAccountAdminTest.php
node --test tests/Frontend/service-desk.test.cjs
```

Rezultatul implementării inițiale: **28 teste PHP, 182 aserțiuni și 3 teste JavaScript trecute**. Sunt acoperite finalizarea pe cont, validarea clienților, facturarea individuală/colectivă în Livewire, refuzul dublării sau amestecării clienților, drepturile de acces, protejarea evidenței, rutele și ecranele admin/operator, totalurile API, limitele de zi din România și retry-ul terminalului fără duplicarea fișei. Verificarea de sintaxă PHP a trecut pentru cele 33 de fișiere PHP noi/modificate.

Corecția pentru refresh adaugă patru teste JavaScript: reluare și salvare pe același ID, izolare pe tab și refuzul referințelor vechi, păstrarea referinței la erori și eliminarea ei numai după succes, respectiv blocarea schimbării fișei în timpul unei cereri. **Toate cele 7 teste JavaScript trec.**

Extinderea din noiembrie este verificată prin 12 teste JavaScript pentru terminal și 32 teste PHP cu 238 aserțiuni pentru catalog, facturare, rapoarte și administrarea fișelor. Sunt incluse introducerea `2,50`/`2.50` m², calculul `2,50 × 5 = 12,50`, rotunjirea `2,50 × 4,01 = 10,03`, respingerea valorilor invalide și a depășirilor cu păstrarea fișei, reexecutarea migrării peste un catalog personalizat, snapshot-urile istorice și agregarea după unitate. Testele terminalului acoperă și sincronizarea tarifului confirmat de server, păstrarea coșului la erori și blocarea editării în timpul salvării. Randarea terminalului și a rapoartelor este verificată prin HTTP/Blade. Configurarea API-ului are verificări suplimentare documentate în documentul 21.

Nu a fost efectuat un test concurent pe MySQL/MariaDB și nu a fost actualizată baza de date a clienților. Blocarea de rânduri este implementată în tranzacții pentru mediul de producție; testele SQLite verifică rezultatele și validările, fără să reproducă blocările MySQL.

## Istoric modificări

- **2026-11-11** — adăugate cele nouă tarife cerute, subcategoria Covoare și introducerea suprafeței cu virgulă sau punct. Documentate migrarea aditivă, rotunjirea la bani, sincronizarea tarifului la salvare și agregarea rapoartelor după denumire și unitate.

- **2026-10-30** — corectată pierderea fișei active la refresh: ID păstrat în URL, reluare numai pentru fișe deschise, listă actualizată la salvare și protecție la schimbarea fișei în timpul cererilor.

- **2026-10-30** — adăugate conturile clienților, pop-up-ul de selecție, registrul de clienți, dashboardul nefacturatelor și evidența facturilor grupate pe client.
- **2026-10-30** — separate finalizarea, facturarea și încasarea; protejate fișele pe cont și selecțiile de facturare împotriva modificării/dublării.
- **2026-10-30** — unificate rapoartele de vulcanizare, corectată încadrarea după finalizare și adăugat API-ul autentificat pentru n8n.

- **2026-05-24** — create tabelele și activarea modulului Service.
- **2026-05-25** — adăugată metoda de plată Protocol.
- **2026-10-29** — documentația veche de fast-food a fost înlocuită cu starea reală a modulului de vulcanizare.
- **2026-10-29** — adăugate categorii/subcategorii, catalogul clientului, snapshot-ul unității și interfața simplificată.
- **2026-10-29** — prețurile sunt recitite din baza de date, accesul este limitat rolului `service`, iar PIN-urile nu mai sunt jurnalizate.
