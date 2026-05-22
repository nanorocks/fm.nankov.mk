# FM Macedonia

Spotify-style web app for streaming 37 Macedonian FM radio stations live in the browser.

Built with **Laravel 13**, **Filament 4** admin panel, **RoachPHP** scraper, and a Tailwind CSS + Feather icons frontend.

---

## Requirements

| Tool | Version |
|------|---------|
| PHP | 8.3+ (tested on 8.5) |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |

SQLite is used by default — no external database required.

---

## Local development

```bash
# 1. Clone and enter the project directory
git clone <repo-url> fm.nankov.mk
cd fm.nankov.mk/project

# 2. Install PHP dependencies
composer install

# 3. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 4. Create the SQLite database and run migrations
touch database/database.sqlite
php artisan migrate

# 5. Install Node dependencies and start Vite
npm install

# 6. Link public storage
php artisan storage:link

# 7. Start all services (server + queue + logs + Vite)
composer run dev
```

Open `http://localhost:8000`.

### Populate data

```bash
# Phase 1a — scrape Macedonian stations from makedonijafm.net (RoachPHP)
php artisan app:radio-web-scraper

# Phase 1b — import additional stations from the radio-browser.info catalog
php artisan app:radio-importer --name=naxi              # all Naxi (Serbia) stations
php artisan app:radio-importer --countrycode=MK         # all Macedonian stations
php artisan app:radio-importer --countrycode=RS         # all Serbian stations
php artisan app:radio-importer                          # top 500 by clickcount (default)

# Phase 2 — download logos to local storage (covers all sources)
php artisan app:radio-channel-table-data-transformation

# Phase 3 — probe every stream and toggle `published` based on whether it actually plays
#         (unpublishes broken streams; re-publishes any that have come back)
php artisan app:radio-channels-check-playable
```

The playability check uses raw curl with `CURLOPT_HTTP09_ALLOWED` so it correctly handles Icecast/Shoutcast servers that respond with the non-standard `ICY 200 OK` status line. Each probe aborts after the first 1 KB to avoid actually streaming audio. Useful options: `--dry-run`, `--only-unpublished` (re-test dead streams), `--only-published`, `--timeout=N`.

`app:radio-importer` accepts `--name=`, `--country=`, `--countrycode=`, `--tag=`, `--limit=` (default 500, capped at 5000), and `--order=`. Imported rows use `alt = 'rb:<stationuuid>'`, so re-running the command upserts idempotently. Stations with no favicon are still imported — their logo column stays blank and can be set manually via the admin.

### Create an admin account

```bash
php artisan make:filament-user
```

Admin panel: `http://localhost:8000/admin`

---

## Production deployment

Run the all-in-one setup script from the repo root:

```bash
bash setup-prod.sh
```

Options:

| Flag | Effect |
|------|--------|
| *(none)* | Full setup including scraping |
| `--skip-scrape` | Skip data scraping (useful for code-only deploys) |
| `--fresh` | Wipe the database and re-migrate (destructive) |

The script handles:
1. PHP / Node version check
2. `.env` creation and app key generation
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build`
5. Database migrations
6. `storage:link`
7. Config / route / view / event cache
8. Station scraping and logo downloads
9. Bulk-publishing stations with live streams

After the script: create an admin user manually with `php artisan make:filament-user`.

---

## Architecture

```
project/
├── app/
│   ├── Console/Commands/
│   │   ├── RadioWebScraper.php                  # artisan app:radio-web-scraper
│   │   ├── RadioImporterCommand.php             # artisan app:radio-importer (radio-browser.info)
│   │   └── RadioChannelTableDataTransformation  # artisan app:radio-channel-table-data-transformation
│   ├── Spiders/
│   │   └── FmRadioScraper.php                   # RoachPHP spider — scrapes a.radio-card elements
│   ├── Pipelines/
│   │   └── SaveRadioStationPipeline.php          # upserts on alt (station name)
│   ├── Services/
│   │   └── RadioBrowserImporter.php              # JSON importer for radio-browser.info
│   ├── Models/
│   │   └── RadioChannel.php                      # column names as constants
│   └── Filament/Resources/
│       └── RadioChannelResource.php              # Filament 4 admin CRUD
├── resources/views/
│   └── welcome.blade.php                         # Spotify-like frontend
├── routes/
│   ├── web.php                                   # / and /channels JSON endpoints
│   └── console.php                               # scheduler (monthly scrape + import)
└── config/app.php                                # RADIO_CHANNELS_URL + RADIO_BROWSER_API_URL
```

### Data flow

```
makedonijafm.net (a.radio-card)
  └─ FmRadioScraper          → saves title, stream URL, logo URL, genre tags
       └─ SaveRadioStationPipeline                     (alt = station slug)

radio-browser.info (/json/stations/search)
  └─ RadioBrowserImporter    → filters by name/country/tag, dedupes by stationuuid
                                                       (alt = rb:<uuid>)

Both sources
  └─ DataTransformation cmd  → downloads logos to storage/app/public/photos/
  └─ publish stations        → sets published=true where audio_url is present
  └─ welcome.blade.php       → renders published stations with audio
```

Station logos are stored locally in `storage/app/public/photos/`. Re-running the transformation command only downloads logos that are not yet cached. Rows with an empty `src` (e.g. radio-browser entries without a favicon) are skipped by the downloader.

### Scheduled tasks

`routes/console.php` schedules the data pipeline to refresh **monthly** (1st of each month at 00:00):

```
app:radio-web-scraper
app:radio-channel-table-data-transformation
app:radio-importer --name=naxi
app:radio-channels-check-playable
```

Activate by adding this cron entry on the server (already noted at the top of `routes/console.php`):

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Inspect the schedule any time with `php artisan schedule:list`.

### Filament 4 note

Filament 4 renamed `Filament\Forms\Form` → `Filament\Schemas\Schema`. Resource `form()` methods use `Schema $schema` and `->components([...])` instead of `->schema([...])`  . The `$navigationIcon` property must be typed `\BackedEnum|string|null`.

---

## Available commands

```bash
# Development
composer run dev                                  # start all services concurrently
php artisan test                                  # run PHPUnit test suite
php artisan test --filter TestName                # single test
vendor/bin/pint                                   # format PHP with Laravel Pint

# Scraper
php artisan app:radio-web-scraper                          # re-scrape makedonijafm.net
php artisan app:radio-importer [--name=… --countrycode=… --tag=… --limit=…]
                                                           # import from radio-browser.info
php artisan app:radio-channel-table-data-transformation    # re-download logos
php artisan app:radio-channels-check-playable              # probe streams, unpublish broken
php artisan schedule:list                                  # show scheduled commands

# Laravel Boost (AI dev tools)
php artisan boost:install                         # configure agent guidelines + MCP
php artisan boost:update                          # pull latest Boost guidelines
php artisan boost:mcp                             # start MCP server (used by .mcp.json)
```

---

## Laravel Boost (AI tooling)

[Laravel Boost](https://laravel.com/docs/boost) v2 is installed as a dev dependency. It exposes an MCP server that gives AI agents live access to the app's database schema, routes, logs, and documentation.

MCP config: `.mcp.json` (repo root, `cwd: project`).

After cloning on a new machine, run `php artisan boost:install` to register the MCP server with your editor.

---

## Environment variables

Key variables beyond the Laravel defaults:

| Variable | Description |
|----------|-------------|
| `RADIO_CHANNELS_URL` | Source URL for the makedonijafm scraper (default: `https://www.makedonijafm.net/`) |
| `RADIO_BROWSER_API_URL` | radio-browser.info mirror used by `app:radio-importer` (default: `https://de1.api.radio-browser.info`; override with `de2`, `fi1`, `us1` if reliability suffers) |
| `APP_URL` | Public URL — used by Boost's `get-absolute-url` tool |
