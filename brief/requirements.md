# Brief di prodotto — WordPress Multisite Manager

Versione: 0.1 — 29 ottobre 2026
Stato: brief di riferimento; prima anteprima diagnostica implementata. Conversione e gestione siti restano fasi successive.
Nome di lavoro: WordPress Multisite Manager. Nome commerciale e slug definitivo da definire.

## 1. Obiettivo e utenti

Un plugin che accompagna agenzie, freelance e utenti esperti nel setup e nella gestione di una rete WordPress Multisite con siti in sottodirectory, attraverso un'interfaccia moderna e comprensibile.

Il plugin parte da un'installazione WordPress esistente: valuta se può diventare una rete, automatizza i passaggi supportati dall'ambiente e fornisce istruzioni contestuali per quelli che richiedono interventi dell'amministratore o dell'hosting. Deve anche gestire una rete in sottodirectory già attiva.

Esempio: https://example.com/ è il sito principale; https://example.com/sito-a/ e https://example.com/sito-b/ sono due siti della stessa rete. Sono siti logici Multisite con contenuti e configurazioni proprie; non sono installazioni WordPress autonome né necessariamente directory fisiche. Condividono il core, le risorse di rete e il sistema utenti secondo il modello WordPress.

## 2. Requisiti forniti dal committente

- Setup e gestione WordPress Multisite.
- Siti nel formato example.com/sito-a e example.com/sito-b.
- Interfaccia moderna e fruibile.
- Compatibilità PHP: 7.4.x, 8.0.x, 8.1.x, 8.2.x, 8.3.x, 8.4.x, 8.5.x.
- Compatibilità WordPress: dalla 7.0 in avanti.
- Target: agenzie, freelance, utenti esperti.
- Valutazione preventiva dei vincoli WordPress e hosting; guida alternativa quando l'automazione non è possibile.

Le sezioni successive traducono questi requisiti in una proposta di prima versione. Le scelte di perimetro aggiuntive sono assunzioni progettuali modificabili, non richieste già approvate.

## 3. Perimetro proposto per la prima versione

### Diagnostica dell'ambiente

Rilevare: versione WordPress/PHP/database, installazione singola o rete esistente, tipo di rete, URL e percorso di installazione, HTTPS, permessi dell'utente, accessibilità dei file di configurazione, configurazione di rewrite verificabile, vincoli e conflitti noti. Individuare differenze tra sito nuovo e sito già popolato, collisioni tra percorsi di siti e contenuti esistenti, configurazioni gestite tramite variabili d'ambiente o file non standard.

Ogni controllo riporta esito, evidenza, conseguenza e azione proposta. Stati: compatibile, intervento richiesto, non supportato, non verificabile. L'assenza di una prova non equivale a compatibilità. I limiti commerciali o tecnici del provider non deducibili dal processo PHP vanno esplicitati come non verificabili e accompagnati da una richiesta pronta per il supporto hosting.

Esiti complessivi:
1. Setup assistito disponibile: controlli essenziali superati e modifiche previste supportate.
2. Procedura manuale necessaria: ambiente potenzialmente compatibile, ma passaggi non eseguibili dal plugin.
3. Setup non supportato: vincolo accertato, senza forzature.
4. Valutazione incompleta: ulteriori informazioni/verifiche necessarie prima di procedere.

### Setup guidato della rete

Percorso proposto: verifica → scelta nome rete ed email amministratore → anteprima URL e modifiche → verifica disponibilità backup → conferma → esecuzione/passaggi manuali → verifica finale.

Usare i meccanismi nativi di WordPress. Applicare solo trasformazioni esplicitamente supportate e verificabili; la sola scrivibilità di wp-config.php non autorizza modifiche arbitrarie. Mostrare un riepilogo dei file, delle impostazioni e dei dati interessati prima dell'esecuzione. Richiedere un backup completo ripristinabile di file e database, senza presentare la copia di un singolo file come backup del sito.

Se i file sono non scrivibili, la configurazione è non standard o servono interventi server, generare una guida con percorso del file, snippet contestuale, prerequisiti, verifiche e istruzioni per l'hosting. Nginx e hosting gestiti possono richiedere una modifica esterna al plugin. Il wizard deve riprendere e rieseguire i controlli dopo l'intervento manuale, senza duplicare rete o siti.

Considerare la disattivazione/riattivazione dei plugin e il passaggio da amministrazione singola ad amministrazione di rete nel processo nativo. Conservare lo stato minimo necessario per riprendere il wizard. Non promettere conversioni atomiche o rollback automatici: in caso di interruzione identificare i passaggi riusciti e fornire una procedura di recupero.

### Gestione dei siti

Proposta MVP: elenco con ricerca e paginazione; creazione sito tramite slug, titolo ed email amministratore; modifica di titolo ed email amministrativa; collegamenti al frontend e al pannello di ogni sito; archiviazione e riattivazione dei siti secondari con conferma.

Validare slug, percorsi riservati, duplicati e conflitti noti prima di creare il sito. Il sito principale non deve essere archiviabile. I contenuti restano nei siti WordPress e la gestione utenti segue le autorizzazioni native. Non creare password o ruoli privilegiati in modo implicito.

## 4. Interfaccia proposta

- Dashboard: stato rete, riepilogo siti e azioni che richiedono attenzione.
- Wizard: passi brevi, progresso visibile, possibilità di riprendere.
- Diagnostica: esito leggibile, spiegazione e azione per ogni controllo.
- Siti: tabella ricercabile/paginata e creazione guidata, con anteprima dell'URL finale.
- Guida: istruzioni generate dal contesto, copia snippet e riepilogo esportabile senza segreti.

Aspetto coerente, gerarchia chiara, stati vuoti/caricamento/errore/successo. Navigazione da tastiera, focus visibile, etichette accessibili ed errori associati ai campi. Gli stati non devono dipendere dal solo colore. Layout utilizzabile su desktop e mobile. Testi italiani, predisposti alla traduzione. Libreria grafica e design definitivo da scegliere durante la progettazione.

## 5. Ruoli, dati e disinstallazione

Prima della conversione: operazioni disponibili solo ad amministratori autorizzati al setup. Dopo la conversione: funzioni di rete riservate ai super amministratori, con controlli di capability anche lato server. Un amministratore di sito non può creare reti, modificare altri siti o accedere ai report sensibili.

Salvare solo opzioni del plugin, stato del wizard e log tecnici essenziali, senza password, chiavi, cookie o contenuto integrale di wp-config.php. Nessuna telemetria o servizio esterno necessario nell'MVP.

La disattivazione o rimozione del plugin non deve eliminare siti, utenti, contenuti, tabelle Multisite o configurazioni della rete. Eventuale cancellazione dei soli dati del plugin sarà esplicita. La rete deve continuare a funzionare con le funzionalità native WordPress.

## 6. Compatibilità e ambiente di prova

Codice PHP e dipendenze compatibili con PHP 7.4: evitare sintassi/API disponibili solo nelle versioni successive o prevedere alternative. Dichiarare Requires PHP: 7.4 e Requires at least: 7.0 nel prodotto definitivo. Lo starter attuale richiede PHP 8.3 ed è soltanto una prova della factory: non dimostra ancora questa compatibilità.

Obiettivo WordPress 7.0+: certificare le release stabili effettivamente testate; ogni nuova release entra nella matrice dopo verifica. Non garantire in anticipo compatibilità con versioni future non pubblicate. Alla data del brief, la matrice ufficiale indica WordPress 7.0/7.1 compatibili con le sette linee PHP richieste.

Matrice di accettazione iniziale: ultima patch disponibile di WordPress 7.0 e 7.1 × ultima patch disponibile delle linee PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5. Registrare le patch esatte e le prove; compatibilità non significa testare ogni patch storica. Le versioni PHP fuori manutenzione restano un requisito di retrocompatibilità, non una raccomandazione per nuove installazioni.

Servono fixture distinte: sito singolo nuovo, sito singolo popolato, rete a sottodirectory, rete a sottodomini non supportata, configurazione non scrivibile/non standard e rewrite non funzionante. Prevedere test Apache e almeno un caso Nginx con intervento manuale.

L'ambiente attuale localhost:8898/8899 resta utile per lo sviluppo generico. La documentazione segnala restrizioni del setup nativo su porte diverse da 80/443: per validare la conversione predisporre un hostname locale e porte compatibili, oppure documentare una fixture alternativa che riproduca davvero il comportamento nativo. Non certificare il wizard basandosi su una rete preconfigurata.

## 7. Criteri di accettazione osservabili

| ID | Scenario | Risultato atteso |
| --- | --- | --- |
| AC-01 | Installazione singola nuova, compatibile | Diagnostica con evidenze e riepilogo delle modifiche; nessuna modifica prima della conferma. |
| AC-02 | Setup assistito confermato | Rete a sottodirectory funzionante, accesso amministrativo preservato, controlli finali superati. |
| AC-03 | File non scrivibili o intervento server necessario | Nessun successo simulato; guida contestuale con snippet, passaggi, verifiche e possibilità di ripresa. |
| AC-04 | Vincolo rilevato o compatibilità non verificabile | Motivazione e stato distinti; nessuna forzatura; indicazioni per risolvere o contattare l'hosting. |
| AC-05 | Creazione sito-a e sito-b | Entrambi raggiungibili nei percorsi previsti, pannelli accessibili e contenuti distinti; sito principale funzionante. |
| AC-06 | Slug duplicato, riservato o in conflitto | Errore comprensibile, nessun sito parziale o modifica del sito esistente. |
| AC-07 | Rete a sottodirectory già attiva | Accesso diretto alla gestione; nessun nuovo setup della rete. |
| AC-08 | Rete a sottodomini o topologia non supportata | Stato non supportato e spiegazione; nessuna conversione automatica. |
| AC-09 | Utente non autorizzato o richiesta senza protezione valida | Operazione negata lato server, anche richiamando direttamente endpoint e azioni. |
| AC-10 | Errore/interruzione durante il setup | Stato coerente dei passi completati, diagnosi e guida di recupero; ripresa senza duplicazioni. |
| AC-11 | Sito singolo già popolato | Rilevazione dei vincoli nativi e dei possibili impatti sui permalink; percorso manuale/staging quando necessario, nessuna modifica silenziosa agli URL. |
| AC-12 | Archiviazione e riattivazione | Sito secondario passa agli stati previsti; il sito principale è protetto. |
| AC-13 | Rimozione plugin | Rete, siti e contenuti continuano a funzionare tramite WordPress. |
| AC-14 | Matrice versioni | Attivazione, diagnostica e flussi supportati senza fatal error; deprecazioni del plugin corrette, problemi delle dipendenze documentati. |
| AC-15 | Uso da tastiera e schermo mobile | Flussi principali completabili, focus visibile, errori leggibili e controlli accessibili. |

I criteri sono requisiti da verificare, non risultati già ottenuti. Il check tecnico dello starter non li soddisfa.

## 8. Esclusioni proposte per l'MVP

Gestione centralizzata di installazioni su server diversi; reti a sottodomini; domain mapping; conversione tra tipi di rete o ritorno a sito singolo; migrazione/clonazione di siti; provisioning di server e acquisto domini; backup remoto completo; modifica automatica delle configurazioni globali dell'hosting; eliminazione definitiva dei siti; modifica degli URL di siti popolati; fatturazione e funzioni commerciali per rivendita hosting.

## 9. Consegne e prossime decisioni

Consegne attese: plugin ZIP installabile, sorgenti, guida setup/recupero, matrice compatibilità con evidenze, report test e ambiente locale documentato.

Da definire prima dell'implementazione: nome e slug, conferma delle operazioni di gestione MVP, eventuali hosting prioritari e livello di automazione ammesso per i file di configurazione. Il successivo passo tecnico è uno studio di fattibilità del setup nativo, prima di promettere modifiche automatiche a wp-config.php e rewrite.

## Fonti tecniche consultate

- Requisiti reti e restrizioni del setup: https://developer.wordpress.org/advanced-administration/multisite/prepare-network/
- Matrice WordPress/PHP: https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/

Le fonti guidano i controlli; il comportamento va verificato sulle versioni effettivamente supportate.

## Esito studio di fattibilità (29 ottobre 2026)

Vedi reports/multisite-feasibility.md per prove, revisione indipendente e proposta tecnica. La prima versione proposta prevede diagnosi, guida e passaggio al setup nativo con successiva riattivazione del manager: il core richiede la disattivazione di tutti i plugin. Automazione completa da validare su fixture isolata.

Precisazioni: il gate del mese controlla contenuti pubblicati, non la data installazione. Il riepilogo deve mostrare impatto sui permalink e utenti che diventeranno super amministratori. La restrizione documentale sulle porte non è stata riprodotta su 7.1.4 e resta da verificare.

Criteri aggiuntivi proposti: AC-16 — anteprima e verifica dei privilegi risultanti; AC-17 — guida fruibile con plugin disattivato e ripresa autorizzata dopo riattivazione in rete.
