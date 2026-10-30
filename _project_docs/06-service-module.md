# 06. Modulul de vulcanizare și servicii auto

## Scop și stare

Modulul `Service` este implementat ca terminal simplificat pentru vulcanizare și spălătorie auto. El are catalog, fișe de lucru, încasare și rapoarte proprii și nu folosește comenzile restaurantului.

Stare la 30 octombrie 2026: **implementat în cod**, inclusiv ierarhia categorie–subcategorie, catalogul inițial primit de la client, terminalul operatorului, conturile clienților și evidența facturilor emise extern. Aplicarea migrării și configurarea API-ului pe instalările clienților sunt operații de deployment separate.

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

Poziția păstrează snapshot-ul denumirii, unității și prețului. `line_total` este recalculat la salvare, iar comanda își recalculează totalul după salvarea sau ștergerea unei poziții.

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

## Interfața operatorului

View: `resources/views/service/index.blade.php`.

Principii implementate:

- două butoane mari pentru categoriile principale;
- subcategorii mari și ușor de apăsat;
- serviciile apar ca butoane cu denumire și preț;
- apăsarea repetată mărește cantitatea;
- coșul are numai butoane mari `+` și `−`;
- numărul mașinii este vizibil, dar opțional;
- numele, telefonul și observațiile sunt ascunse sub „Detalii client (opțional)”;
- acțiunea principală este butonul verde `ÎNCASEAZĂ`;
- metodele Numerar, Card, Mixt, Protocol și **Pe cont client** sunt afișate ca butoane mari;
- „Pe cont client” deschide un al doilea pop-up cu clienții activi salvați, căutare după nume/cod fiscal/telefon și confirmare explicită; lista este pregătită în controller;
- fișele deschise sunt într-un panou separat și pot fi reluate;
- interfața este responsive pentru telefon, tabletă și desktop.

Datele și regulile sunt încărcate de controller și modele. View-ul nu interoghează baza de date și nu leagă schema de tema vizuală.

## Validare și reguli server-side

- este necesară minimum o poziție;
- serviciul trebuie să existe și să fie activ;
- cantitatea trebuie să fie numerică și pozitivă;
- prețul trimis de browser nu este acceptat;
- denumirea, unitatea și prețul sunt recitite din `service_items`;
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

## Verificare automată

Verificat local cu PHP 8.4, folosind SQLite în memorie și întregul istoric real de migrări. Datele configurate în `.env` nu sunt folosite sau resetate de testele modulului.

```powershell
php -d extension=pdo_sqlite -d extension=intl vendor/bin/phpunit tests/Feature/ServiceAccountBillingTest.php tests/Feature/ServiceReportApiTest.php tests/Feature/ServiceAccountAdminTest.php
node --test tests/Frontend/service-desk.test.cjs
```

Rezultat: **28 teste PHP, 182 aserțiuni și 3 teste JavaScript trecute**. Sunt acoperite finalizarea pe cont, validarea clienților, facturarea individuală/colectivă în Livewire, refuzul dublării sau amestecării clienților, drepturile de acces, protejarea evidenței, rutele și ecranele admin/operator, totalurile API, limitele de zi din România și retry-ul terminalului fără duplicarea fișei. Verificarea de sintaxă PHP a trecut pentru cele 33 de fișiere PHP noi/modificate.

Nu a fost efectuat un test concurent pe MySQL/MariaDB și nu a fost actualizată baza de date a clienților. Blocarea de rânduri este implementată în tranzacții pentru mediul de producție; testele SQLite verifică rezultatele și validările, fără să reproducă blocările MySQL.

## Istoric modificări

- **2026-10-30** — adăugate conturile clienților, pop-up-ul de selecție, registrul de clienți, dashboardul nefacturatelor și evidența facturilor grupate pe client.
- **2026-10-30** — separate finalizarea, facturarea și încasarea; protejate fișele pe cont și selecțiile de facturare împotriva modificării/dublării.
- **2026-10-30** — unificate rapoartele de vulcanizare, corectată încadrarea după finalizare și adăugat API-ul autentificat pentru n8n.

- **2026-05-24** — create tabelele și activarea modulului Service.
- **2026-05-25** — adăugată metoda de plată Protocol.
- **2026-10-29** — documentația veche de fast-food a fost înlocuită cu starea reală a modulului de vulcanizare.
- **2026-10-29** — adăugate categorii/subcategorii, catalogul clientului, snapshot-ul unității și interfața simplificată.
- **2026-10-29** — prețurile sunt recitite din baza de date, accesul este limitat rolului `service`, iar PIN-urile nu mai sunt jurnalizate.
