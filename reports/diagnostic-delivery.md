# Consegna — Multisite Manager 0.1.0

## Realizzato

Plugin installabile con dashboard italiana, CSS locale responsive, 18 controlli diagnostici nel profilo single-site, guida contestuale esportabile e protezione tramite capability, sessione autenticata, metodo POST e nonce. Nessuna scrittura applicativa a database/configurazioni, conversione o chiamata esterna. Nome e slug di lavoro: Multisite Manager / multisite-manager.

## Verifiche e prove

- Sintassi dei 5 file PHP e attivazione: PASS (check.json).
- 30 scenari di policy su ciascuno di 7 runtime PHP: PASS, incluse osservazioni mancanti/null, errore query, rete parziale, sottodomini, più reti e porte custom (php-matrix.json).
- 12 verifiche funzionali HTTP e cleanup utente di prova: PASS (http-check.json). Amministratore autorizzato; anonimo/subscriber respinti; nonce assente/invalido respinto; GET export respinto; download autorizzato e senza marker di segreti o path host.
- ZIP installato e attivato su WordPress test senza bind del plugin sorgente (package-verification.json).
- Verifica visiva nel browser: dashboard e apertura della guida; screenshot dashboard.png. Layout esaminato alla larghezza disponibile di 816px. Non è stata eseguita una certificazione mobile o audit accessibilità completo.
- Revisione indipendente: trovato e corretto un P2 sui falsi positivi con osservazioni mancanti. Finding chiuso, nessun ulteriore bug concreto segnalato negli ambiti esaminati. Analisi statica di autorizzazioni, nonce, escaping, leak e compatibilità sintattica.

Gli esiti PHP sono lint e test isolati con helper WordPress simulati: non certificano tutte le combinazioni WordPress/PHP. Prove reali eseguite su WordPress 7.1.4/PHP 8.3.36 in single-site. Conversione, reti Multisite reali, WordPress 7.0, Nginx e policy dei provider restano da collaudare.

## Pacchetto

File: dist/multisite-manager-0.1.0.zip
SHA-256: c038c587f9278bcaa1317d419a354f8cc3b77901d9094a48642dbb72b9af52d8

Lo ZIP finale è identico al pacchetto verificato nel WordPress test. Esclude tests, node_modules, reports e crew-starter.

## Stato del progetto

Prima unità di sviluppo completata. Il prodotto complessivo non è completo: prossimo passo previsto è la fixture di conversione e il collaudo della diagnostica su rete reale, poi gestione dei siti e automazione selettiva. Il dashboard segnala correttamente “Da verificare” per hosting, permessi DDL e routing: non sono errori del plugin, ma verifiche che questo modulo non può certificare.

## Note di riproduzione

Usare npm run check e npm run test:http secondo README. La matrice isolata si ripete con scripts/php-matrix.mjs e Docker; le immagini elencate devono essere presenti o scaricabili. Nessun commit/push eseguito. La configurazione wp-env è stata aggiornata soltanto per il plugin e i mount delle fixture locali.
