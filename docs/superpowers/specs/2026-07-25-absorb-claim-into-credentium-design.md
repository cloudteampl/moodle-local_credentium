# Design Spec — Absorb `local_credentiumclaim` into `local_credentium` v3.0.0

- **Date:** 2026-07-25
- **Status:** Approved (user pre-approved the direction conditionally on the technical
  finding below; execution is autonomous per the user's instruction)
- **Author:** CloudTeam / Claude
- **Source plugin:** `local_credentiumclaim` 1.5.2 (`ct-credentium-moodle-claim`)
- **Target plugin:** `local_credentium` 2.0.7 → **3.0.0** (this repository)

---

## 1. The question that started this

> Is there any Moodle-technical reason the claim/reminder functionality must live in
> a separate plugin from the issuing plugin?

**Answer: No.** Both plugins are of type `local`. A single `local` plugin may register
everything the pair uses today: event observers, scheduled + adhoc tasks, output hooks
(banner), user-menu hooks, message providers, MUC cache definitions, capabilities,
admin pages, and user-facing pages. The original separation was a *project* decision
("do not modify the sibling; depend on it read-only"), not a platform constraint — and
it has since eroded to the point of fiction: Claim 1.5.x already reads
`local_credentium_issuances` directly and inherits the connector's API URL/key. The
two plugins are one system shipped as two packages, which doubles installation,
versioning, and support surface for clients.

## 2. Goal

Fold the entire working Claim feature set into `local_credentium` as a major release
(**3.0.0**), preserving behaviour and data, so clients install and upgrade exactly one
plugin. The old plugin, where installed, is neutralised on upgrade and reported to the
admin for uninstallation.

## 3. Non-goals

- No behavioural redesign of either feature (issuance flow and claim flow keep their
  current logic, timings, and safeguards).
- No change to how the global API key is stored (plaintext config today; category keys
  stay encrypted). Changing that is a separate task.
- No changes to the `ct-credentium-moodle-claim` repository (a final deprecation
  release there is a follow-up recommendation, not part of this change).

## 4. Architecture after the merge

```
                       local_credentium 3.0.0 (one plugin)
┌──────────────────────────────────────────────────────────────────────────┐
│ ISSUE (unchanged)                       CLAIM (absorbed from 1.5.2)      │
│ observer: course_completed              task: sync_claim_status (15 min) │
│ adhoc: issue_credential                   discovery ← {issuances} (own!) │
│ tables: issuances, course_config,         poll v2 /statuses per API key  │
│         templates_cache, category_config  table: {local_credentium_claims}│
│ client: api\client (v1 issue)           client: api\claim_client (v2)    │
│                                         hooks: banner + user menu        │
│                                         pages: mycredentials/claim/      │
│                                                dismiss/claimsyncnow      │
│                                         notifier: message provider       │
│                                                   'credentialready'      │
└──────────────────────────────────────────────────────────────────────────┘
        one settings page (issue section + claim section, one Test connection)
        two admin report pages under Reports, cross-linked with tabs
```

Key simplification: `connector_config` (the Claim class that reverse-engineered the
sibling's configuration) is replaced by `local\claim_config`, which reads **its own
plugin's** config and calls `local_credentium_resolve_category_config()` directly —
no `file_exists` probing, no cross-plugin `require_once`. The `/api`-suffix
normalisation stays (the issue endpoint uses `{apiurl}/credential/issue`, the v2
surface uses `{base}/api/...`).

## 5. Renaming map (Claim → merged)

### Component & files
| Claim (1.5.2) | Merged (3.0.0) |
|---|---|
| component `local_credentiumclaim` | `local_credentium` |
| `classes/api/client.php` | `classes/api/claim_client.php` (`\local_credentium\api\claim_client`) |
| `classes/local/claimable.php` | `classes/local/claimable.php` |
| `classes/local/connector_config.php` | `classes/local/claim_config.php` (reimplemented against own config) |
| `classes/local/hook_callbacks.php` | `classes/local/hook_callbacks.php` |
| `classes/local/notifier.php` | `classes/local/notifier.php` |
| `classes/local/status_refresher.php` | `classes/local/status_refresher.php` |
| `classes/output/banner.php` | `classes/output/banner.php` |
| `classes/task/sync_status.php` | `classes/task/sync_claim_status.php` |
| `classes/event/observer.php` (user_deleted) | merged into existing `classes/observer.php::user_deleted` |
| `classes/privacy/provider.php` | merged into existing provider (claims table + claim API location) |
| `mycredentials.php`, `claim.php`, `dismiss.php` | same names at repo root |
| `syncnow.php` | `claimsyncnow.php` (avoids ambiguity with issuance processing tools) |
| `index.php` (claim status report) | `claimreport.php` (admin_externalpage `local_credentium_claimreport`) |
| `testconnection.php` (read-scope probe) | folded into existing `testconnection.php` |
| `templates/banner.mustache` | `templates/banner.mustache` (`local_credentium/banner`) |
| `admin_settings.php` + form | folded into existing `admin_settings.php` form as a "Claim" section |

### Database
| Claim | Merged |
|---|---|
| `local_credentiumclaim_status` | `local_credentium_claims` (identical fields/keys/indexes; 23 chars, under the XMLDB 28-char limit) |

### Capabilities
| Claim | Merged |
|---|---|
| `local/credentiumclaim:claim` | `local/credentium:claim` (new; CONTEXT_USER, user → CAP_ALLOW) |
| `local/credentiumclaim:viewreports` | existing `local/credentium:viewreports` |

### Config keys (component `local_credentium`)
| Claim | Merged |
|---|---|
| `enabled` | `claimenabled` |
| `showbanner` | `showbanner` |
| `debuglog` | existing `debuglog` (one switch for the whole plugin) |
| `lastrun`, `lastrunresult`, `lastrunpolled`, `lastrunupdated`, `lastrununmatched`, `lastrununanswered`, `lastrununresolved`, `lastrunnotified`, `lastrunnotifyfailed`, `lastrunerror`, `lastrunerrorkind` | same names prefixed `claim` (e.g. `claimlastrunresult`) |

### Other identifiers
- Cache: `local_credentium/claimable` (new `db/caches.php`).
- Lock factory type: `local_credentium_claims`; the task lock resource derives from the
  new task classname via `\core\task\manager::get_canonical_class_name()` as before.
- Message provider: `credentialready` (added next to `credentialissuance`).
- Lang strings: all Claim strings move across; renames only where they collide:
  `enabled*` → `claimenabled*`, `settings_desc` → `claimsettings_desc`,
  `status_*` (claim vocabulary) → `claimstatus_*`, `report` → `claimreport`,
  `globalsettings` dropped (section heading `claimsettings_heading` instead),
  `error:notconfigured` → `error:claimnotconfigured`, `apierror` → `error:claimapifailed`,
  `testconnection_fail`/`_success`/`_disabled`/`_heading` → main's existing equivalents.
  Claim's `debuglog*`, `error:apinotconfigured`, `error:invalidapiurl` are dropped in
  favour of main's. PL translations move with them (both packs stay complete).
- Logging: Claim's `local_credentiumclaim_log()` (debugging()-channel) is replaced by
  the existing `local_credentium_log()` (error_log-channel, gated by `debuglog`) —
  one diagnostics pipeline, already documented as the secret-safe one.
- `lib.php` functions gain the `local_credentium_` prefix, e.g.
  `local_credentium_claim_sync_interval_options()`,
  `local_credentium_apply_claim_sync_interval()`, `local_credentium_show_banner()`,
  `local_credentium_claim_action_button()`, `local_credentium_claim_run_error_message()`,
  and `local_credentium_myprofile_navigation()` (profile-page node; the main plugin
  defines none today, so the core callback name is free).

## 6. Feature gating

`local_credentium_claim_enabled()` = master `enabled` **AND** `claimenabled`. The claim
section of the settings form is hidden while the master switch is off. Rationale: the
master switch has always meant "the Credentium integration is on"; a disabled
integration must not keep nudging learners.

## 7. Settings UI

One `admin_settings.php` form, three groups:
1. Existing integration settings (untouched).
2. **Claim section** (new header): `claimenabled`, `syncinterval` select (5 min–4 h +
   "Custom" when the cron expression was hand-edited — same semantics as Claim 1.5.2),
   `showbanner`. The sync interval is still *derived from and applied to* the scheduled
   task, never stored as config.
3. Existing debug logging + data retention (`debuglog` now also governs claim
   diagnostics).

`testconnection.php` keeps its current template probe and adds the Claim read-scope
probe (`credentials:read`) with the same three-way verdict (ok / missing scope /
transient), for the global credentials and, in category mode, per configured category
exactly as it probes templates today.

## 8. Reporting

Two admin pages under Reports, cross-linked with a `tabtree`:
- **Credentium® Report** (`index.php`, existing issuance history — untouched logic).
- **Credentium® Claim status** (`claimreport.php`, ported: status breakdown,
  diagnostics table, failure-kind advice, "Check status now" button →
  `claimsyncnow.php`).

If the legacy `local_credentiumclaim` plugin is still present on disk, both the
settings page and the claim report show a warning advising uninstallation (data and
settings have been migrated; the old plugin is disabled).

## 9. Upgrade path (v2.x → 3.0.0, savepoint `2026072500`)

1. Create `local_credentium_claims` via XMLDB (also in `install.xml` for fresh installs).
2. **Data migration:** if `local_credentiumclaim_status` exists, copy all rows across
   (same columns). Preserving `dismissed`, `remotestatus` and `timechecked` is what
   prevents re-notification and banner re-appearance for learners who already acted.
   Skip rows whose (userid, credentialkey) already exist in the target (re-run safety).
3. **Config migration:** if the old component has config, map
   `enabled→claimenabled`, `showbanner→showbanner`, `lastrun*→claimlastrun*`.
   Missing old config leaves the new keys unset (claim feature stays off until
   enabled — same as a fresh install).
4. **Neutralise the old plugin:** `set_config('enabled', 0, 'local_credentiumclaim')`
   so its hooks, task and banner go dormant immediately; the admin uninstalls it at
   leisure (its uninstall drops its table and config). We never uninstall another
   plugin programmatically from an upgrade step.
5. Purge caches.
6. The custom sync-task schedule is *not* migrated (the new task registers with the
   15-minute default; the interval is a one-click setting). Documented in CHANGELOG.

Fresh installs: `db/install.php` additionally persists `showbanner = 1` (same
moodleform-default rationale as Claim's install hook).

`version.php`: `version = 2026072500`, `release = '3.0.0'`,
`requires = 2024100700` (Moodle 4.5 — the output/user-menu hooks need the Hooks API;
the plugin already only supports 4.5–5.0), `supported = [405, 500]`.

## 10. Error handling & security (carried over verbatim)

- Claim URL remains a bearer-equivalent secret: minted on click in `claim.php`,
  travels only in the redirect header, never rendered/logged; `sanitize_for_log()`
  redaction ported unchanged into `claim_client`.
- Monotonic status writes, tiered per-row locking, notification exactly-once
  semantics, retry ladder with `Retry-After`, poll budget, failure-kind
  classification: all ported unchanged (they are the hard-won core of Claim 1.5.x).
- All state-changing pages keep `require_login` + capability + `sesskey`.
- Privacy API: main provider gains the claims table metadata, user-context
  export/delete, and the claim-API external location fields; `user_deleted` observer
  now also deletes claim rows and purges the cache.

## 11. Testing

- Port all nine Claim PHPUnit suites with identifier renames
  (`claim_client_test`, `claimable_test`, `sync_claim_status_test`,
  `status_refresher_test`, `claim_config_test`, `banner_test`, `notify_test`,
  `claim_privacy_test`, `sync_interval_test`). Discovery tests get *simpler*: the
  issuances table is now the plugin's own schema, no cross-plugin seeding caveats.
- Existing main-plugin test suites stay as they are.
- Execution harness: GitHub Actions `ci.yml` (ported from the Claim repo, adjusted to
  this component/root layout) — phplint + PHPUnit on the supported Moodle/PHP matrix.
  Local machine has PHP for linting only; there is no local Moodle, so CI is the
  authoritative test run and must be green before merge.

## 12. Release & packaging

- `deploy.sh` gains `--exclude='docs'` (dev specs must not ship) — everything else
  already ships by "copy all, exclude dev files", so new files are included
  automatically. Critical-file check extended with `db/hooks.php`,
  `templates/banner.mustache`, `mycredentials.php`.
- CHANGELOG entry 3.0.0 with upgrade notes (uninstall old plugin, re-set custom sync
  interval, widen API key scope reminder).
- README + CLAUDE.md updated to describe the absorbed claim feature.
- Tag `v3.0.0` on master → existing deploy workflow builds and publishes the
  installable ZIP with checksums.

## 13. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Both plugins active post-upgrade → double banner/notifications | Upgrade step force-disables the old component's `enabled`; all its surfaces are gated on it. Settings + report warn until it is uninstalled. |
| Data loss / re-notification on migration | Row-for-row copy preserving `dismissed`/`remotestatus`/`timechecked`; unique-key guarded; re-runnable. |
| Missed identifier rename → subtle breakage (string keys, config keys, table names) | Mechanical rename table (this doc §5) + repo-wide grep for `credentiumclaim` must return zero hits outside CHANGELOG/docs + CI. |
| Legacy main-plugin code fails new CI checks | CI limited to phplint + PHPUnit (codechecker on the legacy files is a separate clean-up task). |
| `requires` bump locks out Moodle <4.5 sites | The plugin already declares support for 4.5–5.0 only; Claim already required 4.5. |

## 14. Definition of done

- Repo-wide `grep -ri credentiumclaim` clean (except CHANGELOG/docs/upgrade code that
  must reference the old component).
- `php -l` clean; CI (phplint + PHPUnit matrix) green on the feature branch.
- Code-review agent loop: no significant findings.
- Merged to master, tag `v3.0.0` pushed, GitHub Release with installable ZIP +
  checksums published.
- Worktree and feature branch removed.
