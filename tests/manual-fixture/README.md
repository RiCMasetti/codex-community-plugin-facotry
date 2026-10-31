# Evidenze fixture locale

Script ad hoc usati nel collaudo del 30 ottobre 2026, conservati per audit. Non sono una suite generica da rilanciare: incorporano percorso e porta della fixture e il primo converte un sito single-site. La fixture è già Multisite. Non eseguire nuovamente né usare su dati reali.

Il test è stato ripreso dopo connessioni HTTP persistenti interrotte (risolto usando Connection: close) e correzioni al runner per rilevamento siti esistenti e username alfanumerico. Nessuna seconda conversione del database. Il report setup-native.json contiene le verifiche accumulate, incluse alcune ripetizioni. La matrice PHP e check.mjs sono i test ripetibili.

Gli script originali restano locali e sono esclusi da Git: contengono percorsi specifici della macchina e operazioni di conversione non riutilizzabili. I report JSON e gli screenshot originali restano locali.
