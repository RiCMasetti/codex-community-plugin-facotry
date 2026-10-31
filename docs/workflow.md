# Workflow supervisionato

1. Brief: completare brief/requirements.md e registrare decisioni e criteri.
2. Piano: il coordinatore assegna ID, responsabilità, dipendenze e verifiche in state/project.json.
3. Ambiente: npm ci, npm run doctor, npm run env:start. Registrare versioni effettive.
4. Sviluppo: attività piccole, un proprietario per file. Parallelizzare solo attività indipendenti.
5. Verifica: npm run check, poi test specifici del prodotto e prova nel browser. Il revisore salva findings e prove in reports/.
6. Correzione: massimo due tentativi per difetto, poi nuova decisione del coordinatore.
7. Consegna: npm run package, installazione dello ZIP in ambiente di prova e report finale. Nessuna pubblicazione automatica.

Stati attività: pending, in_progress, blocked, done. Un'attività done richiede evidence con il percorso di un report. Lo stato è aggiornato dal coordinatore, non da una pipeline autonoma.

## Evoluzione successiva
Solo dopo un primo plugin verificato: orchestratore persistente, coda, budget API, timeout e retry. Agents SDK e Codex SDK saranno valutati allora; non sono dipendenze di questo prototipo.
