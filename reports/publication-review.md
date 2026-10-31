# Preparazione GitHub — 31 ottobre 2026

Ambito: file candidati alla prima pubblicazione della factory e dei plugin inclusi. Nessun commit locale preesistente. Revisione svolta dal medesimo agente, senza revisore indipendente.

- Percorsi personali rimossi dalla documentazione pubblica.
- Script ad hoc di conversione, report JSON e screenshot conservati localmente ed esclusi da Git; possono contenere dettagli dell'ambiente o dati inseriti nei test.
- Dipendenze installate, pacchetti ZIP, file ambiente, database, backup, chiavi e log esclusi.
- Credenziali presenti nei test pubblici: valori demo wp-env e password temporanee generate casualmente. Non sono credenziali di produzione.
- Immagine PHP 8.3 della matrice resa indipendente dal nome del container locale.
- GPL-2.0-or-later confermata dagli header preesistenti; testo GPL v2 incluso nella root e in entrambi i plugin.

La scansione per pattern e la revisione dei file candidati non costituiscono una garanzia assoluta di assenza di segreti né un audit completo della sicurezza applicativa. I report storici di test descrivono esecuzioni precedenti, non una nuova certificazione di tutte le versioni e degli hosting.
