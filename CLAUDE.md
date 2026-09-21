# CLAUDE.md

Guidance for working on this repository. This is the **only** agent/maintainer
document (see "Documentation rules" below).

## What this is

`perxel-example` - a **public** WordPress plugin (repo
`github.com/perxel/wp-example`, WordPress.org slug `perxel-example`,
published under the `phucbm` .org account, branded Perxel).

It was scaffolded from
[`perxel/wp-plugin-starter`](https://github.com/perxel/wp-plugin-starter). If
this file still says "perxel-example" / "PXEX" / "Example", the
template tokens have not been replaced yet - see the starter README.

**Upstream rule:** the starter is the source of truth for shared process - CI,
release/deploy, WordPress.org compliance rules, `.distignore`, build scripts, and
the "Releasing" and "Compliance" sections of this file. If you improve or fix one of
those while working here, make the same change in the starter too (or tell the
maintainer), so the next plugin inherits it. Plugin-specific code and listing art
stay here.

## Documentation rules

Every Perxel plugin follows these; they are owned by the starter.

- **`README.md` is public-facing only**: what the plugin does, screenshots,
  install, requirements, what data it stores / external services, license. No
  architecture, folder layout, build/lint/release steps, or "how to extend" -
  none of that belongs on the public page.
- **`CLAUDE.md` is the one and only file for developers and agents**:
  architecture, conventions, compliance, releasing. There is **no `AGENTS.md`**
  (and no second "playbook" file) - do not recreate it or duplicate content
  across the two. Claude Code reads `CLAUDE.md`; other agents can be pointed at it.
- `readme.txt` is the WordPress.org listing, `CHANGELOG.md` (optional) the
  changelog. Neither carries developer guidance.
- Master/source art for `.wordpress-org/` lives in `.claude/assets-src/`.
- `.env.local` holds credentials: never commit it (it is in `.gitignore`).
- `bin/*.sh` derive the slug from the main plugin file, so they are byte-identical
  across plugins - never hard-code a slug in them. Per-plugin Plugin Check
  suppressions go in `lint.yml` -> `ignore-codes`.
- `languages/` is optional; `.org` auto-loads translations.

## Layout

```
perxel-example.php      Main file: header, constants, autoloader, UI-kit loader, boot
uninstall.php               Deletes the option (and any custom tables) on delete
includes/*.php              One PSR-4-ish class per concern, namespace Perxel_Example\
includes/views/*.php        Dumb admin templates, fed vars by the screen classes
assets/css, assets/js       Admin-only CSS/JS (plugin-specific; layout comes from the kit)
vendor/perxel-ui/           Shared admin-UI kit - vendored, see below
languages/                  .pot template
readme.txt                  WordPress.org listing (keep in sync with README.md + version)
README.md                   Public-facing GitHub page only (see "Documentation rules")
bin/                        build-zip.sh, update-ui.sh - identical in every plugin
.wordpress-org/             Listing assets (icon, banner, screenshots) - not shipped
.claude/assets-src/         Master/source art for the listing assets - committed, not shipped
.github/workflows/          lint.yml (PHPCS + Plugin Check), release.yml
```

`includes/` is loaded by the `spl_autoload_register` in the main file (not
Composer). `Plugin::instance()->boot()` runs on `plugins_loaded` and wires
`Admin`.

## Architecture

- **`Plugin`** - singleton. `boot()` wires the admin surface; `activate()` is
  the activation hook (seed options, create tables).
- **`Admin`** - owns the menu (one `Tools ->` screen), the shared layout args,
  asset loading, and the plain-form / `admin-post` handlers. Each screen is a
  `render_*()` method + a view under `includes/views/`; heavier per-screen logic
  goes in its own class.
- **`Settings`** - the one option (`PXEX_OPTION_KEY`), read through typed
  accessors, written through `update()` / `sanitize()`. Never call
  `get_option()` for it directly elsewhere.

### Custom tables

The template ships none. When you add them:

- One `includes/Db.php` for schema (`dbDelta` on `Plugin::activate()`, a stored
  `pxex_db_version` option, `Db::maybe_upgrade()` on `init`), and one repository
  class that is the *only* code touching the tables.
- **Bind the table name with the `%i` placeholder - never concatenate it.**
  `%i` needs WP 6.2+ (the template's floor is already 6.5).

  ```php
  // Right:
  $wpdb->get_results(
      $wpdb->prepare( 'SELECT * FROM %i WHERE run_id = %d', Db::items(), $run_id ),
      ARRAY_A
  );
  // Wrong - WordPress.DB.PreparedSQL.NotPrepared (error-level, blocks .org):
  $wpdb->get_results( "SELECT * FROM {$table} WHERE run_id = {$run_id}" );
  ```

- A `SELECT` with only the table (no other args) still goes through
  `prepare()`: `$wpdb->prepare( 'SELECT COUNT(*) FROM %i', Db::runs() )`.
- `DROP TABLE`: `$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table )`.
- Direct `$wpdb` on your own table is expected; annotate the call:
  `// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- <reason>`.
  `dbDelta()` CREATE and uninstall DROP add `WordPress.DB.DirectDatabaseQuery.SchemaChange`.
- A dynamic `IN (...)` list is the one case with no clean placeholder: build it
  with `implode( ', ', array_fill( 0, count( $ids ), '%d' ) )` and wrap that one
  statement in `// phpcs:disable ... WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber` / `// phpcs:enable`.
- Uncomment the `WordPress.DB.DirectDatabaseQuery` block in `phpcs.xml.dist`.
- Don't put a bare SQL keyword like `'create'` as a column *value* inside a
  `$wpdb->insert()`/`update()` call - PHPCS reads it as DDL. Assign it to a
  variable first.

## Conventions

- **Namespace** `Perxel_Example\` - the slug (`perxel-example`) in
  `Ucfirst_Snake` form, so `WordPress.NamingConventions.PrefixAllGlobals`
  accepts it as the plugin prefix (Plugin Check does not read `phpcs.xml.dist`,
  so a `Vendor\Package`-style namespace would be flagged there). Sub-namespaces
  are fine (`Perxel_Example\Admin\Foo` -> `includes/Admin/Foo.php`). Hooks,
  option keys and CSS classes stay `pxex_` / `pxex-`; constants `PXEX_`. Product
  name is the constant `PXEX_NAME` (no rebrand option).
- **Text domain** `perxel-example` (= the slug). JS i18n via `wp.i18n`
  (`wp_set_script_translations`); script deps include `wp-i18n`.
- **Escape at output, no blanket suppressions.** Never `phpcs:disable` a
  `WordPress.Security.*` sniff (EscapeOutput, NonceVerification) for a file or
  block - the WordPress.org review bot flags it as an escaping/nonce failure
  even when every value is escaped. Kit markup is echoed through
  `Admin::kit( \Perxel_UI::rows( ... ) )` (the one delegated `echo`); any other
  pre-escaped echo gets a per-line `// phpcs:ignore <code> -- <reason>`;
  read-only `$_GET` flags get a per-line `NonceVerification.Recommended` ignore.
  `composer run lint` runs `bin/check-suppressions.sh`, which fails on the
  blanket form.
- Admin screens render inside `Perxel_UI_Layout::open()/close()` via
  `Admin::screen()`, which falls back to a plain notice if the kit is not
  vendored. Use the kit components (`rows()`, `notice()`, `toggle()`, `code()`,
  `meter()`, `progress_bar()`, `checkbox_group()`, `card()`) rather than
  hand-rolled markup. A bare `<input type="checkbox">` renders as a square box;
  the iOS switch is `Perxel_UI::toggle()` / the `.pxui-toggle` class. Figures
  (counts, totals) are a `rows()` group - label left, value as `content` right,
  `sub` for the qualifier, `tone` for good/warn/bad.
- Forms that can lose unsaved edits carry `data-pxui-dirty-guard` (kit >=
  0.20.0).

## The `vendor/perxel-ui/` kit

Standalone repo [`perxel/wp-plugin-ui`](https://github.com/perxel/wp-plugin-ui),
vendored via `bin/update-ui.sh <version>` (curl a tagged tarball into
`vendor/perxel-ui/`, Action Scheduler style - no Composer). Committed;
`.gitignore` keeps it out of the general `vendor/` ignore, `.distignore` strips
only its dev-only `showcase/`. Overwriting it can never change plugin behaviour
- the `loader.php` "highest version wins" negotiation picks the newest copy
across every active plugin, and a second copy is inert.

The version passed to `Perxel_UI_Loader::register()` in the main file **and** the
`vendor/perxel-ui/` contents must match the tag you vendored. Update both when
you run `bin/update-ui.sh`.

We host the kit's component showcase as a hidden maintainer-only screen
(`PERXEL_UI_SHOWCASE_HOSTED` + `Admin::can_see_showcase()`), so its own Tools
page is suppressed.

## Before committing

```bash
php -l <changed files>
composer run lint          # phpcs - must stay green
composer run build         # bin/build-zip.sh - installable zip in dist/
```

`phpcs.xml.dist` curates the base `WordPress` standard: a terse-docblock house
style, and `PrefixAllGlobals` is told about both the plugin prefix and the
kit's (`perxel_ui` / `PERXEL_UI` / `Perxel_UI` / `pxui`). CI also runs the
official **Plugin Check** action against the built zip (not the raw checkout).

There are no automated tests and no WP in the lint environment - `phpcs` and
`php -l` verify syntax and style only. Behaviour must be smoke-tested on a real
WordPress site.

## WordPress.org / Plugin Check compliance

Rules that are not obvious and cost real time when re-derived per plugin:

| Rule | Why |
|---|---|
| Namespace root = slug in `Ucfirst_Snake` (`Perxel_Example`) | `PrefixAllGlobals` accepts it as the prefix; a `Vendor\Package` namespace is flagged (`NonPrefixedNamespaceFound`) and Plugin Check ignores the `phpcs.xml.dist` prefix list |
| Custom-table names via `%i`, never string-concatenated | `WordPress.DB.PreparedSQL.NotPrepared` is **error-level** and blocks .org (see "Custom tables") |
| No `load_plugin_textdomain()` | .org auto-loads translations (slug == text domain); calling it on `plugins_loaded` is "too early" on WP 6.7+ |
| Prefix any variable you **assign** in a view (`$pxex_url`); vars passed in via `extract()` are fine | `NonPrefixedVariableFound` fires on template-scope assignments |
| No `phpcs:disable WordPress.Security.*` anywhere in `includes/` or the main file; use `Admin::kit()` and per-line ignores | Reviewers flag file-wide security disables as escaping/nonce failures (hit perxel-image-optimizer and perxel-ai-translate); `bin/check-suppressions.sh` enforces it |
| `set_time_limit()` etc.: `function_exists()` guard + inline `// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- <reason>` | discouraged-function warning |
| Calling another plugin's hooks (WPML `wpml_*`, WooCommerce): scope a `phpcs.xml.dist` exclude to the wrapper file **and** add the code to `lint.yml` -> `ignore-codes` | `NonPrefixedHooknameFound`; the two tools don't share config |
| `'suppress_filters' => true` in a query: same dual-suppression, code `WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters` | deliberate but flagged |

The split that bites: **Plugin Check runs its own ruleset, not `phpcs.xml.dist`.**
Any suppression for a documented false positive goes in *both* places -
`phpcs.xml.dist` (for `composer run lint`) and `lint.yml` -> `ignore-codes`.

## Releasing

1. Bump the version in `perxel-example.php` (header + `PXEX_VERSION`) and
   `readme.txt` (`Stable tag`); add a changelog entry to both `readme.txt` and
   `CHANGELOG.md`. Merge to `main` first. Tag, plugin `Version:` and `Stable tag`
   must all be equal or the deploy fails before touching SVN.
2. Create the tag on `main` and publish a GitHub Release. `release.yml`'s `zip`
   job attaches `perxel-example.zip`; the `deploy` job commits trunk +
   `tags/<version>` + `.wordpress-org/` (-> SVN `assets/`) with the SHA-pinned
   10up action. It only runs when the repo variable `DEPLOY_TO_WPORG` is `true`.
3. Verify `https://wordpress.org/plugins/<slug>/` and
   `https://api.wordpress.org/plugins/info/1.0/<slug>.json` show the new version.
   Assets can 404 on `ps.w.org` for a while after the first commit (CDN lag).

### First release of a new plugin (the only manual bit is the review)

1. Upload `dist/<slug>.zip` at <https://wordpress.org/plugins/developers/add/>.
   No SVN repo exists until the review team approves it.
2. Secrets `SVN_USERNAME` / `SVN_PASSWORD`: set once as **org** secrets and grant
   this repo access (org -> Settings -> Secrets -> Repository access). Use an
   SVN-specific password if the wordpress.org profile offers one. Never paste it
   in chat or commit it.
3. Once approved: set the repo variable `DEPLOY_TO_WPORG=true`, run **Actions ->
   Release -> Run workflow** with the tag and `dry_run` on (default) to check the
   staging without committing, then publish the Release. The very first version
   deploys the same way as every later one - no manual SVN commit.
4. If automation ever breaks, plain `svn` works: check out
   `https://plugins.svn.wordpress.org/<slug>`, copy the `.distignore`-filtered
   build into `trunk/`, `.wordpress-org/*` into `assets/`, `svn cp trunk
   tags/<version>`, `svn ci`.

Notes: a large first commit (hundreds of vendored files) sits on "Committing
transaction..." for minutes - normal. The action strips the `v` from a `vX.Y.Z`
tag itself; on a manual run it can't, hence the explicit `VERSION`. Do not bump
versions, tag or publish releases without the maintainer asking.

Build artifacts (`dist/`) are never committed.

## Source of truth (starter repo only)

Delete this section in a generated plugin.

Every Perxel plugin is generated from here, so this repo is the single source of
truth (SSOT) for everything that is *not* plugin-specific:

| Owned here | Where |
|---|---|
| CI: PHPCS + Plugin Check (built-zip approach) | `.github/workflows/lint.yml`, `phpcs.xml.dist` |
| Release: zip + SHA-pinned WordPress.org deploy, version gate, dry run | `.github/workflows/release.yml` |
| What ships / what doesn't | `.distignore`, `bin/*.sh` (byte-identical everywhere) |
| WordPress.org compliance rules and the release / first-submission process | `CLAUDE.md` -> "Documentation rules", "WordPress.org / Plugin Check compliance", "Releasing" |
| Listing-asset names, sizes and tips | `.wordpress-org/README.md` |
| House layout and conventions | `includes/`, `CLAUDE.md` |

The admin UI kit is the one exception: its source is
[`perxel/wp-plugin-ui`](https://github.com/perxel/wp-plugin-ui); plugins vendor it
with `bin/update-ui.sh`.

Rules:

- **Fix shared things here first**, then port to plugins. If a plugin session finds
  a better CI setup, a new compliance rule or a release gotcha, it goes into the
  starter in the same sitting - never only into the one plugin, and never as a
  separate "playbook" in a plugin repo (that is a second source of truth).
- **Plugin-specific stays in the plugin**: its code, `readme.txt`, listing art,
  slug/tokens.
- Existing plugins do **not** auto-sync. When a starter change matters, port it by
  hand (the slug and prefix tokens are the only expected differences in shared
  files; `bin/*.sh` should be byte-identical).
