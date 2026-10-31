<?php
namespace MSM;
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Admin {
    private static $file;
    private static $page_hook = '';

    public static function boot( $file ) {
        self::$file = $file;
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'network_admin_menu', array( __CLASS__, 'network_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_msm_export_guide', array( __CLASS__, 'export' ) );
        add_action( 'admin_post_nopriv_msm_export_guide', array( __CLASS__, 'deny' ) );
    }

    public static function allowed() {
        return is_multisite() ? is_super_admin() && current_user_can( 'manage_network_options' ) : current_user_can( 'setup_network' );
    }

    public static function can_access() { return self::allowed(); }

    public static function menu() {
        if ( ! is_multisite() && self::allowed() ) { self::register( 'setup_network' ); }
    }

    public static function network_menu() {
        if ( self::allowed() ) { self::register( 'manage_network_options' ); }
    }

    private static function register( $capability ) {
        self::$page_hook = add_menu_page( __( 'Multisite Manager', 'multisite-manager' ), __( 'Multisite Manager', 'multisite-manager' ), $capability, 'multisite-manager', array( __CLASS__, 'page' ), 'dashicons-networking', 65 );
    }

    public static function assets( $hook ) {
        if ( self::$page_hook && $hook === self::$page_hook && self::allowed() ) {
            wp_enqueue_style( 'msm-admin', plugins_url( 'assets/admin.css', self::$file ), array(), '0.2.0' );
        }
    }

    public static function deny() {
        wp_die( esc_html__( 'Non hai i permessi per accedere alla diagnostica della rete.', 'multisite-manager' ), esc_html__( 'Accesso negato', 'multisite-manager' ), array( 'response' => 403 ) );
    }

    public static function export() {
        if ( ! self::allowed() ) { self::deny(); }
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            wp_die( esc_html__( 'Usa il pulsante di esportazione della dashboard.', 'multisite-manager' ), '', array( 'response' => 405 ) );
        }
        check_admin_referer( 'msm_export_guide', 'msm_nonce' );
        $report = Policy::evaluate( Inspector::collect() );
        nocache_headers();
        header( 'Content-Type: text/plain; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="multisite-manager-guida.txt"' );
        header( 'X-Content-Type-Options: nosniff' );
        echo Guide::render( $report ); // Plain text attachment assembled from allowlisted observations.
        exit;
    }

    public static function page() {
        if ( ! self::allowed() ) { self::deny(); }
        $report = Policy::evaluate( Inspector::collect() );
        $counts = array( 'pass'=>0, 'action'=>0, 'unknown'=>0, 'unsupported'=>0 );
        foreach ( $report['checks'] as $check ) { ++$counts[$check['status']]; }
        $f = $report['facts'];
        $refresh = is_network_admin() ? network_admin_url( 'admin.php?page=multisite-manager' ) : admin_url( 'admin.php?page=multisite-manager' );
        ?>
        <div class="wrap msm-shell">
            <header class="msm-header">
                <div><p class="msm-eyebrow"><?php esc_html_e( 'WORDPRESS • DIAGNOSTICA DI RETE', 'multisite-manager' ); ?></p><h1><?php esc_html_e( 'Multisite Manager', 'multisite-manager' ); ?></h1><p class="msm-lead"><?php esc_html_e( 'Una rete ben preparata comincia da un quadro chiaro.', 'multisite-manager' ); ?></p></div>
                <span class="msm-version"><?php esc_html_e( 'Anteprima 0.2', 'multisite-manager' ); ?></span>
            </header>
            <nav class="msm-nav" aria-label="<?php esc_attr_e( 'Sezioni della diagnostica', 'multisite-manager' ); ?>"><a href="#msm-overview"><?php esc_html_e( 'Panoramica', 'multisite-manager' ); ?></a><a href="#msm-checks"><?php esc_html_e( 'Controlli', 'multisite-manager' ); ?></a><a href="#msm-guide"><?php esc_html_e( 'Guida operativa', 'multisite-manager' ); ?></a><a href="<?php echo esc_url( Setup::url() ); ?>"><?php esc_html_e( 'Setup guidato', 'multisite-manager' ); ?></a></nav>
            <section id="msm-overview" class="msm-overview" aria-labelledby="msm-result-title">
                <div class="msm-status-mark" aria-hidden="true">◎</div>
                <div class="msm-overview-copy"><span class="msm-badge msm-<?php echo esc_attr( $report['overall']['status'] ); ?>"><?php echo esc_html( Guide::label( $report['overall']['status'] ) ); ?></span><h2 id="msm-result-title"><?php echo esc_html( $report['overall']['title'] ); ?></h2><p><?php echo esc_html( $report['overall']['summary'] ); ?></p></div>
                <a class="msm-button msm-button-secondary" href="<?php echo esc_url( $refresh ); ?>"><?php esc_html_e( 'Ripeti diagnosi', 'multisite-manager' ); ?></a>
            </section>
            <div class="msm-metrics" aria-label="<?php esc_attr_e( 'Riepilogo dei controlli', 'multisite-manager' ); ?>">
                <?php foreach ( $counts as $status => $count ) : ?><div class="msm-metric"><span class="msm-metric-number"><?php echo esc_html( (string) $count ); ?></span><span class="msm-dot msm-<?php echo esc_attr( $status ); ?>" aria-hidden="true"></span><span><?php echo esc_html( Guide::label( $status ) ); ?></span></div><?php endforeach; ?>
            </div>
            <div class="msm-layout">
                <section id="msm-checks" class="msm-panel" aria-labelledby="msm-checks-title"><div class="msm-panel-heading"><div><p class="msm-eyebrow"><?php esc_html_e( 'IL TUO AMBIENTE', 'multisite-manager' ); ?></p><h2 id="msm-checks-title"><?php esc_html_e( 'Risultati della diagnosi', 'multisite-manager' ); ?></h2></div><span class="msm-subtle"><?php echo esc_html( sprintf( __( '%d controlli', 'multisite-manager' ), count( $report['checks'] ) ) ); ?></span></div>
                    <p class="msm-check-intro"><?php esc_html_e( 'Un controllo superato riguarda solo l’evidenza indicata. Le verifiche mancanti impediscono di dichiarare il setup pronto.', 'multisite-manager' ); ?></p>
                    <div class="msm-check-list"><?php foreach ( $report['checks'] as $check ) : ?><article class="msm-check" data-check="<?php echo esc_attr( $check['id'] ); ?>"><div class="msm-check-heading"><h3><?php echo esc_html( $check['label'] ); ?></h3><span class="msm-badge msm-<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( Guide::label( $check['status'] ) ); ?></span></div><p class="msm-evidence"><?php echo esc_html( $check['evidence'] ); ?></p><p class="msm-action"><?php echo esc_html( $check['action'] ); ?></p></article><?php endforeach; ?></div>
                </section>
                <aside class="msm-side" aria-label="<?php esc_attr_e( 'Passi successivi', 'multisite-manager' ); ?>"><div class="msm-panel msm-next"><p class="msm-eyebrow"><?php esc_html_e( 'IL PROSSIMO PASSO', 'multisite-manager' ); ?></p><h2><?php esc_html_e( 'Porta con te la guida', 'multisite-manager' ); ?></h2><p><?php esc_html_e( 'Salva risultati e istruzioni prima di passare al setup nativo. Rimarranno disponibili anche quando i plugin saranno disattivati.', 'multisite-manager' ); ?></p>
                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="msm_export_guide"><?php wp_nonce_field( 'msm_export_guide', 'msm_nonce' ); ?><button class="msm-button" type="submit"><?php esc_html_e( 'Scarica guida .txt', 'multisite-manager' ); ?></button></form><p class="msm-fine"><?php esc_html_e( 'Senza credenziali, nomi utenti o contenuto dei file di configurazione.', 'multisite-manager' ); ?></p></div>
                    <div class="msm-note"><h2><?php esc_html_e( 'Cosa fa questa versione', 'multisite-manager' ); ?></h2><p><?php esc_html_e( 'Legge l’ambiente e salva un piano nel Setup guidato. La conversione resta affidata a WordPress; il plugin non modifica file e non crea siti.', 'multisite-manager' ); ?></p><p><?php esc_html_e( 'Hosting, routing e compatibilità completa richiedono verifiche ulteriori.', 'multisite-manager' ); ?></p></div>
                </aside>
            </div>
            <section id="msm-guide" class="msm-panel msm-guide" aria-labelledby="msm-guide-title"><div class="msm-panel-heading"><div><p class="msm-eyebrow"><?php esc_html_e( 'DA LEGGERE PRIMA DEL SETUP', 'multisite-manager' ); ?></p><h2 id="msm-guide-title"><?php esc_html_e( 'Guida operativa', 'multisite-manager' ); ?></h2></div></div><details><summary><?php esc_html_e( 'Apri la guida completa per questa installazione', 'multisite-manager' ); ?></summary><pre><?php echo esc_html( Guide::render( $report ) ); ?></pre></details></section>
            <footer class="msm-footer"><?php esc_html_e( 'Diagnosi eseguita ora • Nessuna modifica applicata • Nessun servizio esterno contattato', 'multisite-manager' ); ?></footer>
        </div>
        <?php
    }
}
