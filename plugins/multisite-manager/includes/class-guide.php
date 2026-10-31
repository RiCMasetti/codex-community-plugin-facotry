<?php
namespace MSM;
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Guide {
    public static function render( array $report ) {
        $f = $report['facts'];
        $lines = array(
            __( 'MULTISITE MANAGER — GUIDA DIAGNOSTICA', 'multisite-manager' ),
            __( 'Modulo 0.2.0 • diagnosi in sola lettura; piano separato nel Setup guidato', 'multisite-manager' ),
            '',
            $report['overall']['title'],
            $report['overall']['summary'],
            '',
            sprintf( __( 'WordPress: %1$s | PHP: %2$s | Database: %3$s', 'multisite-manager' ), $f['wp_version'], $f['php_version'], $f['db_version'] ),
            __( 'Lettura puntuale: rieseguire la diagnosi dopo ogni modifica. Nessuna certificazione completa della matrice versioni.', 'multisite-manager' ),
            '',
            __( 'RISULTATI', 'multisite-manager' ),
        );
        foreach ( $report['checks'] as $check ) {
            $lines[] = '[' . self::label( $check['status'] ) . '] ' . $check['label'];
            // URLs remain visible in the authenticated dashboard only; the downloadable guide omits them.
            $lines[] = 'urls' === $check['id'] ? __( 'Indirizzi omessi dall’esportazione. Consultare la dashboard.', 'multisite-manager' ) : $check['evidence'];
            $lines[] = $check['action'];
            $lines[] = '';
        }
        $lines[] = __( 'PERCORSO CONSIGLIATO', 'multisite-manager' );
        if ( $f['multisite'] ) {
            $lines[] = __( 'La rete è già attiva: non ripetere la conversione. Verificare tipo di rete, routing, permessi e contenuti in staging. Per le operazioni sui siti usare gli strumenti nativi di amministrazione rete.', 'multisite-manager' );
        } elseif ( 'present' === $f['network_db_state'] ) {
            $lines[] = __( 'Sono presenti dati di rete ma Multisite non è attivo. Non ricreare la rete e non eseguire una seconda conversione. Recuperare il backup e le istruzioni del setup nativo; far riconciliare database, costanti e rewrite prima di riattivare i plugin.', 'multisite-manager' );
        } elseif ( 'unsupported' === $report['overall']['status'] ) {
            $lines[] = __( 'Non avviare il setup su questa configurazione. Risolvere i vincoli segnalati con il responsabile dell’installazione, quindi eseguire una nuova diagnosi.', 'multisite-manager' );
        } else {
            $lines[] = __( '1. Risolvere i controlli non verificabili e gli interventi richiesti. Confermare con l’hosting il supporto Multisite a sottodirectory; usare una copia di staging.', 'multisite-manager' );
            $lines[] = __( '2. Preparare e provare il ripristino di un backup completo di file e database. Una copia di wp-config.php non è un backup completo.', 'multisite-manager' );
            $lines[] = __( '3. Valutare gli URL del sito principale: la conversione nativa a sottodirectory può impostare /blog/%year%/%monthnum%/%day%/%postname%/. Preparare eventuali redirect.', 'multisite-manager' );
            $lines[] = __( '4. Verificare chi avrà privilegi di super amministratore: il core può includere tutti gli amministratori attuali e l’utente associato all’email della rete.', 'multisite-manager' );
            if ( ! $f['wp_allow_multisite'] ) {
                $lines[] = __( '5. Solo dopo le verifiche, abilitare il setup tramite la configurazione effettiva WordPress. Se WP_ALLOW_MULTISITE esiste già, modificarne il valore nella sua sorgente; non duplicare la costante. Per un wp-config.php standard, il frammento seguente va prima del caricamento di wp-settings.php, all’interno del codice PHP già aperto. In configurazioni gestite rivolgersi all’hosting:', 'multisite-manager' );
                $lines[] = "define( 'WP_ALLOW_MULTISITE', true );";
                $lines[] = __( 'Questo abilita il menu di setup: non converte il sito in Multisite.', 'multisite-manager' );
            } else {
                $lines[] = __( '5. L’accesso al setup è già abilitato: non aggiungere altre definizioni di WP_ALLOW_MULTISITE.', 'multisite-manager' );
            }
            $lines[] = __( '6. Conservare questa guida fuori da WordPress. Disattivare tutti i plugin, incluso Multisite Manager, quindi aprire Strumenti → Configurazione della rete e scegliere sottodirectory solo se disponibile.', 'multisite-manager' );
            $lines[] = __( '7. Applicare le istruzioni specifiche generate dal core per wp-config.php e rewrite. Non sostituire regole server personalizzate con esempi generici. Per Nginx chiedere all’hosting le modifiche e la verifica del servizio.', 'multisite-manager' );
            $lines[] = __( '8. Effettuare un nuovo accesso, riattivare Multisite Manager a livello di rete e rieseguire la diagnosi. Una conversione interrotta richiede recupero, non la ripetizione cieca dei passaggi.', 'multisite-manager' );
            $lines[] = __( '9. Prima di usare la rete verificare frontend, wp-admin, login, media, asset e permalink del sito principale e di siti secondari di prova; controllare identificatore/contenuti e destinazioni dei redirect, non soltanto HTTP 200.', 'multisite-manager' );
        }
        $lines[] = '';
        $lines[] = __( 'RICHIESTA PER IL SUPPORTO HOSTING', 'multisite-manager' );
        $lines[] = __( 'Potete confermare il supporto di WordPress Multisite con siti in sottodirectory sul mio piano? Servono verifica dei permessi per le tabelle di rete, configurazione rewrite per il server in uso, accesso alla configurazione WordPress e una procedura di ripristino. Sono presenti proxy, cache o vincoli gestiti che richiedono passaggi specifici?', 'multisite-manager' );
        $lines[] = '';
        $lines[] = __( 'Documentazione ufficiale: https://developer.wordpress.org/advanced-administration/multisite/prepare-network/', 'multisite-manager' );
        $lines[] = __( 'Questa guida non contiene password, chiavi, nomi utenti né contenuti dei file di configurazione. Rivederla comunque prima di condividerla.', 'multisite-manager' );
        return implode( "\n", $lines ) . "\n";
    }

    public static function label( $status ) {
        $labels = array( 'pass'=>__( 'Controllo superato', 'multisite-manager' ), 'action'=>__( 'Intervento richiesto', 'multisite-manager' ), 'unsupported'=>__( 'Non supportato', 'multisite-manager' ), 'unknown'=>__( 'Da verificare', 'multisite-manager' ) );
        return isset( $labels[$status] ) ? $labels[$status] : $labels['unknown'];
    }
}
