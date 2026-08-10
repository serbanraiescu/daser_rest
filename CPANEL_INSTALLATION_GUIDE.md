# Ghid de Instalare Inițială pe cPanel (Zero Terminal)

Acest ghid explică pas cu pas cum se instalează platforma **Daser Restaurant OS** pentru un client nou, complet din interfața cPanel și browser, fără a fi nevoie de acces SSH sau linie de comandă.

---

## Pasul 1: Clonarea proiectului în cPanel
1. Accesați cPanel, secțiunea **Git Version Control**.
2. Adăugați o nouă clonare indicând repository-ul Git (ramura `main`).
3. Puneți fișierele de bază în folderul principal (de ex. `/home/username/daser_rest`).
4. Asigurați-vă că domeniul sau subdomeniul clientului este configurat în cPanel să aibă ca rădăcină (Document Root) folderul `public_html`.

---

## Pasul 2: Configurarea fișierului `.env`
1. În **cPanel File Manager**, navigați în directorul principal al aplicației (ex. `/home/username/daser_rest`).
2. Copiați fișierul `.env.example` sub numele de `.env` (dacă nu există deja).
3. Modificați următoarele valori cheie:
   * **Baza de date**:
     ```env
     DB_DATABASE=nume_baza_date
     DB_USERNAME=utilizator_baza_date
     DB_PASSWORD=parola_baza_date
     ```
   * **Adresa URL**:
     ```env
     APP_URL=https://domeniu-client.ro
     ```
   * **Token-ul de Securitate Deploy**:
     ```env
     DEPLOY_TOKEN=parola_secreta_aici
     ```
     *(Alegeți un token secret unic pe care îl veți folosi în browser).*

---

## Pasul 3: Generarea Bazei de Date (Schema completă)
Pentru a genera schema bazei de date completă pe o bază de date curată, deschideți browserul și accesați:
👉 `https://domeniu-client.ro/__deploy/fresh?token=parola_secreta_aici`

*Acest endpoint va rula automat `migrate:fresh --force` pentru a curăța complet și construi de la zero toate tabelele necesare.*

---

## Pasul 4: Inițializarea Setărilor și a Alergenilor
După ce tabelele au fost create, accesați:
👉 `https://domeniu-client.ro/__deploy/run?token=parola_secreta_aici`

*Acest pas va:*
1. Genera folderele de stocare media (`storage/products`, `settings`, `gallery` etc.) direct în `public_html` cu permisiuni de scriere `0777` (rezolvând automat erorile de symlink din cPanel).
2. Rula seeders pentru alergeni (`AllergenSeeder`).
3. Șterge și optimiza cache-ul Laravel pentru viteză maximă.

---

## Actualizarea unei instalări existente

După actualizarea codului din cPanel Git Version Control, migrările noi și curățarea cache-ului se rulează din browser:

`https://domeniu-client.ro/__deploy/migrate?token=parola_secreta_aici`

Acest endpoint rulează numai `migrate --force` și `optimize:clear`. Nu șterge datele existente. Pentru actualizări normale nu utilizați endpoint-ul `fresh`, deoarece acesta recreează baza de date.

---

## Pasul 5: Crearea Contului de Administrator
Pentru a crea instantaneu contul de Super Administrator, accesați în browser:
👉 `https://domeniu-client.ro/__deploy/admin`

*Sistemul va afișa mesajul de confirmare:*
> `"Admin user created successfully! You can now log in at /admin"`

* **Credentiale implicite de logare:**
  * **Email:** `app@abistro.ro`
  * **Parolă:** `ParolaApp2026?Das`

---

## Pasul 6: Configurația Finală în Admin Panel
1. Accesați panoul de administrare la adresa: `https://domeniu-client.ro/admin`
2. Autentificați-vă cu datele de mai sus.
3. **IMPORTANT**: Mergeți la setările profilului dvs. (colțul dreapta-sus sau meniul de profil) și **schimbați imediat email-ul și parola** cu cele ale clientului.
4. Mergeți la secțiunea **Setări Platformă** pentru a personaliza:
   * Numele restaurantului (se va schimba automat peste tot)
   * Logo-ul restaurantului
   * Favicon-ul specific clientului
   * Datele fiscale (CIF/VAT, adresă, monedă)
