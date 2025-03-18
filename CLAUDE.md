# WP Movie Collector - Development Guide

## Build & Test Commands
```bash
# Install dependencies
composer install
npm install

# Build assets
npm run build

# Watch for changes during development
npm run watch

# Run PHP linting
composer run lint

# Run PHP tests
composer run test

# Run a single test
composer run test -- --filter=TestName

# Run JS tests
npm run test
```

## Code Style Guidelines

### PHP
- Follow WordPress coding standards (PSR-2 with WordPress specifics)
- Class names: CamelCase
- Functions/methods: snake_case
- Variables: snake_case
- Hooks: use plugin prefix `wp_movie_collector_`
- Use type hints and return types when possible

### JavaScript
- Use ES6+ features
- Prefer named exports over default exports
- Use camelCase for variables and functions
- Use PascalCase for components and classes

### Database
- Table names prefixed with `wp_movie_`
- Fields use snake_case
- Add proper indexes for performance

### Error Handling
- Use try/catch for API integrations
- Log errors with WordPress logging functions
- Provide user-friendly error messages