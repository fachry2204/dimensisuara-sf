# Installation on Plesk Laravel Toolkit

The project requires PHP 8.2 or newer, as specified by `composer.json`. Configure the domain document root to the application's `public` directory. Use the same PHP version for the website and Composer/Artisan commands.

The repository includes `public/.htaccess` for Apache routing. It forwards URLs such as `/login` to `public/index.php` while allowing real assets and directories to be served directly. Ensure hidden files are deployed as well. A Plesk-generated 404 can indicate an incorrect document root or missing server rewrite rules; Artisan cache clearing does not replace those server rules. For an nginx-only configuration, configure the equivalent front-controller fallback in the hosting's nginx settings.

## Composer bootstrap fix

Composer runs `php artisan package:discover` after building the autoloader. Previously, `AppServiceProvider` loaded encrypted system settings from the database before Artisan could start. A new hosting account without a configured database or SQLite file therefore failed during installation.

Infrastructure commands now boot without querying database settings. This includes package discovery, key generation, migrations, and configuration/cache commands. HTTP requests and operational console commands continue to apply database-backed settings; queue jobs refresh settings before processing. Runtime database or encryption errors are not silently suppressed.

The installation tests run package discovery, key generation, and configuration caching with an absent database and no application key. They isolate cache files and do not modify the real database or `.env`.

## Deployment order

1. Pull the updated `main` branch in Plesk Git/Laravel Toolkit.
2. Configure `.env` using the hosting database's real connection details. For MySQL, set `DB_CONNECTION=mysql` and supply `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. Set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` to the hosted URL.
3. Install locked dependencies:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader
   composer check-platform-reqs --no-dev
   ```

4. For a completely new application/database, generate its key once with `php artisan key:generate`. When importing an existing database containing encrypted system settings, retain the original application's `APP_KEY` instead. Existing encrypted data cannot be decrypted using a different key.
5. With the database configured and backed up if it contains existing data, run `php artisan migrate --force`.
6. Build frontend assets using `npm ci` and `npm run build` (or upload the corresponding build output from the same source revision if Node.js is unavailable on the hosting).
7. Ensure `storage` and `bootstrap/cache` are writable by the site's PHP user, then run `php artisan config:cache` and `php artisan view:cache`.

The source repository does not contain live database data, `.env`, uploaded branding, private signatures, or backups. These runtime files/settings must be provisioned or transferred separately when moving an existing installation.

## Uploaded logos and favicon

New branding uploads are stored in `storage/app/public/branding`; their relative references are encrypted in `system_settings`. The application serves only the configured logo/favicon through `/branding/{kind}/{version}`, so these images do not depend on a storage symlink, a static uploads location, or an asset CDN URL. Keep the storage directory persistent across deployments and writable by the PHP user. Legacy files in `public/uploads/branding` remain supported.

If an imported database points to a logo file that was not transferred, the UI uses its default image and System Settings displays a reupload message. Select the logo/favicon again and click **Simpan Identitas Aplikasi**. Choosing a file only previews it; the form must be submitted. A storage failure retains the old saved settings and displays an error.

Run `npm run build` after pulling source changes. The asset-copy phase excludes the Vue source entry and component directory so it cannot overwrite Vite's compiled `soundfresh.js` with raw `import ... from 'vue'` code.

If deployment still fails, inspect the final lines of the Laravel Toolkit task log. Package extraction messages alone do not establish whether the failure is a missing extension, filesystem permission, database connection, migration, or another Composer script error. This fix addresses the reproduced bootstrap dependency; it does not claim to diagnose an unseen hosting log.

Reference: [Plesk Laravel Toolkit documentation](https://docs.plesk.com/en-US/obsidian/administrator-guide/website-management/laravel-toolkit.80010/).
