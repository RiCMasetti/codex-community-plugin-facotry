# WordPress Plugin Crew — Codex

Factory locale supervisionata. Primo prodotto: **Multisite Manager 0.2.0**, diagnostica e setup guidato per WordPress Multisite in sottodirectory.

## Prova il plugin

Sviluppo: http://localhost:8898/wp-admin/admin.php?page=multisite-manager

Installazione dello ZIP verificata separatamente: http://localhost:8899/wp-admin/admin.php?page=multisite-manager

Credenziali locali wp-env: admin / password. Sono ambienti di sviluppo; non esporli pubblicamente. La dashboard è disponibile nel menu Multisite Manager. Mostra 18 controlli sul sito singolo, azioni consigliate e guida esportabile in testo. Il modulo non converte WordPress e non crea siti.

Genera lo ZIP con `npm run package`: il percorso include versione e timestamp ed è stampato dal comando. I pacchetti storici e i checksum restano locali. Report: reports/setup-delivery.md.

## Avvio e verifiche

Prerequisiti: Git, Node.js (LTS consigliata), npm, Docker attivo con Compose, Codex con accesso ChatGPT per lavorare con la crew.

```sh
npm ci
npm run doctor
npm run env:start
npm run check
npm run package
npm run test:http
npm run env:stop
```

check verifica sintassi, attivazione e scenari della policy. test:http richiede lo ZIP già installato nel WordPress test su localhost:8899; usa le credenziali demo, crea un utente subscriber temporaneo e lo rimuove al termine. Non eseguirlo su installazioni con dati reali. Per installare un nuovo ZIP nel test: npm run wp-env -- run tests-cli wp plugin install /var/www/html/opt/msm-dist/NOME-FILE.zip --activate (aggiungere --force solo per aggiornare questa copia di test).

Il test usa una copia installata dello ZIP; il plugin non è montato dai sorgenti in quell'ambiente. Le cartelle tests e dist sono mappate nel webroot locale sotto opt/msm-tests e opt/msm-dist solo per il collaudo, e non sono parte dello ZIP.

## Come lavora la crew

Apri questa directory come progetto in Codex. AGENTS.md definisce regole e ruoli. brief/requirements.md contiene il prodotto; crew/ le responsabilità; state/project.json lo stato; reports/ le evidenze.

Incarico di esempio: «Leggi AGENTS.md e il brief. Coordina lo sviluppo della prossima attività, delega implementazione e revisione a subagenti disponibili, verifica nell'ambiente locale e aggiorna stato ed evidenze».

Questa factory è supervisionata: non contiene orchestratore autonomo, scheduler o chiamate API OpenAI a pagamento. La compatibilità WordPress del plugin è separata dai requisiti Node/npm degli strumenti di sviluppo.

## Compatibilità e limiti

WordPress locale fissato a 7.1.4; runtime ambiente PHP 8.3.36. Il plugin richiede PHP 7.4 e WordPress 7.0. Sintassi e 30 scenari isolati verificati su PHP 7.4.33, 8.0.30, 8.1.34, 8.2.34, 8.3.36, 8.4.27, 8.5.11. Integrazione reale HTTP/ZIP: WordPress 7.1.4 e PHP 8.3.36 single-site. La rete reale in sottodirectory è verificata anche su localhost:8900. La matrice completa di integrazione WordPress/PHP e gli hosting diversi restano da verificare.

package-lock.json blocca wp-env 12.0.0 e dipendenze npm. Le immagini container possono essere aggiornate: non promettiamo riproducibilità bit per bit. wp-env mantiene dati fuori dal repository, normalmente in ~/.wp-env e volumi Docker. Stop conserva i dati. Reset/destroy li eliminano e non vanno usati senza accettarne la perdita.

wp-env usa ancora il suo ambiente test integrato (opzioni deprecate ma operative); migrazione a configurazioni separate da valutare nel prossimo ciclo. L'installazione iniziale ha segnalato quattro avvisi moderati nella catena di sviluppo Playground/Express/qs e un requisito npm >=11.16.0 di alcuni componenti; npm nella sessione originale era 11.12.1. Il percorso Docker è stato verificato. Queste dipendenze non sono incluse nel plugin ZIP.

Il vecchio plugins/crew-starter resta come esempio del bootstrap. Non è incluso nel pacchetto Multisite Manager.

## Setup guidato 0.2.0

Apri http://localhost:8898/wp-admin/admin.php?page=multisite-manager-setup per preparare il piano. Il plugin salva nome/email, conferme backup/hosting e stato osservato. Il piano obsoleto richiede revisione esplicita. La conversione usa il setup nativo WordPress: configurazione e rewrite si applicano manualmente seguendo le istruzioni del core. Riattivando il plugin nella rete si ritrova il piano. Nessuna modifica automatica a wp-config.php/.htaccess.

Nella sessione di sviluppo originale è stata verificata una fixture separata: http://localhost:8900/wp-admin/network/admin.php?page=multisite-manager-setup, siti /sito-a/ e /sito-b/. Avvio/stop: npm run setup:start e npm run setup:stop. Non rilanciare una conversione su questa rete. L’ambiente 8898 resta single-site. Il backup della fixture originale è fuori dal webroot nella cartella wp-env della fixture. Un nuovo clone non contiene database, backup o i siti demo: la configurazione avvia Multisite, i sottositi vanno creati nelle schermate native.

Il wizard verifica lo stato interno e propone una checklist; non certifica automaticamente routing e hosting. Elenco/creazione/modifica dei siti usano ancora le schermate native WordPress. La gestione siti con interfaccia dedicata è la fase successiva. Il piano resta nel database anche disattivando il plugin e contiene l’email scelta; vedere docs/setup-contract.md.

## Pubblicazione e privacy

Questo repository contiene sorgenti, brief, ruoli della crew e report narrativi. I JSON di esecuzione, gli screenshot, gli script di conversione specifici della macchina, i database, i backup e i pacchetti generati restano locali e sono esclusi da Git. I riferimenti a questi file nei report e nello stato sono evidenze storiche, non file scaricabili dal repository. I comandi di test rigenerano i rispettivi report locali.

Le credenziali `admin / password` sono i valori demo di wp-env e servono solo per installazioni locali isolate. Il progetto non richiede credenziali API OpenAI. Non pubblicare esportazioni del wizard senza verificarle: possono contenere il nome e l'email scelti.

## Licenza

GPL-2.0-or-later: GNU General Public License, versione 2 o, a scelta, qualsiasi versione successiva. Vedere [LICENSE](LICENSE). I due plugin includono anche una copia della licenza nei propri sorgenti. Le dipendenze di terzi mantengono le rispettive licenze.

## Resoconto del progetto

[Leggi il resoconto PDF](docs/resoconto-codex-plugin-factory.pdf): metodo, contributo dei subagent, risultati, analisi di prodotto e dati disponibili sui costi. Documento del 31 ottobre 2026; i saldi riportati sono storici e riferiti all’intero account, non al solo progetto.
