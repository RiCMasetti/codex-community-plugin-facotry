=== Multisite Manager ===
Contributors: riccardomas
Tags: multisite, network, diagnostics
Requires at least: 7.0
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Diagnostica in sola lettura e guida contestuale per reti WordPress a sottodirectory.

== Description ==

Anteprima tecnica: dashboard diagnostica con controlli dell'ambiente, azioni consigliate e guida testuale esportabile. Il Setup guidato salva un piano e accompagna al setup nativo con verifica dello stato dopo la riattivazione. Non converte l'installazione, non modifica configurazioni e non gestisce ancora i siti.

La versione legge informazioni disponibili a WordPress e segnala ciò che non può verificare, come policy hosting e routing completo. Gli stati verdi riguardano singoli controlli e non certificano il setup complessivo.

La dashboard è disponibile agli amministratori autorizzati su sito singolo e ai super amministratori autorizzati nel pannello di rete. L'esportazione richiede sessione autenticata, permessi e nonce valido.

Nessuna telemetria, chiamata API esterna o risorsa grafica remota. Il Setup guidato conserva nell'opzione msm_setup_plan_v1 del sito principale nome ed email della rete, ID dell'autore, conferme operative, impronta delle osservazioni e date. Nessun segreto o contenuto della configurazione viene memorizzato. Il piano rimane dopo disattivazione e disinstallazione, per consentire la ripresa del setup: eliminarlo tramite gestione delle opzioni se non serve più. Disattivazione e disinstallazione non rimuovono siti o dati WordPress.

Requisiti dichiarati: WordPress 7.0+, PHP 7.4–8.5. La matrice completa deve ancora essere certificata; consultare il report di consegna per le combinazioni realmente verificate. Le future famiglie WordPress sono indicate come da verificare.

== Installation ==

1. Installare lo ZIP in Plugin > Aggiungi plugin > Carica plugin.
2. Attivare il plugin; su rete già esistente attivarlo a livello di rete.
3. Aprire Multisite Manager nel menu amministrativo.
4. Aprire Setup guidato, rivedere nome/email, effetti su URL e privilegi, confermare backup e hosting e salvare il piano.
5. Esportare piano e istruzioni (include email: condividere solo con autorizzati), disattivare tutti i plugin e completare il setup nativo.
6. Riattivare a livello di rete e aprire Setup guidato > Verifica rete. Il controllo interno non certifica il routing: verificare manualmente frontend, amministrazione, media e URL dei siti.

== Changelog ==

= 0.2.0 =
* Setup guidato con piano persistente, conferme esplicite e protezione POST/capability/nonce.
* Ripresa dopo il setup nativo, rilevamento piano obsoleto e collegamenti ai siti della rete.
* Esportazione privata del piano; nessuna conversione automatica o scrittura dei file.

= 0.1.0 =
* Prima anteprima diagnostica in sola lettura.
