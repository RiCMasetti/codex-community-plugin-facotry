<?php
namespace MSM;
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Inspector {
    public static function collect() {
        global $wpdb, $wp_version;
        require_once ABSPATH . 'wp-admin/includes/network.php';
        $multisite = is_multisite();
        $old_suppress = $wpdb->suppress_errors( true );
        $old_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_date < DATE_SUB(NOW(), INTERVAL 1 MONTH)" );
        $old_count = $wpdb->last_error ? null : ( null === $old_count ? null : (int) $old_count );
        $native = null;
        if ( ! $multisite && null !== $old_count ) {
            $native = allow_subdirectory_install();
            if ( $wpdb->last_error ) { $native = null; }
        }
        $network_table = $wpdb->base_prefix . 'site';
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $network_table ) ) );
        $network_state = $wpdb->last_error ? 'unknown' : 'none';
        $network_count = 'unknown' === $network_state ? null : 0;
        if ( $found && 'unknown' !== $network_state ) {
            $network_count = $wpdb->get_var( "SELECT COUNT(*) FROM `" . str_replace( '`', '``', $network_table ) . "`" );
            if ( $wpdb->last_error || null === $network_count ) {
                $network_state = 'unknown';
                $network_count = null;
            } else {
                $network_count = (int) $network_count;
                $network_state = $network_count > 0 ? 'present' : 'unknown'; // Existing empty network tables may indicate an interrupted setup.
            }
        }
        // Orphaned network tables must not look like a fresh single-site install.
        if ( 'none' === $network_state ) {
            foreach ( array( 'blogs', 'sitemeta' ) as $suffix ) {
                $partial = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->base_prefix . $suffix ) ) );
                if ( $partial || $wpdb->last_error ) { $network_state = 'unknown'; $network_count = null; break; }
            }
        }
        $admins = new \WP_User_Query( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1, 'count_total' => true ) );
        $admin_count = $wpdb->last_error ? null : (int) $admins->get_total();
        $wpdb->suppress_errors( $old_suppress );

        $config = ABSPATH . 'wp-config.php';
        $location = 'root';
        if ( ! is_file( $config ) ) {
            $config = dirname( rtrim( ABSPATH, '/\\' ) ) . '/wp-config.php';
            $location = is_file( $config ) ? 'parent' : 'unknown';
        }
        $server_string = isset( $_SERVER['SERVER_SOFTWARE'] ) ? (string) $_SERVER['SERVER_SOFTWARE'] : '';
        $server = false !== stripos( $server_string, 'apache' ) ? 'apache' : ( false !== stripos( $server_string, 'nginx' ) ? 'nginx' : 'unknown' );
        $custom_topology = defined( 'SUNRISE' );
        $topology_verified = 'unknown' !== $network_state;
        if ( $multisite && ! is_subdomain_install() ) {
            $network = get_network();
            $network_domain = $network ? $network->domain : '';
            $previous_errors = $wpdb->suppress_errors( true );
            $sites = get_sites( array( 'number' => 101, 'network_id' => get_current_network_id() ) );
            if ( $wpdb->last_error || ! $network ) { $topology_verified = false; }
            $wpdb->suppress_errors( $previous_errors );
            // Sampling cannot certify an arbitrary network: larger networks remain unverified.
            if ( count( $sites ) > 100 ) { $topology_verified = false; }
            foreach ( $sites as $site ) {
                if ( $site->domain !== $network_domain ) { $custom_topology = true; break; }
            }
        }
        return array(
            'wp_version' => (string) $wp_version,
            'php_version' => PHP_VERSION,
            'db_version' => (string) $wpdb->db_version(),
            'multisite' => $multisite,
            'subdomain_install' => $multisite && is_subdomain_install(),
            'network_count' => $network_count,
            'custom_topology' => $custom_topology,
            'topology_verified' => $topology_verified,
            'site_url' => self::public_url( get_option( 'siteurl' ) ),
            'home_url' => self::public_url( get_option( 'home' ) ),
            'https' => is_ssl(),
            'authorized' => Admin::allowed(),
            'old_published_count' => $old_count,
            'native_subdirectory_allowed' => $native,
            'network_db_state' => $network_state,
            'active_plugins_count' => count( (array) get_option( 'active_plugins', array() ) ),
            'wp_allow_multisite' => defined( 'WP_ALLOW_MULTISITE' ) && WP_ALLOW_MULTISITE,
            'do_not_upgrade_global_tables' => defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ),
            'config_location' => $location,
            'config_readable' => 'unknown' !== $location && is_readable( $config ),
            'config_writable' => 'unknown' !== $location && is_writable( $config ),
            'custom_content_dir' => wp_normalize_path( WP_CONTENT_DIR ) !== wp_normalize_path( ABSPATH . 'wp-content' ),
            'server' => $server,
            'admin_count' => $admin_count,
            'permalink_structure' => (string) get_option( 'permalink_structure' ),
        );
    }

    private static function public_url( $url ) {
        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) { return ''; }
        return $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' ) . ( isset( $parts['path'] ) ? $parts['path'] : '' );
    }
}
