# Contract — Multisite Manager 0.1.0

PHP classes namespace MSM; static methods. Load main plugin under WordPress, or class-policy.php/class-guide.php with WP functions stubbed for isolated tests.

- `Inspector::collect(): array` reads environment; no application database/config mutations, no external requests.
- `Policy::evaluate(array $facts): array` deterministic and mutation-free. Missing fields use conservative defaults. Uses WP translation, URL parsing and untrailingslashit helpers.
- `Guide::render(array $report): string` text export using allowlisted facts and check evidence. Omits site URLs; never reads raw config. Inspector must not pass secrets as facts.
- `Admin::allowed()` and `Admin::can_access()` bool; single site `setup_network`, multisite `is_super_admin() && manage_network_options`.

Facts: wp_version, php_version, db_version (strings), multisite/subdomain_install/custom_topology/https/authorized/wp_allow_multisite/do_not_upgrade_global_tables/config_readable/config_writable/custom_content_dir (bool), network_count (int|null), topology_verified (bool), site_url/home_url (credential/query/fragment-redacted strings), old_published_count/admin_count (int|null; null = failed/unknown), native_subdirectory_allowed (bool|null), network_db_state ('none','present','unknown'), active_plugins_count (int), config_location ('root','parent','unknown'), server ('apache','nginx','unknown'), permalink_structure string.

Report: overall {status,title,summary}, checks [{id,label,status,evidence,action}], facts merged with defaults. Check/overall statuses: pass, action, unsupported, unknown. Overall priority unsupported > unknown > action > pass. Host policy, DDL permissions, routing and path conflicts remain unknown in v0.1, so no full readiness claim.

Check IDs: wordpress, php, database, topology, permissions; on single site network_state, published_content, global_tables, plugins, enable_network, privileges; then configuration, content_path, urls, https, rewrite, host_policy, path_conflicts.

UI: `/wp-admin/admin.php?page=multisite-manager` for single site; `/wp-admin/network/admin.php?page=multisite-manager` for network. Asset hook only this page.
Export: `POST /wp-admin/admin-post.php`, action=msm_export_guide, nonce field `msm_nonce`, nonce action `msm_export_guide`. Forbidden authenticated/nopriv =>403; authorized GET=>405; invalid nonce=>403. Content-Type text/plain UTF-8; attachment multisite-manager-guida.txt; no-cache/nosniff.

No REST routes, AJAX, JavaScript runtime, activation mutations, uninstall deletions, vendor dependencies or telemetry. CSS local responsive, native details/summary for guide. Policy branches need fixture tests; actual Inspector + access/export need WP runtime integration.

Missing observations: absent or null facts are unknown, including boolean/zero-valued checks. Rendering defaults do not constitute observations. An empty facts array must produce no pass/action/unsupported checks (all unknown); explicit false is distinct from missing.
