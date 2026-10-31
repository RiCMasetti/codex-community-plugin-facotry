<?php
/**
 * Plugin Name: Multisite Manager
 * Description: Diagnostica e setup guidato per reti WordPress in sottodirectory.
 * Version: 0.2.0
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Author: WordPress Plugin Crew
 * License: GPL-2.0-or-later
 * Text Domain: multisite-manager
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/class-inspector.php';
require_once __DIR__ . '/includes/class-policy.php';
require_once __DIR__ . '/includes/class-guide.php';
require_once __DIR__ . '/includes/class-admin.php';
\MSM\Admin::boot( __FILE__ );
require_once __DIR__ . '/includes/class-setup.php';
\MSM\Setup::boot( __FILE__ );
