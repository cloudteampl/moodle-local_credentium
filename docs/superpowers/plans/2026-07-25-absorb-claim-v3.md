# Absorb Credentium Claim into local_credentium v3.0.0 — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans
> (inline execution — the port requires holding both codebases in context; a fresh
> subagent per task would re-read ~7k lines each time). Steps use checkbox syntax.

**Goal:** Fold the entire `local_credentiumclaim` 1.5.2 feature set into
`local_credentium` as v3.0.0 with data/config migration, so clients run one plugin.

**Architecture:** Mechanical port of the Claim classes/pages/tests into the
`local_credentium` component under the renaming map of the design spec §5
(`docs/superpowers/specs/2026-07-25-absorb-claim-into-credentium-design.md` — the
authoritative rename table), plus new glue: `claim_config` (reads own config),
merged settings form, merged privacy/observer, upgrade migration, cross-linked
reports.

**Tech Stack:** Moodle 4.5–5.0 plugin APIs (XMLDB, Hooks API, MUC, Message API,
Privacy API, scheduled tasks), PHPUnit via moodle-plugin-ci on GitHub Actions.

## Global Constraints

- Source of ported code: `/Users/michalkarski/git/ct-credentium-moodle-claim` (v1.5.2).
- Rename map: design spec §5 — apply to every ported identifier; repo-wide
  `grep -ri credentiumclaim` must end up clean except CHANGELOG, docs/, and the
  upgrade/uninstall-advice code that must name the legacy component.
- Claim logging goes through existing `local_credentium_log()`; never `debugging()`.
- `version.php`: `2026072500` / `'3.0.0'` / requires `2024100700` / supported `[405, 500]`.
- New DB table name: `local_credentium_claims` (≤28 chars).
- Every task ends with `php -l` on touched files; commit per task.

---

### Task 1: Core claim classes + db plumbing
**Files:** Create `classes/local/claimable.php`, `classes/local/claim_config.php`,
`classes/api/claim_client.php`, `classes/local/status_refresher.php`,
`classes/local/notifier.php`, `classes/local/hook_callbacks.php`,
`classes/output/banner.php`, `classes/task/sync_claim_status.php`,
`db/caches.php`, `db/hooks.php`, `templates/banner.mustache`.
Modify `db/tasks.php` (+sync task), `db/messages.php` (+`credentialready`),
`db/access.php` (+`local/credentium:claim`), `db/install.xml` (+claims table),
`lib.php` (+claim helpers per spec §5).
**Interfaces produced:** `claimable::TABLE = 'local_credentium_claims'`;
`claim_config::{is_configured,is_usable,global_credentials,for_course,normalize_base_url,settings_url}`;
`claim_client` (same public surface as Claim's `api\client`);
task class `\local_credentium\task\sync_claim_status`;
lib: `local_credentium_claim_enabled()`, `local_credentium_show_banner()`,
`local_credentium_get_claim_sync_task()`, `local_credentium_claim_sync_lock_resource()`,
`local_credentium_claim_sync_interval_options()`, `local_credentium_get_claim_sync_interval()`,
`local_credentium_apply_claim_sync_interval(int)`, `local_credentium_claim_action_button(int,string,string)`,
`local_credentium_claim_run_error_advice($kind)`, `local_credentium_claim_run_error_message()`,
`local_credentium_myprofile_navigation(...)`.
`claim_config` new implementation core:

```php
public static function global_credentials(): ?\stdClass {
    return self::build((string) get_config('local_credentium', 'apiurl'),
                       (string) get_config('local_credentium', 'apikey'));
}
public static function for_course(?int $courseid): ?\stdClass {
    if (empty($courseid)) { return self::global_credentials(); }
    // cache … then:
    try {
        $config = local_credentium_resolve_category_config($courseid);
        $resolved = is_object($config)
            ? self::build((string)($config->apiurl ?? ''), (string)($config->apikey ?? ''))
            : null;
    } catch (\Throwable $e) { $resolved = null; }
    return self::$coursecache[$courseid] = $resolved ?? self::global_credentials();
}
```
(no `is_installed()` / `load_connector()` — same plugin now; `is_usable()` =
`is_configured() || !empty(get_config('local_credentium','categorymode'))`).
- [ ] Port each file applying the rename map; run `php -l`; commit.

### Task 2: User-facing pages
**Files:** Create `mycredentials.php`, `claim.php`, `dismiss.php`, `claimsyncnow.php`
(ported from `mycredentials.php`, `claim.php`, `dismiss.php`, `syncnow.php`).
**Consumes:** Task 1 classes + lib helpers; capability `local/credentium:claim`.
- [ ] Port with renames (URLs `/local/credentium/...`, `claimsyncnow.php` reads
  `claimlastrun*` config keys, uses `claimreport.php` as return URL); lint; commit.

### Task 3: Settings + test connection
**Files:** Modify `admin_settings.php` (claim section in the moodleform: header,
`claimenabled` checkbox, `syncinterval` select incl. "Custom", `showbanner`
advcheckbox, all hidden while master `enabled` unchecked; save handler persists
`claimenabled`/`showbanner` and applies the interval; legacy-plugin warning box when
`/local/credentiumclaim/version.php` exists), `testconnection.php` (append
read-scope probe via `claim_client` after each successful template probe),
`settings.php` (+`local_credentium_claimreport` admin_externalpage under reports).
- [ ] Implement; lint; commit.

### Task 4: Claim status report + tabs
**Files:** Create `claimreport.php` (port of Claim's `index.php`, reads
`claimlastrun*`, buttons → `claimsyncnow.php`); modify `index.php` (+tabtree with
two tabs when user may view both pages).
- [ ] Implement; lint; commit.

### Task 5: Privacy + observer merge
**Files:** Modify `classes/privacy/provider.php` (claims-table metadata + claim-API
location fields `issuerequestid`/`locale`; user-context export of claims; user-context
delete also removes claims + purges cache), `classes/observer.php::user_deleted`
(+`$DB->delete_records(claimable::TABLE, …)` + `claimable::purge_cache`).
- [ ] Implement; lint; commit.

### Task 6: Install/upgrade/version
**Files:** Modify `db/install.php` (`set_config('showbanner', 1, 'local_credentium')`),
`db/upgrade.php` (savepoint `2026072500`), `version.php`.
Upgrade step core:

```php
if ($oldversion < 2026072500) {
    // 1. Claims table via xmldb (mirror install.xml).
    // 2. Data migration from the legacy plugin, if present:
    if ($dbman->table_exists('local_credentiumclaim_status')) {
        $rs = $DB->get_recordset('local_credentiumclaim_status');
        foreach ($rs as $old) {
            if ($DB->record_exists('local_credentium_claims',
                    ['userid' => $old->userid, 'credentialkey' => $old->credentialkey])) {
                continue; // Re-run safety.
            }
            unset($old->id);
            $DB->insert_record('local_credentium_claims', $old);
        }
        $rs->close();
    }
    // 3. Config migration (only when the legacy component has config).
    $map = ['enabled' => 'claimenabled', 'showbanner' => 'showbanner',
            'lastrun' => 'claimlastrun', /* …all lastrun* keys per spec §5 */];
    foreach ($map as $oldkey => $newkey) {
        $value = get_config('local_credentiumclaim', $oldkey);
        if ($value !== false && get_config('local_credentium', $newkey) === false) {
            set_config($newkey, $value, 'local_credentium');
        }
    }
    // 4. Neutralise the legacy plugin so surfaces cannot double up.
    if (get_config('local_credentiumclaim', 'enabled') !== false) {
        set_config('enabled', 0, 'local_credentiumclaim');
    }
    upgrade_plugin_savepoint(true, 2026072500, 'local', 'credentium');
}
```
- [ ] Implement; lint; commit.

### Task 7: Language packs (EN + PL)
**Files:** Modify `lang/en/local_credentium.php`, `lang/pl/local_credentium.php` —
merge all Claim strings under the renames of spec §5; add the missing plain keys the
main client already throws (`apinotconfigured`, `apierror` → keep existing usage,
`invalidjsonresponse`, `invalidresponse`, `issuancefailed`); add
`uninstalllegacyclaim` warning string. Both packs stay key-complete and sorted.
- [ ] Implement; verify EN/PL key sets identical via a diff script; commit.

### Task 8: Tests
**Files:** Create `tests/claim_client_test.php`, `tests/claimable_test.php`,
`tests/sync_claim_status_test.php`, `tests/status_refresher_test.php`,
`tests/claim_config_test.php`, `tests/banner_test.php`, `tests/notify_test.php`,
`tests/claim_privacy_test.php`, `tests/claim_sync_interval_test.php` — ports of the
nine Claim suites with the rename map; discovery seeding now uses the plugin's own
`local_credentium_issuances` schema directly.
- [ ] Port; lint; commit. (Execution happens on CI — no local Moodle.)

### Task 9: CI + packaging
**Files:** Create `.github/workflows/ci.yml` (from Claim repo, adjusted: component
`local_credentium`, phplint + phpunit jobs); modify `deploy.sh`
(+`--exclude='docs'`, critical files += `db/hooks.php`, `templates/banner.mustache`,
`mycredentials.php`, `claimreport.php`).
- [ ] Implement; commit.

### Task 10: Docs
**Files:** Modify `README.md` (claim feature section, scopes note), `CHANGELOG.md`
(3.0.0 entry with upgrade notes), `CLAUDE.md` (architecture: claim subsystem, new
table, new pages/tasks).
- [ ] Implement; commit.

### Task 11: Verification gate
- [ ] `find . -name '*.php' | xargs php -l` clean.
- [ ] `grep -ri credentiumclaim --exclude-dir=.git --exclude-dir=docs .` → only
  CHANGELOG + upgrade/uninstall-advice references.
- [ ] Cross-check every `get_string` call in ported files resolves to a lang key
  (scripted). Push branch; CI must be green.

### Task 12: Review loop → release
- [ ] Code-review agent until no significant findings (fix + re-run).
- [ ] Merge to master (no-ff), push, tag `v3.0.0`, verify deploy workflow published
  the Release ZIP + checksums.
- [ ] Remove worktree + branch.
