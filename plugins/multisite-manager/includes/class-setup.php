<?php
namespace MSM;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Guided handoff to the native network installer. Never writes core configuration. */
final class Setup {
    const OPTION = 'msm_setup_plan_v1';
    private static $file;
    private static $hook = '';

    public static function boot( $file ) {
        self::$file = $file;
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
        add_action( 'network_admin_menu', array( __CLASS__, 'menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        foreach ( array( 'save', 'recheck', 'export' ) as $action ) {
            add_action( 'admin_post_msm_setup_' . $action, array( __CLASS__, $action ) );
            add_action( 'admin_post_nopriv_msm_setup_' . $action, array( 'MSM\\Admin', 'deny' ) );
        }
    }
    public static function url() {
        return is_multisite() ? network_admin_url( 'admin.php?page=multisite-manager-setup' ) : admin_url( 'admin.php?page=multisite-manager-setup' );
    }
    public static function menu() {
        if ( ! Admin::allowed() || ( is_multisite() && ! is_network_admin() ) ) { return; }
        self::$hook = add_submenu_page( 'multisite-manager', __( 'Setup guidato', 'multisite-manager' ), __( 'Setup guidato', 'multisite-manager' ), is_multisite() ? 'manage_network_options' : 'setup_network', 'multisite-manager-setup', array( __CLASS__, 'page' ) );
    }
    public static function assets( $hook ) {
        if ( self::$hook && self::$hook === $hook && Admin::allowed() ) {
            wp_enqueue_style( 'msm-admin', plugins_url( 'assets/admin.css', self::$file ), array(), '0.2.0' );
        }
    }
    public static function owner_id() {
        return is_multisite() ? (int) get_main_site_id( get_current_network_id() ) : (int) get_current_blog_id();
    }
    public static function plan() {
        $value = is_multisite() ? get_blog_option( self::owner_id(), self::OPTION, array() ) : get_option( self::OPTION, array() );
        return is_array( $value ) && isset( $value['version'] ) && 1 === $value['version'] ? $value : array();
    }
    private static function persist( array $plan ) {
        if ( is_multisite() ) {
            update_blog_option( self::owner_id(), self::OPTION, $plan );
        } else {
            update_option( self::OPTION, $plan, false );
        }
        if ( self::plan() !== $plan ) { self::fail( __( 'Non è stato possibile salvare il piano. Verifica i permessi del database.', 'multisite-manager' ), 500 ); }
    }
    /** No secrets, timestamps, or configuration contents enter this fingerprint. */
    public static function fingerprint( array $facts ) {
        $snapshot = array();
        foreach ( array( 'wp_version','php_version','multisite','subdomain_install','network_count','network_db_state','topology_verified','custom_topology','site_url','home_url','old_published_count','native_subdirectory_allowed','do_not_upgrade_global_tables','config_location','config_readable','config_writable','custom_content_dir','admin_count','permalink_structure' ) as $key ) {
            $snapshot[$key] = array_key_exists( $key, $facts ) ? $facts[$key] : null;
        }
        return hash( 'sha256', wp_json_encode( $snapshot ) );
    }
    public static function phase( array $facts, array $plan ) {
        if ( ! empty( $facts['multisite'] ) ) {
            if ( ! empty( $facts['subdomain_install'] ) || ! empty( $facts['custom_topology'] ) || $facts['network_count'] > 1 ) { return 'unsupported'; }
            if ( empty( $facts['topology_verified'] ) || 1 !== $facts['network_count'] ) { return 'assessment_incomplete'; }
            return 'network_active_routing_pending';
        }
        if ( 'present' === $facts['network_db_state'] ) { return 'database_prepared'; }
        if ( 'unknown' === $facts['network_db_state'] ) { return 'assessment_incomplete'; }
        if ( empty( $plan ) ) { return 'plan_required'; }
        if ( empty( $plan['fingerprint'] ) || ! hash_equals( $plan['fingerprint'], self::fingerprint( $facts ) ) ) { return 'plan_stale'; }
        return 'awaiting_native_setup';
    }
    public static function blockers( array $facts ) {
        $messages = array();
        $report = Policy::evaluate( $facts );
        foreach ( $report['checks'] as $check ) { if ( 'unsupported' === $check['status'] ) { $messages[] = $check['label'] . ': ' . $check['evidence']; } }
        if ( ! empty( $facts['multisite'] ) ) { $messages[] = __( 'La rete è già attiva: usa la verifica finale, senza creare un nuovo piano di conversione.', 'multisite-manager' ); }
        if ( 'none' !== $facts['network_db_state'] ) { $messages[] = __( 'Prima di preparare un nuovo setup occorre chiarire lo stato della rete nel database.', 'multisite-manager' ); }
        if ( null === $facts['old_published_count'] || null === $facts['native_subdirectory_allowed'] || ! $facts['native_subdirectory_allowed'] || $facts['old_published_count'] > 0 ) {
            $messages[] = __( 'Il controllo sui contenuti non consente il percorso standard. Serve un piano di migrazione in staging; non forzare il gate delle sottodirectory.', 'multisite-manager' );
        }
        if ( empty( $facts['topology_verified'] ) ) { $messages[] = __( 'La topologia non è stata verificata.', 'multisite-manager' ); }
        return array_unique( $messages );
    }
    private static function fail( $message, $status = 400 ) { wp_die( esc_html( $message ), esc_html__( 'Setup guidato', 'multisite-manager' ), array( 'response'=>$status ) ); }
    private static function guard( $action ) {
        if ( ! Admin::allowed() ) { Admin::deny(); }
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) { self::fail( __( 'Questa operazione richiede il modulo della pagina setup.', 'multisite-manager' ), 405 ); }
        check_admin_referer( 'msm_setup_' . $action, 'msm_setup_nonce' );
    }
    private static function input( $key ) { return isset( $_POST[$key] ) && is_string( $_POST[$key] ) ? wp_unslash( $_POST[$key] ) : ''; }
    public static function save() {
        self::guard( 'save' );
        $facts = Inspector::collect();
        $blocks = self::blockers( $facts );
        if ( $blocks ) { self::fail( implode( ' ', $blocks ), 409 ); }
        if ( ! hash_equals( self::fingerprint( $facts ), self::input( 'msm_fingerprint' ) ) ) { self::fail( __( 'La configurazione è cambiata: ricarica il setup e rivedi il piano.', 'multisite-manager' ), 409 ); }
        $name = sanitize_text_field( self::input( 'network_name' ) );
        $email = sanitize_email( self::input( 'network_email' ) );
        if ( '' === $name || strlen( $name ) > 160 || ! is_email( $email ) ) { self::fail( __( 'Inserisci un nome rete (massimo 160 byte) e un indirizzo email valido.', 'multisite-manager' ) ); }
        if ( '1' !== self::input( 'backup_confirmed' ) || '1' !== self::input( 'hosting_confirmed' ) ) { self::fail( __( 'Conferma backup ripristinabile e verifica dei requisiti con l’hosting prima di salvare il piano.', 'multisite-manager' ) ); }
        $plan = array( 'version'=>1, 'network_name'=>$name, 'network_email'=>$email, 'fingerprint'=>self::fingerprint( $facts ), 'owner_site_id'=>self::owner_id(), 'created_by'=>get_current_user_id(), 'saved_at'=>gmdate( 'c' ), 'backup_confirmed'=>true, 'hosting_confirmed'=>true );
        self::persist( $plan );
        wp_safe_redirect( self::url() ); exit;
    }
    public static function recheck() {
        self::guard( 'recheck' );
        $facts = Inspector::collect();
        $plan = self::plan();
        if ( $plan ) {
            $plan['last_checked_at'] = gmdate( 'c' );
            $plan['last_observed_phase'] = self::phase( $facts, $plan );
            // Never replace the original fingerprint silently; stale plans need explicit re-save.
            self::persist( $plan );
        }
        wp_safe_redirect( self::url() ); exit;
    }
    public static function export() {
        self::guard( 'export' );
        $plan = self::plan();
        if ( ! $plan ) { self::fail( __( 'Salva prima un piano di setup.', 'multisite-manager' ), 409 ); }
        $facts = Inspector::collect();
        $lines = array( __( 'MULTISITE MANAGER — PIANO DI SETUP', 'multisite-manager' ), 'Nome rete: ' . $plan['network_name'], 'Email rete: ' . $plan['network_email'], 'Salvato: ' . $plan['saved_at'], 'Stato osservato: ' . self::phase( $facts, $plan ), __( 'Se il piano è obsoleto (plan_stale), rivederlo e salvarlo nuovamente prima del setup. Non usare le istruzioni per una nuova conversione se il database o la rete sono già preparati.', 'multisite-manager' ), '', __( 'Contiene l’email amministrativa scelta: condividere solo con persone autorizzate.', 'multisite-manager' ), __( 'Le conferme backup/hosting sono dichiarazioni dell’amministratore, non verifiche automatiche.', 'multisite-manager' ), __( 'Nel setup nativo usare il nome e l’email riportati sopra; scegliere sottodirectory solo se disponibile.', 'multisite-manager' ), '', Guide::render( Policy::evaluate( $facts ) ) );
        nocache_headers(); header( 'Content-Type: text/plain; charset=UTF-8' ); header( 'Content-Disposition: attachment; filename="multisite-manager-piano.txt"' ); header( 'X-Content-Type-Options: nosniff' );
        echo implode( "\n", $lines ); exit;
    }
    private static function form_open( $action ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="msm_setup_' . esc_attr( $action ) . '">';
        wp_nonce_field( 'msm_setup_' . $action, 'msm_setup_nonce' );
    }
    public static function page() {
        if ( ! Admin::allowed() ) { Admin::deny(); }
        $facts = Inspector::collect(); $plan = self::plan(); $phase = self::phase( $facts, $plan ); $blocks = self::blockers( $facts );
        $labels = array( 'plan_required'=>__( 'Prepara il piano', 'multisite-manager' ), 'awaiting_native_setup'=>__( 'Piano salvato: prosegui con il setup nativo', 'multisite-manager' ), 'plan_stale'=>__( 'Configurazione cambiata: rivedi il piano', 'multisite-manager' ), 'database_prepared'=>__( 'Database preparato: completa la configurazione', 'multisite-manager' ), 'network_active_routing_pending'=>__( 'Rete attiva: verifica routing e siti', 'multisite-manager' ), 'unsupported'=>__( 'Rete fuori dal profilo supportato', 'multisite-manager' ), 'assessment_incomplete'=>__( 'Verifica preliminare incompleta', 'multisite-manager' ) );
        $name = isset( $plan['network_name'] ) ? $plan['network_name'] : get_option( 'blogname' );
        $email = isset( $plan['network_email'] ) ? $plan['network_email'] : get_option( 'admin_email' );
        ?>
        <div class="wrap msm-shell msm-setup">
            <header class="msm-header"><div><p class="msm-eyebrow"><?php esc_html_e( 'DALLA DIAGNOSI ALLA RETE', 'multisite-manager' ); ?></p><h1><?php esc_html_e( 'Setup guidato', 'multisite-manager' ); ?></h1><p class="msm-lead"><?php esc_html_e( 'Prepara, completa e verifica il percorso Multisite.', 'multisite-manager' ); ?></p></div><span class="msm-version">0.2.0</span></header>
            <nav class="msm-nav" aria-label="<?php esc_attr_e( 'Sezioni Multisite Manager', 'multisite-manager' ); ?>"><a href="<?php echo esc_url( is_multisite() ? network_admin_url( 'admin.php?page=multisite-manager' ) : admin_url( 'admin.php?page=multisite-manager' ) ); ?>"><?php esc_html_e( 'Diagnostica', 'multisite-manager' ); ?></a><a href="<?php echo esc_url( self::url() ); ?>" aria-current="page"><?php esc_html_e( 'Setup guidato', 'multisite-manager' ); ?></a></nav>
            <ol class="msm-steps"><li>1. <?php esc_html_e( 'Piano e conferme', 'multisite-manager' ); ?></li><li>2. <?php esc_html_e( 'Setup nativo', 'multisite-manager' ); ?></li><li>3. <?php esc_html_e( 'Verifica della rete', 'multisite-manager' ); ?></li></ol>
            <section class="msm-overview" data-setup-phase="<?php echo esc_attr( $phase ); ?>"><div class="msm-overview-copy"><span class="msm-badge msm-unknown"><?php esc_html_e( 'Stato rilevato', 'multisite-manager' ); ?></span><h2><?php echo esc_html( $labels[$phase] ); ?></h2><p><?php esc_html_e( 'Il piano resta salvato durante la disattivazione del plugin. La conversione viene eseguita tramite WordPress e le istruzioni del tuo hosting.', 'multisite-manager' ); ?></p></div><?php self::form_open( 'recheck' ); ?><button class="msm-button msm-button-secondary" type="submit"><?php echo esc_html( $facts['multisite'] ? __( 'Verifica rete', 'multisite-manager' ) : __( 'Ricontrolla stato', 'multisite-manager' ) ); ?></button></form></section>
            <?php if ( 'plan_stale' === $phase ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Le osservazioni sono cambiate dal salvataggio. Esamina nuovamente dati e conferme e salva un nuovo piano. Il vecchio piano non autorizza altri interventi.', 'multisite-manager' ); ?></p></div><?php endif; ?>
            <?php if ( $facts['multisite'] ) : ?>
                <section class="msm-panel msm-setup-panel"><h2><?php esc_html_e( 'La rete esiste già', 'multisite-manager' ); ?></h2><p><?php esc_html_e( 'Non avviare una nuova conversione. “Verifica rete” rilegge la configurazione e lo stato del database; non prova via HTTP il routing e non certifica i siti.', 'multisite-manager' ); ?></p>
                <?php if ( $plan ) : ?><p><?php esc_html_e( 'Piano ritrovato sul sito principale:', 'multisite-manager' ); ?> <strong><?php echo esc_html( $plan['network_name'] ); ?></strong></p><?php endif; ?>
                <?php if ( 'network_active_routing_pending' === $phase ) : ?><p><a class="msm-button" href="<?php echo esc_url( network_admin_url( 'sites.php' ) ); ?>"><?php esc_html_e( 'Apri i siti della rete', 'multisite-manager' ); ?></a></p><ul class="msm-site-links"><?php foreach ( get_sites( array( 'network_id'=>get_current_network_id(), 'number'=>20 ) ) as $site ) : ?><li><a href="<?php echo esc_url( get_home_url( $site->blog_id ) ); ?>"><?php echo esc_html( get_home_url( $site->blog_id ) ); ?></a> · <a href="<?php echo esc_url( get_admin_url( $site->blog_id ) ); ?>"><?php esc_html_e( 'Amministrazione', 'multisite-manager' ); ?></a></li><?php endforeach; ?></ul><p><?php esc_html_e( 'Mostrati fino a 20 siti. Controlla per ogni sito contenuti distinti, login, wp-admin, media, asset, permalink e redirect. Conferma anche l’elenco dei super amministratori.', 'multisite-manager' ); ?></p><?php endif; ?>
                </section>
            <?php elseif ( 'database_prepared' === $phase ) : ?>
                <section class="msm-panel msm-setup-panel"><h2><?php esc_html_e( 'Riprendi senza creare un’altra rete', 'multisite-manager' ); ?></h2><p><?php esc_html_e( 'Il database contiene già una rete ma Multisite non è attivo. Recupera dal setup nativo le istruzioni generate e completa costanti e rewrite. Non ripetere il popolamento delle tabelle. In caso di dubbi usa la procedura di ripristino verificata.', 'multisite-manager' ); ?></p><a class="msm-button" href="<?php echo esc_url( admin_url( 'network.php' ) ); ?>"><?php esc_html_e( 'Apri le istruzioni native', 'multisite-manager' ); ?></a></section>
            <?php else : ?>
                <div class="msm-layout"><section class="msm-panel msm-setup-panel"><h2><?php esc_html_e( '1. Prepara il piano', 'multisite-manager' ); ?></h2>
                    <?php if ( $blocks ) : ?><div class="msm-setup-warning"><h3><?php esc_html_e( 'Prima risolvi questi punti', 'multisite-manager' ); ?></h3><ul><?php foreach ( $blocks as $block ) : ?><li><?php echo esc_html( $block ); ?></li><?php endforeach; ?></ul></div><?php else : ?>
                    <?php self::form_open( 'save' ); ?><input type="hidden" name="msm_fingerprint" value="<?php echo esc_attr( self::fingerprint( $facts ) ); ?>">
                    <p class="msm-field"><label for="msm-network-name"><?php esc_html_e( 'Nome della rete', 'multisite-manager' ); ?></label><input id="msm-network-name" name="network_name" type="text" maxlength="160" required value="<?php echo esc_attr( $name ); ?>"></p>
                    <p class="msm-field"><label for="msm-network-email"><?php esc_html_e( 'Email amministrativa della rete', 'multisite-manager' ); ?></label><input id="msm-network-email" name="network_email" type="email" required value="<?php echo esc_attr( $email ); ?>"></p>
                    <p class="msm-check-option"><label><input type="checkbox" name="backup_confirmed" value="1" required> <?php esc_html_e( 'Ho un backup completo di file e database e ho verificato come ripristinarlo.', 'multisite-manager' ); ?></label></p>
                    <p class="msm-check-option"><label><input type="checkbox" name="hosting_confirmed" value="1" required> <?php esc_html_e( 'Ho verificato con l’hosting requisiti Multisite, permessi e passaggi server necessari; procederò prima in staging.', 'multisite-manager' ); ?></label></p>
                    <p class="msm-fine"><?php esc_html_e( 'Sono tue conferme operative, non verifiche automatiche del plugin. Salvare il piano non converte il sito.', 'multisite-manager' ); ?></p><button type="submit" class="msm-button"><?php echo esc_html( $plan ? __( 'Rivedi e salva il piano', 'multisite-manager' ) : __( 'Salva il piano', 'multisite-manager' ) ); ?></button></form><?php endif; ?>
                </section><aside class="msm-panel msm-setup-panel"><p class="msm-eyebrow"><?php esc_html_e( 'EFFETTI DA CONOSCERE', 'multisite-manager' ); ?></p><h2><?php esc_html_e( 'URL e privilegi', 'multisite-manager' ); ?></h2><p><?php esc_html_e( 'I siti avranno percorsi come /sito-a/ e /sito-b/. Il sito principale può passare alla struttura:', 'multisite-manager' ); ?></p><code class="msm-code">/blog/%year%/%monthnum%/%day%/%postname%/</code><p><?php echo esc_html( sprintf( __( 'Amministratori del sito rilevati: %s. Il core può promuoverli a super amministratori e includere l’utente associato all’email scelta.', 'multisite-manager' ), null === $facts['admin_count'] ? __( 'non verificabile', 'multisite-manager' ) : (string) $facts['admin_count'] ) ); ?></p><p><?php esc_html_e( 'Controlla l’elenco utenti e predisponi eventuali redirect prima del passaggio.', 'multisite-manager' ); ?></p></aside></div>
                <?php if ( $plan && 'awaiting_native_setup' === $phase ) : ?><section class="msm-panel msm-setup-panel"><h2><?php esc_html_e( '2. Prosegui con WordPress', 'multisite-manager' ); ?></h2><ol><li><?php esc_html_e( 'Scarica il piano. Contiene nome ed email da usare nel setup nativo e resta consultabile fuori da WordPress.', 'multisite-manager' ); ?></li><li><?php if ( $facts['wp_allow_multisite'] ) { esc_html_e( 'L’accesso al setup è già abilitato.', 'multisite-manager' ); } else { esc_html_e( 'Abilita WP_ALLOW_MULTISITE nella configurazione effettiva, seguendo la guida esportata. Non duplicare costanti esistenti.', 'multisite-manager' ); } ?></li><li><?php esc_html_e( 'Disattiva tutti i plugin, incluso Multisite Manager. Apri Strumenti → Configurazione della rete, inserisci i dati salvati e scegli sottodirectory se disponibile.', 'multisite-manager' ); ?></li><li><?php esc_html_e( 'Applica le istruzioni del core per configurazione e rewrite. Accedi di nuovo, riattiva Multisite Manager nella rete e torna a questo setup.', 'multisite-manager' ); ?></li></ol><?php if ( $facts['wp_allow_multisite'] ) : ?><p><a href="<?php echo esc_url( admin_url( 'network.php' ) ); ?>"><?php esc_html_e( 'Apri il setup nativo (richiede prima la disattivazione dei plugin)', 'multisite-manager' ); ?></a></p><?php endif; ?></section><?php endif; ?>
            <?php endif; ?>
            <?php if ( $plan ) : ?><section class="msm-panel msm-setup-panel"><h2><?php esc_html_e( 'Il tuo piano salvato', 'multisite-manager' ); ?></h2><p><strong><?php echo esc_html( $plan['network_name'] ); ?></strong> · <?php echo esc_html( $plan['network_email'] ); ?></p><?php self::form_open( 'export' ); ?><button type="submit" class="msm-button"><?php esc_html_e( 'Scarica piano e istruzioni', 'multisite-manager' ); ?></button></form><p class="msm-fine"><?php esc_html_e( 'L’esportazione contiene l’email amministrativa. Condividila solo con persone autorizzate. Il piano non certifica il completamento della conversione.', 'multisite-manager' ); ?></p></section><?php endif; ?>
        </div>
        <?php
    }
}
