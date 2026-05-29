<x-filament-panels::page>
    <div x-data="userGuideData()" class="space-y-6">
        
        <!-- HEADER & SEARCH BAR -->
        <div class="bg-amber-50/50 dark:bg-gray-800/40 border border-amber-200/60 dark:border-gray-700/50 rounded-3xl p-8 shadow-sm relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-10 dark:opacity-5 translate-x-10 -translate-y-10 text-amber-600">
                <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <div class="relative z-10 max-w-2xl">
                <h1 class="text-3xl font-black mb-2 tracking-tight text-gray-900 dark:text-white">Ghid de Utilizare Restaurant OS</h1>
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-6 leading-relaxed">
                    Bine ai venit în centrul de asistență integrat. Aici găsești explicații pas-cu-pas, fluxuri operaționale și sfaturi practice pentru gestionarea restaurantului tău. Folosește căutarea rapidă de mai jos sau filtrează pe categorii.
                </p>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" 
                           x-model="search" 
                           placeholder="Caută o funcție, un modul sau un termen (ex: inventar, ospătar, rețete)..." 
                           class="w-full bg-white dark:bg-gray-900 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-2xl pl-12 pr-10 py-4 text-sm focus:ring-4 focus:ring-amber-300 focus:border-amber-500 placeholder-gray-400 shadow-sm">
                    <button x-show="search !== ''" 
                            @click="search = ''" 
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600"
                            x-cloak>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- CATEGORIES FILTER TABS -->
        <div class="flex flex-wrap gap-2 pb-2">
            <template x-for="tab in tabs" :key="tab.id">
                <button @click="activeCategory = tab.id; openSection = null"
                        class="px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all border-2 flex items-center gap-2"
                        :class="activeCategory === tab.id 
                            ? 'bg-amber-600 border-amber-600 text-white shadow-md' 
                            : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-500 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'">
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </div>

        <!-- SEARCH RESULTS INFO -->
        <div x-show="search !== ''" class="text-sm font-medium text-gray-500 italic pl-1" x-cloak>
            Rezultate căutare pentru „<span x-text="search" class="text-amber-600 font-bold"></span>”: <span x-text="filteredSections.length"></span> ghiduri găsite.
        </div>

        <!-- NO SECTIONS AT ALL FALLBACK -->
        <template x-if="!sections || sections.length === 0">
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center">
                <div class="w-16 h-16 bg-orange-50 dark:bg-gray-800 text-orange-500 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-black text-gray-800 dark:text-white mb-1">Ghidul este gol</h3>
                <p class="text-gray-400 text-sm max-w-sm">Nu există ghiduri configurate.</p>
            </div>
        </template>

        <!-- NO SEARCH RESULTS PLACEHOLDER -->
        <div x-show="filteredSections.length === 0 && sections && sections.length > 0" 
             class="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center"
             x-cloak>
            <div class="w-16 h-16 bg-orange-50 dark:bg-gray-800 text-orange-500 rounded-2xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-black text-gray-800 dark:text-white mb-1">Nu am găsit rezultate</h3>
            <p class="text-gray-400 text-sm max-w-sm">
                Nu există niciun ghid care să corespundă termenilor căutați. Încearcă alte cuvinte cheie sau selectează altă categorie de filtrare.
            </p>
        </div>

        <!-- INTEGRATED USER GUIDE ACCORDION/CARDS GRID -->
        <div class="grid grid-cols-1 gap-4" x-show="filteredSections.length > 0">
            <template x-for="section in filteredSections" :key="section.id">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm transition-all overflow-hidden"
                     :class="openSection === section.id ? 'ring-2 ring-amber-500 border-transparent shadow-md' : 'hover:shadow-md hover:border-gray-200 dark:hover:border-gray-700'">
                    
                    <!-- Card Header (Click to toggle) -->
                    <button @click="openSection = (openSection === section.id ? null : section.id)" 
                            class="w-full text-left p-6 flex justify-between items-center gap-4 transition-colors"
                            :class="openSection === section.id ? 'bg-amber-50/30 dark:bg-gray-800/20' : 'bg-white dark:bg-gray-900'">
                        <div class="flex-grow">
                            <span class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-500" x-text="tabs.find(t => t.id === section.category)?.label"></span>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white mt-1" x-text="section.title"></h3>
                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-1 leading-relaxed" x-text="section.description"></p>
                        </div>
                        <div class="shrink-0 w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 dark:text-gray-400 transition-transform duration-300"
                             :class="openSection === section.id ? 'rotate-180 bg-amber-500 text-white' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>

                    <!-- Card Body (Steps, Warnings) -->
                    <div x-show="openSection === section.id" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 max-h-0"
                         x-transition:enter-end="opacity-100 max-h-[1000px]"
                         class="border-t border-gray-100 dark:border-gray-800 p-6 space-y-6"
                         x-cloak>
                        
                        <!-- Steps list -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Instrucțiuni Pas cu Pas:</h4>
                            <div class="grid grid-cols-1 gap-3">
                                <template x-for="(step, sIndex) in section.steps" :key="sIndex">
                                    <div class="flex items-start gap-3 bg-gray-50/50 dark:bg-gray-800/10 p-4 rounded-xl border border-gray-100 dark:border-gray-800/80">
                                        <span class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 flex items-center justify-center text-xs font-black shrink-0" x-text="sIndex + 1"></span>
                                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed" x-html="step"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Important warning block -->
                        <template x-if="section.warning">
                            <div class="bg-orange-50 dark:bg-orange-950/20 border-l-4 border-orange-500 p-4 rounded-r-xl">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <h5 class="text-xs font-bold uppercase tracking-wider text-orange-800 dark:text-orange-300">Informație Importantă:</h5>
                                        <p class="text-orange-700 dark:text-orange-400 text-xs mt-1 leading-relaxed" x-html="section.warning"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- SCRIPT DATA FUNCTION FOR ALPINE.JS (AVOIDS INLINE HTML PARSING LIMITS) -->
    <script>
        function userGuideData() {
            return {
                search: '',
                activeCategory: 'all',
                openSection: null,
                tabs: [
                    { id: 'all', label: 'Toate Ghidurile', icon: 'book-open' },
                    { id: 'menu', label: 'Meniu & Produse', icon: 'cake' },
                    { id: 'orders', label: 'Mese & Comenzi', icon: 'shopping-cart' },
                    { id: 'staff', label: 'Personal & Ecran Bucătărie', icon: 'users' },
                    { id: 'inventory', label: 'Inventar & Stocuri', icon: 'archive-box' },
                    { id: 'settings', label: 'Setări & Public', icon: 'cog' }
                ],
                sections: [
                    {
                        id: 'dashboard',
                        category: 'menu',
                        title: '1. Dashboard & Analiză Statistici',
                        description: 'Cum să interpretezi datele financiare, graficele de vânzări și performanța restaurantului în timp real.',
                        tags: ['dashboard', 'statistici', 'grafice', 'vanzari', 'kpi', 'incasari'],
                        steps: [
                            '<strong>Indicatorii Cheie (KPIs):</strong> În partea de sus a ecranului principal poți vizualiza: Total Încasări (Cash/Card), Număr de Comenzi active, Valoarea Medie a Bonului și ocuparea meselor în timp real.',
                            '<strong>Graficul de Vânzări:</strong> Afișează evoluția încasărilor pe zile sau ore. Poți schimba perioada (Azi, Ultimele 7 zile, Luna aceasta) din colțul dreapta-sus al ecranului.',
                            '<strong>Top Produse Vândute:</strong> Un clasament al celor mai populare preparate din meniu, excelent pentru a vedea ce preferă clienții și a ajusta stocurile de ingrediente.'
                        ],
                        warning: 'Datele din dashboard se actualizează automat pe baza comenzilor marcate ca „Plătit” (Paid) de către ospătari sau din Service Module.'
                    },
                    {
                        id: 'menu-products',
                        category: 'menu',
                        title: '2. Produse, Categorii & Alergeni',
                        description: 'Adăugarea preparatelor în meniu, gruparea lor pe categorii elegante și asocierea alergenilor obligatorii.',
                        tags: ['produse', 'categorii', 'alergeni', 'pret', 'imagine', 'meniu'],
                        steps: [
                            '<strong>Categorii Meniu:</strong> Mergi la <em>Meniu > Categorii</em>. Adaugă categorii noi (ex: „Burgeri”, „Băuturi calde”). Poți seta ordinea de afișare și destinația implicită de preparare (Kitchen/Bucătărie sau Bar).',
                            '<strong>Adăugarea Alergenilor:</strong> Înainte de a crea produse, adaugă alergenii la <em>Meniu > Alergeni</em> conform normativelor legale (ex: Gluten, Lactoză, Arahide).',
                            '<strong>Crearea unui Produs:</strong> La <em>Meniu > Produse</em>, apasă „Produs Nou”. Completează: Nume, Descriere, Preț, Imagine (pentru meniul digital public) și selectează alergenii corespunzători.',
                            '<strong>Variante de Produs:</strong> Dacă un produs are mai multe mărimi sau opțiuni (ex: Pizza Mică / Pizza Mare), poți defini variații cu prețuri diferite în secțiunea „Variations” a produsului.'
                        ],
                        warning: 'Asigură-te că activezi bifa „Este disponibil” pentru ca produsul să poată fi comandat de către ospătari sau clienți.'
                    },
                    {
                        id: 'ingredients',
                        category: 'menu',
                        title: '3. Ingrediente & Import CSV',
                        description: 'Gestiunea ingredientelor brute, asocierea lor cu fișierul de import rapid și definirea rețetelor.',
                        tags: ['ingrediente', 'csv', 'import', 'retete', 'furnizori', 'materie prima'],
                        steps: [
                            '<strong>Definirea Ingredientelor:</strong> Mergi la <em>Meniu > Ingrediente</em>. Creează elementele de bază (ex: „Chiflă burger”, „Carne de vită”, „Cartofi congelati”). Setează unitatea de măsură potrivită (buc, kg, g, l, ml).',
                            '<strong>Import CSV Rapid:</strong> Dacă ai o listă mare de ingrediente de la furnizori, folosește funcția „Import CSV” disponibilă în ecranul de ingrediente. Descarcă modelul de fișier, completează denumirile și unitățile de măsură, apoi încarcă fișierul pentru import instant.',
                            '<strong>Legătura cu Stocul:</strong> Pentru fiecare ingredient pe care dorești să îl urmărești automat pe stoc, bifează „Urmărire stoc activă” și asociază-l cu un <em>Produs de Inventar</em> (detalii în secțiunea Gestiune Stoc).'
                        ],
                        warning: 'Unitatea de măsură definită la ingredient trebuie să corespundă cu unitatea de măsură din Inventar pentru o conversie corectă.'
                    },
                    {
                        id: 'tables-map',
                        category: 'orders',
                        title: '4. Mese, Zone & Harta Meselor',
                        description: 'Desenarea hărții restaurantului pe tablete sau desktop pentru urmărirea stării de ocupare.',
                        tags: ['mese', 'zone', 'harta meselor', 'pozitie', 'seats', 'locuri'],
                        steps: [
                            '<strong>Crearea Zonelor:</strong> Mergi la <em>Mese > Zone</em>. Zonele reprezintă secțiunile restaurantului (ex: „Salon Principal”, „Terasă”, „Takeaway”).',
                            '<strong>Adăugarea Meselor:</strong> La <em>Mese > Mese</em>, creează mesele specificând numărul de locuri (seats) și forma grafică (rotundă sau pătrată/dreptunghiulară).',
                            '<strong>Configurarea Hărții:</strong> Accesează pagina <em>Harta Meselor</em>. Aici poți trage (drag & drop) mesele în ecran pentru a recrea layout-ul fizic al restaurantului. Setează dimensiunea lor grafică exact așa cum dorești ca ospătarii să le vadă.'
                        ],
                        warning: 'Mesele de la pachet/takeaway (ex: P11-P15) trebuie configurate în aceeași zonă, dar cu formă pătrată sau denumire specifică pentru a fi ușor recunoscute de personal.'
                    },
                    {
                        id: 'staff-members',
                        category: 'staff',
                        title: '5. Personal, Roluri & Login cu PIN',
                        description: 'Gestiunea ospătarilor, bucătarilor și a codurilor unice de acces rapid în aplicația mobilă.',
                        tags: ['staff', 'personal', 'ospatari', 'bucatari', 'pin', 'login', 'acces'],
                        steps: [
                            '<strong>Adăugarea Personalului:</strong> Mergi la <em>Personal > Membri Staff</em>. Adaugă numele angajatului și selectează rolul corespunzător (Waiter/Ospătar, Kitchen Staff/Bucătar, Bar Staff/Barman, Manager).',
                            '<strong>Codul PIN Securizat:</strong> Generează sau completează un cod PIN numeric unic de 4 cifre pentru fiecare angajat. Acesta va fi folosit de ospătar pentru a intra rapid în aplicația de marcat fără parolă lungă.',
                            '<strong>Activarea Contului:</strong> Asigură-te că starea este setată pe „Activ”. Personalul inactiv nu se va putea autentifica pe terminale.'
                        ],
                        warning: 'Păstrează codurile PIN unice. Sistemul va returna eroare dacă încerci să asociezi același cod PIN la doi angajați diferiți.'
                    },
                    {
                        id: 'waiter-app',
                        category: 'orders',
                        title: '6. Interfața Ospătarului (Waiter App - `/waiter`)',
                        description: 'Cum preia ospătarul comanda de la masă, trimiterea la bucătărie și încasarea cash sau card.',
                        tags: ['waiter', 'ospatar', 'masa', 'nota plata', 'split', 'bon', 'incasare'],
                        steps: [
                            '<strong>Autentificare rapidă:</strong> Ospătarul accesează link-ul <code>/waiter</code> pe tabletă sau telefon și introduce codul său PIN unic de 4 cifre.',
                            '<strong>Selectare Masă & Plasare Comandă:</strong> Harta interactivă arată mesele libere (albastre) și cele ocupate (roșii). Apasă pe o masă, alege produsele dorite, selectează variațiile sau adaugă note speciale (ex: „fără ceapă”), apoi trimite comanda.',
                            '<strong>Adăugare produse ulterioare:</strong> Dacă clienții mai comandă ceva, ospătarul redeschide masa și adaugă preparate noi. Sistemul va trimite la bucătărie doar noile produse adăugate, evidențiate distinct.',
                            '<strong>Notă de plată & Split/Plată Parțială:</strong> Ospătarul poate tipări nota de plată direct din tabletă. În caz de split, poate selecta doar anumite produse pentru a le încasa parțial (cash/card), restul rămânând active pe masă.',
                            '<strong>Încasare Finală:</strong> Când masa plătește integral, se alege metoda de plată (Cash/Card) și se emite bonul, masa redevenind liberă (albastră).'
                        ],
                        warning: 'Ospătarul poate emite și factură fiscală direct de pe tabletă bifând opțiunea „Factură Fiscală” și completând datele CUI/Companie primite de la client.'
                    },
                    {
                        id: 'kitchen-display',
                        category: 'staff',
                        title: '7. Ecranele Digitale din Bucătărie & Bar (`/kitchen` / `/bar`)',
                        description: 'Afișajul digital (KDS) din secțiile de preparare cu notificări sonore automate la comenzi noi.',
                        tags: ['kitchen', 'bar', 'preparare', 'ecran bucatarie', 'sound', 'notificare audio', 'gata'],
                        steps: [
                            '<strong>Accesarea Monitorului:</strong> Bucătarii accesează <code>/kitchen</code>, iar barmanii accesează <code>/bar</code> pe un ecran sau o tabletă montată în secție.',
                            '<strong>Activarea Sunetului:</strong> La prima deschidere, este obligatoriu să apăsați butonul mare <strong>START KDS 🚀</strong> pentru a permite browserului să redea alerte sonore.',
                            '<strong>Fluxul de preparare (Kanban):</strong> Comenzile sosesc instant pe coloana „Nou (Pending)”. Bucătarul apasă „Start Cooking” pentru a muta comanda în starea „În preparare”. Când preparatul este gata, apasă „Mark Ready” pentru a muta comanda în coloana „Gata de servire”.',
                            '<strong>Notificare Ospătar:</strong> Imediat ce o comandă este marcată ca „Ready” la bucătărie, ospătarul care a preluat comanda primește o alertă sonoră și vizuală pe terminalul său mobil pentru a merge să ridice farfuria.'
                        ],
                        warning: 'Notificările audio funcționează complet automat, declanșându-se exact o singură dată pentru fiecare produs nou trimis către secție, indiferent de masa de pe care provine (comenzi normale sau takeaway).'
                    },
                    {
                        id: 'stock-config',
                        category: 'inventory',
                        title: '8. Configurare Stoc & Retete Ingrediente',
                        description: 'Legătura dintre produsele din meniul fizic, ingredientele urmărite și produsele din depozitul de Inventar.',
                        tags: ['reteta', 'stoc', 'materie prima', 'conversie', 'cantitate', 'asociere'],
                        steps: [
                            '<strong>Crearea Produselor de Inventar:</strong> În secțiunea <em>Inventar > Produse Inventar</em>, creați materiile prime cumpărate de la furnizori (ex: „Cartofi sac 10kg” cu unitatea kg, „Chifle burger” la buc, „Ulei floarea soarelui” la litri).',
                            '<strong>Asocierea Ingredientului cu Inventarul:</strong> Mergeți la <em>Meniu > Ingrediente</em>. Editați un ingredient (ex: „Cartofi prăjiți”), bifați „Urmărire stoc activă”, alegeți Produsul de Inventar creat la pasul 1 („Cartofi sac 10kg”) și introduceți conversia per porție (ex: 0.200 kg).',
                            '<strong>Configurarea Rețetei pe Produs:</strong> Mergeți la <em>Meniu > Produse</em> și editați produsul final (ex: „WPA Burger”). În tabul <strong>Recipe & Ingredients</strong>, adăugați ingredientele componente și specificați cantitatea utilizată pentru o porție (ex: Chiflă x 1, Cheddar x 1, Cartofi prăjiți x 1).'
                        ],
                        warning: 'Dacă un produs din meniu nu are rețeta completată sau ingredientele nu au urmărirea de stoc activată, vânzarea acelui produs nu va scădea nimic din stoc!'
                    },
                    {
                        id: 'stock-movements',
                        category: 'inventory',
                        title: '9. Aprovizionări (Intrări) & Ajustări de Stoc',
                        description: 'Înregistrarea facturilor de achiziție, a pierderilor/deșeurilor și modificarea stocului real.',
                        tags: ['aprovizionare', 'intrare marfa', 'ajustare stoc', 'pierderi', 'stoc faptic', 'factura'],
                        steps: [
                            '<strong>Intrare de Stoc (Aprovizionare):</strong> Când sosește marfa de la furnizor, mergeți la <em>Inventar > Produse Inventar</em>, apăsați acțiunea rapidă <strong>„Intrare Stoc”</strong> din dreptul produsului. Introduceți cantitatea recepționată și detalii (ex: număr factură, preț, furnizor).',
                            '<strong>Ajustare Stoc (Inventariere):</strong> Dacă stocul scriptic din calculator nu corespunde cu cel faptic din depozit, folosiți acțiunea rapidă <strong>„Ajustare Stoc”</strong>. Introduceți stocul real găsit la numărătoare, iar sistemul va genera automat o mișcare de corecție (pozitivă sau negativă).',
                            '<strong>Înregistrare Pierderi / Deșeuri:</strong> La ajustări sau direct din interfață, puteți menționa motivul „Waste” (Deșeuri/Alterat) pentru produsele care au expirat sau s-au deteriorat.'
                        ],
                        warning: 'Fiecare intrare sau ajustare generează o înregistrare permanentă și nemodificabilă în istoricul de „Mișcări Stoc” pentru transparență totală.'
                    },
                    {
                        id: 'inventory-flow',
                        category: 'inventory',
                        title: '10. Fluxul Complet: Comandă → Paid → Scădere Automată Stoc',
                        description: 'Cum funcționează sistemul automat în timp real și ce se întâmplă în baza de date la fiecare pas.',
                        tags: ['flux', 'deducere stoc', 'tranzactie', 'paid', 'automat', 'idempotenta'],
                        steps: [
                            '<strong>Pasul 1 - Comanda Activa:</strong> Când ospătarul introduce o comandă și o trimite la bucătărie, stocul <strong>NU</strong> este modificat încă. Comanda poate fi modificată, produsele șterse sau mutate fără a afecta gestiunea.',
                            '<strong>Pasul 2 - Plata Comenzii (Status devine „Paid”):</strong> Imediat ce ospătarul confirmă plata bonului în Waiter App (sau din admin), sistemul execută o tranzacție securizată în baza de date.',
                            '<strong>Pasul 3 - Calculul și Deducerea Stocului:</strong> Sistemul caută rețeta fiecărui produs vândut. Pentru fiecare ingredient urmărit, calculează: <code>Cantitate Vândută × Cantitate Utilizată în Rețetă × Coeficient Conversie</code>.',
                            '<strong>Pasul 4 - Actualizare și Istoric:</strong> Stocul curent al materiei prime scade automat. Se creează o mișcare de stoc de tip „sale” asociată comenzii. În comandă se marchează câmpul <code>stock_deducted_at</code> cu data curentă (astfel, chiar dacă se editează ulterior comanda, stocul nu va fi scăzut de două ori - logica idempotentă).'
                        ],
                        warning: 'Dacă opțiunea „Prevenire stoc negativ” este activată în Setări, sistemul nu va permite scăderea stocului sub 0, blocând tranzacția sau scăzând doar până la 0 și emițând o alertă în log.'
                    },
                    {
                        id: 'inventory-reports',
                        category: 'inventory',
                        title: '11. Raport Inventar, Snapshots & Print A4',
                        description: 'Cum se realizează inventarul la sfârșit de lună, vizualizarea diferențelor și tipărirea fișei fizice.',
                        tags: ['inventar lunar', 'print a4', 'pdf', 'raport', 'snapshot', 'diferente'],
                        steps: [
                            '<strong>Vizualizare Raport Curent:</strong> Accesați <em>Inventar > Raport Inventar</em>. Aici vedeți stocul scriptic curent, produsele cu stoc critic (sub limita minimă) și statistici de consum.',
                            '<strong>Tipărire Fișă de Inventariere:</strong> Apăsați butonul <strong>„Printează Fișă A4”</strong>. Se va deschide o pagină curată, optimizată special pentru imprimantă, cu coloane goale pentru Stoc Fizic, Diferențe și Semnături, perfectă pentru numărătoarea manuală în depozit.',
                            '<strong>Crearea unui Snapshot de Inventar (Inventar Lunar):</strong> Mergeți la <em>Inventar > Inventare Lunare</em>. Apăsați „Generare Snapshot Nou”. Sistemul va salva stocul scriptic din acea secundă ca referință istorică permanentă.',
                            '<strong>Înregistrarea Diferențelor:</strong> După ce ați finalizat numărătoarea fizică, editați snapshot-ul creat și introduceți stocurile reale înregistrate. Sistemul va calcula diferențele, procentele de pierdere și va ajusta automat stocurile curente în depozit.'
                        ],
                        warning: 'Generați întotdeauna snapshot-ul de inventar la sfârșit de lună după închiderea ultimei comenzi și înainte de a înregistra recepțiile de marfă pentru luna următoare.'
                    },
                    {
                        id: 'service-module',
                        category: 'settings',
                        title: '12. Modulul de Autodeservire (Service Module - `/service`)',
                        description: 'Interfața de tip Fast-Food Touchscreen pentru comenzi rapide la tejghea și încasare pe loc.',
                        tags: ['service module', 'fast food', 'tejghea', 'touchscreen', 'comanda rapida', 'self service'],
                        steps: [
                            '<strong>Destinație:</strong> Ideal pentru puncte de vânzare rapidă, food trucks, patiserii sau zone de tejghea unde clienții comandă, plătesc pe loc și primesc produsele.',
                            '<strong>Plasare Comandă:</strong> Interfața <code>/service</code> este optimizată pentru ecrane mari tactile. Personalul apasă pe produse în format grid, alege metode de plată (Cash/Card) și apasă „Finalizează & Plătește”.',
                            '<strong>Scădere automată instantă:</strong> Deoarece comenzile din Service Module sunt plătite direct la creare, scăderea stocurilor din inventar are loc instantaneu în fundal.'
                        ],
                        warning: 'Modulul Service poate fi activat sau dezactivat oricând din ecranul de Configurare Platformă în funcție de specificul locației dumneavoastră.'
                    },
                    {
                        id: 'public-menu',
                        category: 'settings',
                        title: '13. Meniul Public Digital & QR Code (`/menu`)',
                        description: 'Meniul public pe care clienții îl accesează de pe telefoanele lor scanând codurile QR de pe mese.',
                        tags: ['meniu public', 'qr code', 'layout', 'design', 'meniu digital', 'clienti'],
                        steps: [
                            '<strong>Acces client:</strong> Clienții scanează codul QR de pe masă și accesează linkul direct <code>/menu</code>.',
                            '<strong>Design adaptiv:</strong> Meniul digital public este complet optimizat pentru telefoane mobile, are categorii cu derulare rapidă, imagini de înaltă rezoluție, prețuri clare și afișează automat alergenii asociați fiecărui produs.',
                            '<strong>Configurare Layout-uri:</strong> Din <em>Configurare Platformă</em> puteți alege stilul vizual al meniului public digital (ex: layout Compact, Modern, Listă clasică sau Grid cu poze mari).'
                        ],
                        warning: 'Produsele ascunse în admin sau marcate ca indisponibile vor fi eliminate automat în timp real și din meniul public al clienților.'
                    },
                    {
                        id: 'platform-settings',
                        category: 'settings',
                        title: '14. Configurarea Platformei & Setări Globale',
                        description: 'Personalizarea numelui firmei, logo-ului, culorii tematice și a setărilor avansate de sistem.',
                        tags: ['setari', 'configurare', 'logo', 'moneda', 'nume companie', 'cpanel'],
                        steps: [
                            '<strong>Informații Companie:</strong> Mergi la <em>Configurare Platformă</em>. Configurează: Nume Restaurant, Monedă (implicit RON), Adresă, CUI, Date contact.',
                            '<strong>Personalizare Vizuală:</strong> Încarcă sigla restaurantului (Logo Black / Logo White) și favicon-ul care vor apărea în facturi, rapoarte, Waiter App și Meniul Public.',
                            '<strong>Setări Modul Stoc:</strong> Tot aici poți bifa „Prevenire stoc negativ” pentru a nu permite vânzări scriptice peste stocurile reale recepționate din depozit.'
                        ],
                        warning: 'După schimbarea setărilor globale, nu este necesar să goliți cache-ul manual pe cPanel; modificările se aplică instant în întreaga platformă.'
                    }
                ],
                
                get filteredSections() {
                    return this.sections.filter(section => {
                        const matchesCategory = this.activeCategory === 'all' || section.category === this.activeCategory;
                        const q = this.search.toLowerCase().trim();
                        
                        const searchableText = [
                            section.title,
                            section.description,
                            ...(section.tags || []),
                            ...(section.steps || [])
                        ].join(' ').toLowerCase();
                        
                        const matchesSearch = q === '' || searchableText.includes(q);
                        
                        return matchesCategory && matchesSearch;
                    });
                }
            };
        }
    </script>
</x-filament-panels::page>
