<?php
/** Run with php tests/setup-policy.php from this build or the project root. */
define( 'ABSPATH', __DIR__ . '/' );
function wp_json_encode( $value ) { return json_encode( $value ); }
require ( isset( $argv[1] ) ? $argv[1] : dirname( __DIR__ ) . '/plugins/multisite-manager' ) . '/includes/class-setup.php';

$checks = 0;
function expect_same( $expected, $actual, $label ) {
    global $checks;
    ++$checks;
    if ( $expected !== $actual ) { fwrite( STDERR, 'FAIL ' . $label . ': ' . var_export( $actual, true ) . "\n" ); exit( 1 ); }
}
$facts = array(
    'multisite'=>false, 'subdomain_install'=>false, 'custom_topology'=>false,
    'topology_verified'=>true, 'network_count'=>0, 'network_db_state'=>'none',
    'wp_allow_multisite'=>false, 'active_plugins_count'=>1,
    'wp_version'=>'7.1.4', 'php_version'=>'7.4.33', 'site_url'=>'https://example.com',
    'home_url'=>'https://example.com', 'old_published_count'=>0,
    'native_subdirectory_allowed'=>true, 'do_not_upgrade_global_tables'=>false,
    'config_location'=>'root', 'config_readable'=>true, 'config_writable'=>false,
    'custom_content_dir'=>false, 'admin_count'=>1, 'permalink_structure'=>'/%postname%/',
);
expect_same( 'plan_required', MSM\Setup::phase( $facts, array() ), 'Fresh site requires plan' );
$plan = array( 'fingerprint'=>MSM\Setup::fingerprint( $facts ) );
expect_same( 'awaiting_native_setup', MSM\Setup::phase( $facts, $plan ), 'Saved plan current' );
foreach ( array( 'wp_allow_multisite'=>true, 'active_plugins_count'=>0 ) as $key=>$value ) {
    $changed = $facts; $changed[$key] = $value;
    expect_same( $plan['fingerprint'], MSM\Setup::fingerprint( $changed ), 'Expected handoff change excluded: ' . $key );
}
foreach ( array( 'permalink_structure'=>'/blog/%postname%/', 'admin_count'=>2, 'home_url'=>'https://changed.example.com', 'old_published_count'=>1 ) as $key=>$value ) {
    $changed = $facts; $changed[$key] = $value;
    expect_same( 'plan_stale', MSM\Setup::phase( $changed, $plan ), 'Changed evidence invalidates: ' . $key );
}
$recovery = $facts; $recovery['network_db_state'] = 'present'; $recovery['network_count'] = 1;
expect_same( 'database_prepared', MSM\Setup::phase( $recovery, $plan ), 'Database recovery without WP_ALLOW_MULTISITE' );
$partial = $facts; $partial['network_db_state'] = 'unknown';
expect_same( 'assessment_incomplete', MSM\Setup::phase( $partial, $plan ), 'Partial database blocks setup' );
$network = $recovery; $network['multisite'] = true;
expect_same( 'network_active_routing_pending', MSM\Setup::phase( $network, $plan ), 'Internal active network never certifies routing' );
$network['topology_verified'] = false;
expect_same( 'assessment_incomplete', MSM\Setup::phase( $network, $plan ), 'Unknown topology' );
foreach ( array( 'network_count'=>2, 'custom_topology'=>true, 'subdomain_install'=>true ) as $key=>$value ) {
    $changed = $network; $changed[$key] = $value;
    expect_same( 'unsupported', MSM\Setup::phase( $changed, $plan ), 'Known unsupported overrides incomplete: ' . $key );
}
$network['topology_verified'] = true; $network['network_count'] = 0;
expect_same( 'assessment_incomplete', MSM\Setup::phase( $network, $plan ), 'Missing network data cannot certify active state' );
echo "PASS: $checks setup policy checks\n";
