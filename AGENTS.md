# WordPress Plugin Crew — istruzioni condivise

## Modalità e costi
Workflow supervisionato in Codex con login ChatGPT. Nessuna chiamata API OpenAI è implementata. Non avviare servizi a pagamento o introdurre chiavi API senza richiesta. Gli agenti condividono i limiti del piano.

## Ruoli
Il coordinatore legge README.md, docs/workflow.md, brief/requirements.md e state/project.json. Può delegare sviluppo e revisione a subagenti disponibili. Il revisore è indipendente dallo sviluppatore; se non disponibile, dichiarare la revisione svolta dal medesimo agente. Non creare nuove chat per simulare subagenti.
Leggere il file di ruolo in crew/ prima dell'incarico. Delegare solo compiti delimitati con input, file posseduti e criteri di accettazione. Evitare modifiche concorrenti agli stessi file.

## Sviluppo
Non utilizzare il progetto wordpress-plugin adiacente come sorgente senza richiesta. Il plugin crew-starter è solo una prova tecnica. Definire lo slug del prodotto quando il brief sarà disponibile.
Preservare modifiche dell'utente. Verificare git status prima delle modifiche. Non fare commit, push, pubblicazioni, reset o pulizia dati senza istruzioni pertinenti.
Per WordPress: capability check e nonce nelle azioni amministrative; permission_callback nelle REST route; validazione e sanitizzazione degli input, escaping contestuale degli output, query preparate. Mai segreti nel repository o nei log.

## Evidenze
Non dichiarare completata una fase solo perché è stato scritto del codice. Salvare risultati e limiti in reports/, aggiornare state/project.json dopo verifica. npm run check verifica solo sintassi PHP e attivazione: non sostituisce test funzionali, sicurezza o controllo visivo.
Massimo 2 cicli di correzione per lo stesso difetto prima di riportare causa e opzioni al coordinatore. Ogni consegna indica file, controlli eseguiti e problemi aperti.
