Implementační dokumentace k 1. úloze do IPP 2021/2022
Jméno a příjmení: Juraj Dedič
Login: xdedic07

# IPP22 - Projekt

### Úvod

Pri riešení som sa snažil využiť OOP preto som si vytvoril triedu **Operand**, ktorá predstavuje argument inštrukcie a triedu **Instruction** pre inštrukcie programu.

Pred začiatkom syntaktickej analýzy si vytváram pole s inštrukciami a ich očakávanými typmi operandov.

### Syntaktická analýza

V samotnom tele parseru (slučke while), následne naklonujeme inštrukciu a pomocou metód skontrolujeme či sú operandy v poriadku a zapíšeme ich do príslušných parametrov. V prípade, že na mieste kde očakávame operačný kód je token ktorý tomuto kódu neodpovedá, program bude končiť s chybovým kódom. Takisto chybovým kódom skončí program pokiaľ sú operandy v nesprávnom tvare.

Nakoniec objekt inštrukcie s vyplnenými parametrami uložíme do poľa, ktoré predstavuje program.

### Výstup

Pre generovanie výstupného xml používam knižnicu xmlwriter. Po vypísaní potrebných tagov sa postupne prechádza pole s inštrukciami a pre každú z nich sa do výstupného súboru zapíšu informácie z predpripravených objektov.
