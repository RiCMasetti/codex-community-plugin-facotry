# Fattibilità — WordPress Multisite Manager

Data: 29 ottobre 2026. Stato: studio completato, proposta tecnica; plugin funzionale non implementato.

## Esito

Fattibilità positiva per diagnostica, guida contestuale e gestione di reti in sottodirectory. La conversione completamente automatica resta da validare con un prototipo isolato: non è una capacità già dimostrata.

Proposta iniziale: il manager diagnostica il sito, prepara il piano e una guida esportabile, accompagna l'utente nel setup nativo e riprende il controllo dopo la riattivazione a livello di rete. Per installazioni standard l'automazione selettiva dei file potrà essere aggiunta dopo le prove dedicate. Questa proposta limita l'automazione iniziale e deve restare distinta dal requisito finale di automatizzare dove possibile.

## Metodo ed evidenze

- Ispezione dei sorgenti realmente installati: WordPress 7.1.4.
- Revisione indipendente di un secondo agente in sola lettura, integrata in questo documento.
- Sei verifiche su PHP 8.3.36: tre sul sito/capability reali senza scritture applicative e tre sulla funzione nativa con database sostituito temporaneamente in memoria.
- Risultato: 6/6 PASS. Evidenze in `feasibility-probe.json`; script riproducibile in `scripts/feasibility-probe.mjs`.
- Ambiente osservato: single-site, un plugin attivo, nessun contenuto pubblicato più vecchio di un mese, gate nativo delle sottodirectory positivo.
- Non eseguiti: conversione, creazione siti, modifiche a wp-config/rewrite, prove browser del wizard, matrice PHP 7.4–8.5, verifica WordPress 7.0, Nginx o hosting commerciali.

## Riscontri dai sorgenti

Percorsi relativi alla distribuzione WordPress 7.1.4, non al plugin.

| Riscontro | Evidenza | Implicazione |
| --- | --- | --- |
| Il wizard nativo si ferma se esistono plugin attivi | `wp-admin/includes/network.php:136–149` | Anche il manager va disattivato; guida esportabile e ripresa dopo attivazione di rete. |
| La restrizione del mese riguarda contenuti pubblicati, non la data d'installazione | `wp-admin/includes/network.php:54–78` | Eseguire sia il gate nativo sia una query diagnostica esplicita; distinguere errore query da assenza contenuti. |
| Il gate può essere forzato da filtro/costante | Stessa funzione | Non introdurre override; risultato nativo positivo non certifica assenza rischi sui contenuti. |
| La conversione a sottodirectory cambia permalink del sito principale | `wp-admin/includes/schema.php:1134–1140` | Anteprima della struttura risultante e dei possibili URL coinvolti; staging/redirect per siti popolati. |
| Il core aggiunge amministratori esistenti alla lista dei super amministratori | `wp-admin/includes/schema.php:1238–1242,1274–1287` | Anteprima privilegi prima della conversione e confronto dopo. L'email rete può identificare un utente aggiuntivo. |
| Tabelle, rete e file sono passaggi separati | `wp-admin/network.php:94–114`; `includes/schema.php:977–983` | Non trattare come transazione unica; distinguere stato parziale e impedire retry ciechi. |
| Il config può stare sopra ABSPATH | `wp-admin/includes/network.php:425–427` | Rilevare il percorso effettivo, senza assumere il layout standard. |
| Le regole Apache dipendono dai percorsi; Nginx rinvia a configurazione esterna | `wp-admin/includes/network.php:677–709` | Adattatori separati; guida per Nginx e configurazioni non riconosciute. |
| Capability del setup diversa prima/dopo conversione | `wp-includes/capabilities.php:773–779` | Usare setup_network prima, capability di rete e super admin dopo; nonce e controlli server per ogni azione. |

### Precisazione sulle porte locali

La documentazione generale dichiara una restrizione sulle porte diverse da 80/443. Nei file di setup/runtime 7.1.4 ispezionati non è stato trovato un rifiuto esplicito: viene rimossa solo la porta standard dal nome host. Non abbiamo provato la conversione su 8898/8899, quindi queste porte non sono né certificate né dimostrate incompatibili. La fixture di riferimento sarà un hostname locale con porta standard; si proverà separatamente il caso con porta personalizzata. Nessuna modifica alle porte attuali è necessaria per lo studio.

## Matrice decisionale proposta

| Caso | Esito | Azione |
| --- | --- | --- |
| Rete già attiva, sottodirectory e singola rete riconosciuta | Gestione disponibile | Verifica accessi, elenco siti, operazioni native. |
| Single-site nuovo, layout standard, controlli superati | Setup assistito | Piano, backup confermato, guida nativa e verifica finale; scrittura automatica non ancora certificata. |
| Config o rewrite non scrivibili | Intervento manuale | Snippet contestuali e istruzioni hosting; mantenere lo stato in attesa. |
| Nginx/hosting gestito | Intervento manuale o valutazione incompleta | Rilevare ciò che è osservabile; chiedere verifica provider quando serve. |
| Contenuti vecchi/URL a rischio | Migrazione da pianificare | Non forzare il gate; staging e piano permalink prima di cambiare la rete. |
| Costante DO_NOT_UPGRADE_GLOBAL_TABLES definita | Blocco del setup nativo | Spiegare vincolo e inviare a verifica configurazione; non rimuoverla automaticamente. |
| Rete DB presente, Multisite non attivo | Recupero configurazione | Non ripopolare rete; riconciliare database, costanti e rewrite. |
| Sottodomini, più reti, domain mapping, sunrise/layout custom non riconosciuto | Fuori profilo MVP | Spiegazione e percorso dedicato senza modifiche. |
| Impossibile verificare server, permessi DB o policy provider | Valutazione incompleta | Nessuna etichetta compatibile; elenco delle verifiche mancanti. |

Le autorizzazioni DDL del database non si dimostrano col solo accesso SELECT; eventuali prove di scrittura devono avvenire solo nella fase autorizzata e con pulizia delle risorse di prova.

## Architettura proposta

1. **Inspector**: raccoglie osservazioni read-only, con origine e limiti (CLI e processo web possono avere utenti/permessi differenti).
2. **EligibilityPolicy**: determina esito e motivazioni, evitando di assumere che file scrivibile significhi hosting compatibile.
3. **PlanBuilder**: produce piano con URL, file, effetti su permalink e privilegi, passi manuali e impronta della configurazione. Le impronte non devono esporre segreti.
4. **GuideRenderer**: mostra ed esporta istruzioni e verifiche. Non esporta interi config, credenziali o liste utenti a persone non autorizzate.
5. **SetupStateRepository**: conserva stato sul sito principale prima della conversione; dopo la riattivazione riconcilia con rete/costanti reali e trasferisce lo stato utile in opzioni di rete.
6. **NetworkVerifier**: verifica sito principale, siti secondari, admin, routing, asset e permessi. Il solo HTTP 200 non prova il routing: controllare identificatore/contenuto del sito atteso e redirect.
7. **SiteManager**: usa API native per creazione/aggiornamento/archiviazione e capability specifiche; controlli separati per URL, titolo, email amministrativa e assegnazione utenti.
8. **Admin UI**: wizard e dashboard, stato/azione chiari. Libreria frontend da scegliere dopo prototipo dei flussi; nessuna dipendenza runtime PHP oltre 7.4.

Stati proposti: `unassessed → assessed → plan_ready → awaiting_backup_confirmation → awaiting_native_setup → database_prepared → awaiting_config → network_active → awaiting_rewrite_verification → verified`. Gestione esplicita di `manual_action_required` e `recovery_required`.

Gli stati sono derivati anche dai fatti osservati: il plugin può essere disattivato durante il setup e non può intercettarne tutti i passaggi. La riattivazione richiede nuova autenticazione/autorizzazione e non deve eseguire automaticamente operazioni in sospeso.

### Futuro adattatore di modifica file

Ammesso solo per profili validati. Prima dell'applicazione: verificare configurazione invariata, conflitti di costanti, symlink, posizione e proprietà dei file, backup protetti fuori da percorsi pubblici, fattibilità scrittura/rename e PHP valido. Non usare espressioni regolari generiche per riscrivere PHP arbitrario. Conservare istruzioni di ripristino fuori dal plugin perché un config invalido può impedire a WordPress di avviarsi. In assenza di questi requisiti: guida manuale, senza degrado silenzioso.

## Prototipo necessario per chiudere i rischi

- Fixture isolata single-site, senza dati utente, hostname locale e porta standard.
- Caso pulito: disattivazione plugin → setup nativo sottodirectory → applicazione configurazione → nuovo login → attivazione rete manager → riconciliazione stato.
- Creazione sito-a/sito-b; contenuti distinti, login, wp-admin, asset, permalink, media e redirect.
- Caso popolato: contenuto vecchio e permalink personalizzati; diagnosi prima delle modifiche e misura effetti dopo conversione nella sola fixture.
- Caso privilegi: almeno due amministratori e utente corrispondente all'email rete; confronto previsto/effettivo dei super admin.
- Interruzione dopo preparazione DB: ripresa senza duplicare rete/tabelle/siti.
- File non scrivibili/config sopra root/rewrite errato: guida corretta e nessun falso positivo.
- Nginx e porte personalizzate: prove dedicate, non inferenze dal caso Apache.
- Successivamente matrice 7.0/7.1 × PHP 7.4, 8.0–8.5, con patch esatte; test UI e controlli autorizzativi.

## Prossima unità di sviluppo proposta

Implementare prima la diagnostica read-only e la schermata degli esiti, con report esportabile e test della policy. In parallelo alla fase successiva, validare la fixture di conversione. La gestione dei siti segue dopo una rete test verificata. Non esporre il pulsante di conversione automatica prima di aver validato il percorso completo e il recupero.

## Fonti

- https://developer.wordpress.org/reference/functions/allow_subdirectory_install/
- https://developer.wordpress.org/cli/commands/core/multisite-convert/
- https://developer.wordpress.org/advanced-administration/multisite/prepare-network/

WP-CLI può preparare tabelle e costanti, ma richiede comunque configurazione rewrite: non è una dipendenza obbligatoria per il plugin distribuito e non equivale a conversione end-to-end verificata.
