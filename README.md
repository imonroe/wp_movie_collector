# WP Movie Collector

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

Example:
```
[movie_collection type="movies" per_page="24"]
```

### Adding Movies

1. Go to Movies > Add New Movie in the WordPress admin
2. Enter movie details manually or scan a barcode to fetch metadata
3. Save the movie to add it to your collection

### Adding Box Sets

1. Go to Movies > Add New Box Set in the WordPress admin
2. Enter box set details
3. Add existing movies to the box set or create new ones

## Contributing

Contributions are welcome! Feel free to submit pull requests or open issues on the GitHub repository.

## License

This plugin is licensed under the GPL v2 or later.