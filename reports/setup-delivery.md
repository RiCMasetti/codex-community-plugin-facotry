# Multisite Manager 0.2.0 — setup guidato

Consegnato il 30 ottobre 2026. Wizard disponibile su localhost:8898/wp-admin/admin.php?page=multisite-manager-setup. Piano persistente con nome/email, conferme operative, rilevazione piano obsoleto, export, passaggio al setup nativo e recupero dopo attivazione rete.

## Verifiche

- Conversione reale tramite POST al modulo core network.php su fixture dedicata localhost:8900; costanti e rewrite generati dal core applicati dagli strumenti di collaudo, mai dal plugin. Backup locale fuori dal webroot.
- Piano salvato prima, riconoscimento database preparato, ritrovamento piano e rete attiva dopo disattivazione/riattivazione.
- Due siti /sito-a/ e /sito-b/: contenuti distinti, permalink, wp-admin e asset CSS verificati HTTP.
- POST, nonce, autenticazione, campi/conferme e fingerprint verificati. Amministratore locale del sottosito respinto da save/recheck/export e pagina network; utente temporaneo eliminato.
- 30 casi policy diagnostica e 16 setup, più lint, passati su PHP 7.4.33, 8.0.30, 8.1.34, 8.2.34, 8.3.36, 8.4.27, 8.5.11. La matrice usa helper isolati; integrazione WordPress reale solo 7.1.4/PHP8.3.36.
- Revisione indipendente: corretti recupero senza WP_ALLOW_MULTISITE e precedenza incompatibilità nota su topologia incompleta.
- UI prima/dopo osservata nel browser, screenshot setup-wizard.png e setup-network.png. Nessuna certificazione mobile/accessibilità completa.

Il runner HTTP è stato ripreso dopo errori di connessione persistente e correzioni allo script di prova; la rete non è stata riconvertita. Verifiche accumulate nel report setup-native.json.

## Limiti del prodotto

Il wizard accompagna una conversione nativa: non è un installer automatico, non scrive configurazione o rewrite e non crea siti. Le conferme backup/hosting sono dichiarazioni dell’operatore. Il controllo rete è interno: il routing viene collaudato separatamente. Media upload, hosting Nginx/gestiti, matrice completa WordPress/PHP e scenari di migrazione dati reali restano da verificare.

Il salvataggio mantiene dati nell’opzione msm_setup_plan_v1 del sito principale, incluso l’indirizzo scelto. Disattivare il plugin non elimina il piano. Il prossimo modulo è la gestione siti dedicata; al momento si usano le schermate native WordPress.

Pacchetto: dist/multisite-manager-0.2.0.zip
SHA256: bdb5ed2815d8922c9bd440ffa70e3a66ff50a31be3bbb7c44a65fbeeda8c66a2
