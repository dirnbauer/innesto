# Development

The extension targets TYPO3 14.3.6+ and PHP 8.4+. Install dependencies with
`ddev composer install`, then run `ddev exec Build/Scripts/runTests.sh -s ci`.
The extension's Composer lock is ignored so CI resolves current compatible
stable releases. The repository declarations are needed because Desiderio
and its companion extension are distributed through GitHub.

## Local demo

DDEV serves `.Build/public` at the URL reported by `ddev describe`.
The generated application configuration, runtime files and test databases are
ignored by Git.

```bash
ddev start
ddev composer install
ddev exec vendor/bin/typo3 setup
```

Use the interactive setup to configure the local database and admin account.
For the DDEV database, host, database, username and password are all `db`.
Create a site and add `webconsulting/innesto` to its `dependencies`.
Remove the setup wizard's placeholder `page` TypoScript before rendering with
Desiderio's site set. Set `websiteTitle: Innesto` in the site configuration.
Build Desiderio's theme delivery with the included demo configuration:

```bash
ddev exec npx --yes vite@8.2.0 build --config Build/vite.config.mjs
```

In the generated `config/system/additional.php`, enable the built assets and
allow this DDEV hostname:

```php
<?php
if (getenv('IS_DDEV_PROJECT') === 'true') {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '^innesto\\.ddev\\.site$';
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['useDevServer'] = '0';
}
```

Then run:

```bash
ddev exec vendor/bin/typo3 extension:setup
ddev exec vendor/bin/typo3 innesto:seed <page-uid>
ddev exec vendor/bin/typo3 cache:flush
```

## Tests

`Build/Scripts/runTests.sh` accepts `-s unit`, `functional`, `phpstan`, `audit`
or `ci`. Set `PHP_BIN` to a specific executable or use `-p 8.5` when a `php8.5`
binary is available. With DDEV, run it inside the container.

Unit tests cover registry URL handling, generated YAML and XML, source and
CType collisions, invalid paths, non-overwrite behavior, CSS conversion and
idempotent site-set registration.

Functional tests boot the installed TYPO3 release with Desiderio and Content
Blocks. They use temporary SQLite databases under `.Build/public/typo3temp`,
create fixtures, exercise DataHandler and render the shipped elements. The
local MariaDB demo data remains independent of those tests.
Coverage includes repeated runs, hidden records, replacement ordering and
rollback of parent and child records when a write fails.

The content audit can also run without installing TYPO3 if `ext-yaml` is
available. Alternatively, `AUDIT_AUTOLOAD` may point to an existing Composer
autoloader that provides `symfony/yaml`.
