# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`fm.nankov.mk` is a Laravel 13 application that lists Macedonian FM radio stations. It scrapes station data from an external source, stores it in a SQLite database, and presents a web UI where users can stream audio, toggle favorites (stored in `localStorage`), and manage stations via a Filament admin panel.

All application code lives under `project/`.

## Commands

All commands must be run from `project/`.

**Start dev environment (server + queue + logs + Vite together):**
```bash
composer run dev
```
This runs `php artisan serve`, `php artisan queue:listen`, `php artisan pail`, and `npm run dev` concurrently.

**Individual services:**
```bash
php artisan serve        # Laravel dev server
npm run dev              # Vite asset bundler (watch mode)
npm run build            # Production assets
```

**Database:**
```bash
php artisan migrate
php artisan migrate:fresh
```

**Code style (Laravel Pint):**
```bash
./vendor/bin/pint
```

**Tests (PHPUnit):**
```bash
php artisan test
php artisan test --filter TestName   # single test
```

**Data pipeline commands (run in order for a fresh scrape):**
```bash
php artisan app:radio-web-scraper                        # Step 1: scrape station list
php artisan app:radio-channel-table-data-transformation  # Step 2: download photos, scrape audio URLs
```

**First-time production setup:**
```bash
php artisan make:filament-user   # create admin account
php artisan storage:link         # expose public disk at /storage
```

## Architecture

### Data pipeline

Station data enters through a two-step web scraping pipeline built on [RoachPHP](https://roach-php.dev/):

1. **`FmRadioScraper`** (`app/Spiders/FmRadioScraper.php`) — hits `config('app.radio_station_url')`, collects station card links/images, and feeds each item to `SaveRadioStationPipeline`, which upserts a `RadioChannel` row (keyed on `alt`).

2. **`RadioChannelTableDataTransformation`** artisan command (`app/Console/Commands/`) — iterates all channels, downloads their photos to `storage/public/photos/`, then invokes `AudioSubtitleSpider` per channel to scrape the individual station page for its `<audio>` source URL. Results are saved via `SaveAudioAndSubtitleRadioStationPipeline`.

Spiders write through their respective `ItemProcessorInterface` pipelines in `app/Pipelines/`.

### Data model

`RadioChannel` (`app/Models/RadioChannel.php`) is the only domain model. Constants are defined on the class for every column name. The `photo` mutator always prefixes stored values with `/storage/` to normalize paths. Fields `link`, `src`, `alt`, and `base_url` are scraper internals hidden from JSON output. Only rows with `published = true` and a non-null `audio_url` appear on the public frontend.

### Public frontend

A single Blade view (`resources/views/welcome.blade.php`) renders the station grid. Vanilla JS handles:
- Play/pause toggling through a shared `<audio>` element fixed to the bottom of the page
- Favorites managed in `localStorage` and displayed in a DaisyUI drawer sidebar

Styling: Tailwind CSS v3 + DaisyUI v5 via Vite.

### Admin panel

Filament v4 at `/admin`. The `RadioChannelResource` provides CRUD for stations including a custom `CustomImageColumn` (renders a Blade partial) and a `MediaAction` that plays the audio stream inline. Bulk publish/unpublish actions are available. The panel is registered in `app/Providers/Filament/AdminPanelProvider.php`.

### Storage

SQLite by default (`database/database.sqlite`). Station photos are stored on the `public` disk (`storage/app/public/photos/`) and served via the symlinked `/storage` path.

### Laravel Boost MCP

`laravel/boost` v2 is installed as a dev dependency and exposes an MCP server (`php artisan boost:mcp`). The MCP config is at `.mcp.json` (repo root, `cwd: project`). It provides 15+ tools to AI agents: `database-query`, `database-schema`, `search-docs`, `browser-logs`, `list-routes`, and more. Run `php artisan boost:update` to pull the latest guidelines.

Boost also provides two skills loaded into Claude Code: `laravel-best-practices` and `tailwindcss-development`.

### Filament 4 form API

Filament 4 renamed `Filament\Forms\Form` to `Filament\Schemas\Schema`. Resource `form()` methods use `Schema $schema` + `->components([...])`. The `$navigationIcon` property must be typed `\BackedEnum|string|null`.

### Key config

`RADIO_STATION_URL` (or `config('app.radio_station_url')`) must be set in `.env` for the scrapers to work — this key is not in `.env.example`.
