# Setup guidato 0.2.0 — contract

Plugin source: `plugins/multisite-manager`.
Page: `admin.php?page=multisite-manager-setup` (network admin after conversion).
Authorization: same as diagnostics: `setup_network` single-site; super admin AND `manage_network_options` on network. All unauthenticated action routes deny. GET page is read-only.

## POST routes
All forms target `admin_url('admin-post.php')`. Require session, current capability, POST, nonce field `msm_setup_nonce` whose action equals the POST action value. GET action returns 405, unauthorized returns 403. No redirect on failures.

- `action=msm_setup_save`: `network_name` (sanitized, nonempty, <=160 bytes), `network_email` (sanitized and valid), `backup_confirmed=1`, `hosting_confirmed=1`, `msm_fingerprint` (hidden current observation fingerprint). New blocked evidence or stale form returns 409. Missing/invalid fields return 400. Saves plan then redirects to setup.
- `action=msm_setup_recheck`: recollect facts; if plan exists persist last_checked_at and last_observed_phase. Never silently refresh original fingerprint. Redirects to setup; UI derives phase again live.
- `action=msm_setup_export`: requires saved plan, text attachment `multisite-manager-piano.txt`, no cache and nosniff. Contains selected name and email and diagnostics guide, privacy warning. Does not export config contents/secrets or existing user identities.

## Persistence and phases
Option `msm_setup_plan_v1` in current site options before conversion, main site's options via get_blog_option/update_blog_option after conversion. No usermeta or sitewide copy. Persists through plugin deactivation and network activation. Shape: version(integer1), network_name, network_email, fingerprint, owner_site_id, created_by(user ID), saved_at(UTC ISO8601), backup_confirmed(true), hosting_confirmed(true); recheck adds last_checked_at and last_observed_phase. No uninstall removal, explicitly documented.

Public helpers in MSM\\Setup: plan(), owner_id(), fingerprint(facts), phase(facts,plan), blockers(facts), url().

`data-setup-phase` overview values:
- plan_required: no saved plan
- awaiting_native_setup: observation fingerprint still matches
- plan_stale: observations changed; explicitly review/confirm/save again
- database_prepared: actual network records while multisite false; recovery, not another conversion
- assessment_incomplete: database unknown or active network topology unknown
- unsupported: active subdomain/custom/multiple network
- network_active_routing_pending: supported internal network state; no HTTP routing certification

Fingerprint hashes only allowlisted observations (versions, topology, DB state, public URLs, counts, native content gate, config location/read/write booleans, custom content flag, permalink). It is not a hash of configuration file contents. Excludes active-plugin count and WP_ALLOW_MULTISITE because they must change during native handoff. A plan cannot be saved while native content gate or unknown topology blocks it. Checkbox values are administrator statements, not verified backup/hosting facts.

UI links to a new native installer only when WP_ALLOW_MULTISITE true; recovery of an existing database network exposes native instructions even with that constant false; requires manual plugin deactivation including this plugin. No auto config writes, conversion, plugin deactivation, new sites or external HTTP requests. After native conversion and network activation the saved plan reappears. Network panel lists up to20 site frontend/admin links and native Sites management; manual routing/media/login/privilege checklist remains pending.

## Parent validation
PHP syntax + existing regression suite, valid save/export, wrong nonce/capability/GET/missing checkbox/email/name/fingerprint, stale-plan recheck stays stale, main-site plan survives native form conversion and network activation, partial DB/unsupported topology states, no config modification by plugin. Browser UI before/after conversion. Version support is declared, not a claim of full matrix testing.

Inspector conservatively treats an empty existing site table or orphan blogs/sitemeta tables as unknown (assessment incomplete), rather than a fresh conversion candidate.
