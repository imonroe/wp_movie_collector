# WP Movie Collector

[![CI](https://github.com/imonroe/wp_movie_collector/actions/workflows/ci.yml/badge.svg)](https://github.com/imonroe/wp_movie_collector/actions/workflows/ci.yml)

A WordPress plugin designed to help you manage your physical movie collection.

## Description

WP Movie Collector is a comprehensive WordPress plugin for movie collectors who want to catalog and organize their physical movie collection. Whether you have DVDs, Blu-rays, 4K UHDs, or box sets, this plugin provides a robust solution to keep track of your collection.

### Features

- **Movie Management**: Add, edit, and delete movies in your collection
- **Box Set Support**: Group movies together in box sets
- **Custom Database**: Dedicated tables for optimal performance
- **Metadata Retrieval**: Fetch movie details from TMDb and OMDb APIs
- **Barcode Scanning**: Quickly add movies by scanning barcodes
- **Search & Filter**: Find movies by title, director, actor, genre, and more
- **Responsive Design**: Works on all devices
- **Shortcode Integration**: Display your collection on any page or post

## Installation

1. Upload the `wp-movie-collector` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings, including your API keys for TMDb and OMDb
4. Add the `[movie_collection]` shortcode to any page or post to display your collection

## API Keys

This plugin uses the following external APIs to fetch movie metadata:

- [TMDb (The Movie Database)](https://www.themoviedb.org/documentation/api): Primary API for movie data
- [OMDb (Open Movie Database)](https://www.omdbapi.com/): Secondary API for backup data

You'll need to obtain API keys from these services and add them in the plugin settings.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Usage

### Shortcode

Use the `[movie_collection]` shortcode to display your movie collection on any page or post.

**Parameters:**
- `type`: Display type (`all`, `movies`, or `box_sets`). Default: `all`
- `per_page`: Number of items to display per page. Default: `12`
- `orderby`: Field to sort by (`title`, `release_year`, `id`, `created_at`, `acquisition_date`, `format`). Default: `title`
- `order`: Sort direction (`ASC` or `DESC`). Default: `ASC`

Examples:
```
[movie_collection type="movies" per_page="24"]
[movie_collection type="box_sets" orderby="release_year" order="DESC"]
```

Visitors can further search and filter the displayed collection by title, format, genre, year, director, and studio, and load more results without reloading the page.

### Using a Barcode Scanner

On the Add Movie screen you can populate the barcode field two ways:

1. **USB/Bluetooth scanner** — most consumer scanners act as a keyboard. Focus the barcode field and scan; the scanner "types" the barcode, then use the lookup button to fetch metadata.
2. **Manual entry** — type the UPC/EAN and use the lookup button.

A Barcode Lookup API key (Settings) is required for barcode-to-title resolution; TMDb/OMDb keys enrich the result with full metadata.

### Import / Export

Go to **Movies > Import/Export**:

- **Export** your collection as CSV or JSON for backup or migration.
- **Import** from CSV or JSON. Download the CSV template first so your columns match. Choose **append** to add to the existing collection, or **replace** to overwrite it.

### Adding Movies

1. Go to Movies > Add New Movie in the WordPress admin
2. Enter movie details manually or scan a barcode to fetch metadata
3. Save the movie to add it to your collection

### Adding Box Sets

1. Go to Movies > Add New Box Set in the WordPress admin
2. Enter box set details
3. Add existing movies to the box set or create new ones

## Contextual Help

Every WP Movie Collector admin page includes a **Help** tab (top-right of the screen) with guidance for that page, plus links to documentation and support. Settings fields include inline descriptions and links to each API provider's sign-up page.

## Troubleshooting

- **Barcode lookup returns nothing** — confirm a Barcode Lookup API key is set in Settings, and that the barcode is a valid UPC/EAN. Not all discs are in the database.
- **Metadata is missing or incomplete** — add a TMDb key (primary) and/or OMDb key (fallback). TMDb generally provides the richest data.
- **Collection page is empty** — make sure you've added movies and that the `[movie_collection]` shortcode `type` isn't filtering them all out.
- **Single movie/box set pages 404** — flush permalinks by visiting **Settings > Permalinks** once after activation.

## Developer Reference

### Database Schema

The plugin stores its data in three custom tables (prefixed with the site's `$wpdb->prefix`):

- `wp_movie_collection` — one row per movie (title, release_year, format, region_code, barcode, director, studio, actors, genre, cover image, acquisition_date, etc.).
- `wp_movie_box_sets` — one row per box set (similar metadata, no director/actors).
- `wp_movie_box_set_relationships` — links movies to box sets with a `display_order`.

Indexes cover barcode, release_year, format, the `title_year` and `format_year` composites, `created_at`, and `acquisition_date`.

### Hooks

Custom post types (`movie`, `box_set`) and taxonomies (genre, director, studio, actor) are registered on `init`. Action and AJAX hooks are prefixed `wp_movie_collector_`.

### REST API

The plugin registers REST routes on `rest_api_init` via `WP_Movie_Collector_REST_Controller` under the plugin's namespace. See that class for the available endpoints.

## Contributing

Contributions are welcome! Feel free to submit pull requests or open issues on the GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.