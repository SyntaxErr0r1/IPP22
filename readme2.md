Implementační dokumentace k 2. úloze do IPP 2021/2022  
Jméno a příjmení: Juraj Dedič  
Login: xdedic07

## interpret.py

Fungovanie interpret.py je rozdelené na viacero krokov.

Načítanie parametrov je riešené bez použitia knižníc s vínimkou regexov.
Po načítaní vstupných parametrov príkazového riadku sa načíta vstupný súbor.
Tento vstupný súbor je následne validovaný a parsovaný.
Pri parsovaní súboru sa objekty jednotlivých inštrukcíí spolu s ich argumentami ukladajú do poľa.

Tieto inštrukcie sú následne zoradené podľa atribútu order. Taktiež sú do osobitného poľa uložené návestia `LABEL`, ktoré sú reprezentované objektom obsahujúcim názov a index tohoto návestia.

Ďalej nasleduje cyklus ktorý prejde pole s inštrukciami a začne vykonávať potrebné operácie. Pre každú inštrukciu existuje funkcia s názvom `INSTRUCTION_action`, kde INSTRUCTION je opcode danej inštrukcie. Názvy týchto funkcii sú pomocou dictionary `set` namapované na príslušné reťazce operačných kódov. 

Každej takejto funkcii vykonávajúcej operácie je poskytnutý objekt inštrukcie obsahujúci objekty argumentov.


### Objektová reprezentácia
Hlavný Singleton objekt je `storage` triedy `DataStorage`, ktorý obsahuje rámce, zásobník premených, zásobník volaní, polia s programom (`program`) a návestiami. Okrem toho uchováva aktuálnu pozíciu v kóde (premenná `program_counter`) a počet prečítaných riadkov zo vstupu (pri použití --input). 

DataStorage ďalej implementuje metódy pre prácu s premennými a rámcami.
Snaha bola navrhnúť túto triedu tak, aby bolo možné pracovať pri vykonávaní jednotlivých inštrukcií čo najjednoduchšie. Preto je implementovaná napríklad metóda `assign_variable`, ktorá priradí hodnotu a typ premennej podľa názvu (tj. zariadi potrebné úkony pre priradenie do správneho rámca).
Z mnohých ďalších metód ktoré táto trieda obsahuje je vhodné spomenúť napríklad `get_variable`, `create_variable` ktoré sú často využívané.

Metóda ktorá nieje súčasťou DataStorage (pretože nie nutne berie dáta z neho) ale je dôležitá pre zjednotenie práce s hodnotami je `get_symbol`, ktorá vráti objekt typu `Variable` bez ohľadu na to či je vstupný argument premenná uložená na zásobníku, alebo hodnota.

Trieda `Variable` ukladá názov, typ a hodnotu premennej a pomocné metódy.
Dôležitá je najmä `type_adjust`, ktorá upraví hodnotu podľa typu premennej, napríklad konverzia na int, alebo nahradenie kódov v reťazci ich symbolmi. Objekty tejto triedy sú uložené v zásobníku, rámcoch a všeobecne využívané pri vykonávaní inštrukcií.

Trieda `Instruction` obsahuje arguementy order, opcode a index (potrebný pri `call` pre vloženie do zásobníku volaní). Ďalej implementuje funkciu `get_arg` ktorá poskytuje argumenty a pri neexistencii skončí program príslušnou chybou.

Trieda `Argument` má typ a hodnotu.


## test.php

Testovací skript bol najprv implementovaný len pre vyhodnocaovanie výstupu parseru s jednoduchý výstupom. Neskôr bola pridaná podpora pre testovanie celého projektu.

Pri testovacom skripte je najprv riešené spracovanie argumentov príkazového riadku a ich validácia. Kontrola existencie zadaných adresárov a súborov je realizovaná pomocou funkcií `check_file` a `ckeck_dir`.

Následne sa prehľadá zadaný adresár. Štandardne je pre tento účel použitý `DirectoryIterator`, no pri rekurzívnom prehľadávaní je to `RecursiveDirectoryIterator`. Pri prehľadávaní sa filtrujú súbory s koncovkou `.src`, pretože to je jediný súbor ktorý je povinný pri každom teste. Cesta každého testovacieho súboru sa následne uloží do poľa bez prípony.

V tele cyklu sa pre každý test následne skontrolujú ostatné súbory (`.out`,`.rc`,`.in`) a pokiaľ neexistujú tak sa vytvoria prázdne.

Skripty sú spúšťané pomocou funkcie `shell_exec`.
Ak sa jedná o testovanie len parseru, tak sa spustí len tento skript sám s tým že je použitý prepínač `<` spolu s cestou k vstupnému súboru.
Podobné je to aj pri testovaní s `--int-only`, tu sa však poskytuje zdrojový súbor cez `--source=<cesta>` a vtupný súbor pomocou prepínaču `>`. Pri testovaní oboch skriptov naraz je vytvorený medzisúbor s príponou `.test.xml` a ak je návratový kód parseru `0`, tak je spustený aj interpret.
Výstupy testov sú v súbore s príponou `.test.out` v adresári testu.

Pri validácii výsledkov sa v prvom rade hľadí na návratový kód. Ak je návratový kód 0, tak porovnáva výstupné súbory pomocou nástroja `diff`, prípadne `jexamxml` pri použití `--parse-only`.

Dočasné súbory `.test.out`, prípadne `.test.xml` budú pri absencii prepínača `--noclean` vymazané.

Pre každý test je vytvorený objekt, ktorý obsahuje názov (cestu k testu), skutočné a očakávané výstupy a návratové kódy a výsledok testu. Tieto objekty sú postupne pridávané do poľa.

Ku koncu sa vypisuje výstupný HTML kód. Výpis kódu je realizovaný pomocou `echo`, podľa vytvorenej statickej predlohy.
Na začiatku dokumentu sú celkové štatistiky spolu s argumentami príkazového riadku, ktoré sa môžu zísť pri neskoršom prehliadaní výsledkov. Následne sa pre každý neuspešný test vypíše výsledok testovacieho prípadu. Takýto výsledok pozostáva z názvu prípadu, očakávaných a reálnych vstupov a návratových kódov. V prípade nesprávneho výstupu a použitia `--parse-only` je tu správa, že jexamxml našiel rozdiel vo výstupných súboroch. Ak boli medzi testami aj nejaké úspešné, bude súčasťou aj zoznam týchto testov, ktorý je predvolene schovaný a dá sa zobraziť kliknutím na tlačítko `toggle` (ak je vynutý JavaScript, zoznam zostane zobrazený).