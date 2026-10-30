# Documentația tehnică `daser_rest`

Acest director descrie starea reală a aplicației, actualizată la 30 octombrie 2026. Codul aplicației este sursa de adevăr; directoarele `vendor/` și `public/js/filament/` conțin dependențe sau asset-uri publicate și nu sunt tratate drept logică proprie.

## Ordinea recomandată de lectură

1. [01-project-overview.md](01-project-overview.md) — prezentarea istorică a proiectului.
2. [02-database-architecture.md](02-database-architecture.md) — schema și relațiile bazei de date.
3. [03-modules-overview.md](03-modules-overview.md) — prezentarea modulelor.
4. [04-order-flow.md](04-order-flow.md) — fluxul comenzilor de restaurant.
5. [05-inventory-system.md](05-inventory-system.md) — stocuri și inventar.
6. [06-service-module.md](06-service-module.md) — modulul de vulcanizare, catalogul ierarhic, terminalul operatorului și rapoartele.
7. [07-public-menu.md](07-public-menu.md) — meniul și paginile publice.
8. [08-authentication.md](08-authentication.md) — autentificarea admin/staff.
9. [09-routes-reference.md](09-routes-reference.md) — referința istorică a rutelor.
10. [10-deployment.md](10-deployment.md) — instalare și deployment.
11. [11-known-issues.md](11-known-issues.md) — probleme cunoscute anterior.
12. [12-future-ideas.md](12-future-ideas.md) — idei, nu funcții implementate.
13. [13-arhitectura-reala-si-fluxuri.md](13-arhitectura-reala-si-fluxuri.md) — auditul actual al arhitecturii și fluxurilor.
14. [14-catalog-functii-backend.md](14-catalog-functii-backend.md) — localizarea și comportamentul funcțiilor PHP proprii.
15. [15-catalog-functii-frontend.md](15-catalog-functii-frontend.md) — funcțiile JavaScript/Alpine din view-uri.
16. [16-fisiere-configurare-date-si-riscuri.md](16-fisiere-configurare-date-si-riscuri.md) — configurare, migrații, teste, fișiere auxiliare și constatări.
17. [17-import-produse-csv.md](17-import-produse-csv.md) — formatul și regulile importului de produse.
18. [18-produse-bilingve-si-configurare-alergeni.md](18-produse-bilingve-si-configurare-alergeni.md) — traduceri produse și activarea/dezactivarea alergenilor.
19. [19-meniu-multilingv-si-import-complet.md](19-meniu-multilingv-si-import-complet.md) — RO/EN/DE/IT/FR și formatul unic pentru mâncare/băuturi.
20. [20-galerii-evenimente-si-recenzii-google.md](20-galerii-evenimente-si-recenzii-google.md) — albume foto, evenimente și secțiunea Google fără API.
21. [21-api-rapoarte-n8n.md](21-api-rapoarte-n8n.md) — API-ul autentificat pentru rapoarte zilnice/lunare de vulcanizare și configurarea notificărilor n8n.

## Convenții de stare

- **Implementat**: există cod executabil și o cale de utilizare.
- **Pregătit/parțial**: există structură, dar integrarea sau verificarea este incompletă.
- **Risc/defect**: comportament observat în cod care trebuie evaluat înaintea extinderii.
- Căile și numerele de linie se raportează la starea analizată; după editări, numerele pot migra.

## Domeniul inventarului de funcții

Catalogul include clasele și metodele proprii din `app/`, rutele din `routes/`, seederele, factory-urile și funcțiile JavaScript/Alpine din `resources/views/`. Metodele standard `up()`/`down()` ale migrațiilor sunt inventariate pe fișier în documentul 16. Callback-urile anonime din configurările Filament sunt explicate în cadrul metodei publice care le conține. Nu sunt reproduse funcțiile framework-ului din `vendor/` și nici bundle-urile Filament publicate în `public/`.

## Istoric modificări

- **2026-10-30** — documentate conturile clienților, evidența facturilor în admin și API-ul de rapoarte pentru n8n; notificările și deployment-ul rămân de configurat pe instalări.

- **2026-08-08** — creat indexul documentației și adăugată seria de audit 13–16.
- **2026-08-08** — adăugată documentația importului CSV pentru produse.
- **2026-08-08** — documentate produsele bilingve și configurarea alergenilor.
- **2026-08-10** — documentat meniul multilingv și importul complet din CSV.
- **2026-08-10** — documentate galeriile/evenimentele și recenziile Google simplificate.
- **2026-10-29** — rescrisă documentația modulului Service pentru fluxul real de vulcanizare și catalogul clientului.
- **2026-10-29** — documentată actualizarea completă din Git direct din panoul admin și condițiile cPanel necesare.
