# BeyondTrailTales - MVP Trip Planner

A simple trip planning application with backpack management, built with PHP and JSON/SQLite storage.

## Features

- **Trip Planning**: Create and manage trips with dates, locations, and descriptions
- **Backpack Management**: Configure different backpacks for various adventure types
- **Photo Upload**: Attach photos to trips with required alt text for accessibility (ADA compliant)
- **Responsive Design**: Works on desktop and mobile devices
- **Accessible**: WCAG AA compliant with keyboard navigation and screen reader support

## Quick Start

1. Ensure XAMPP is running with Apache
2. Visit http://localhost/BTT/public/
3. Run the seed script for sample data: http://localhost/BTT/test/seed.php

## URLs

- **Home**: http://localhost/BTT/public/
- **Trips**: http://localhost/BTT/public/trips.php
- **Backpacks**: http://localhost/BTT/public/backpacks.php
- **API Health**: http://localhost/BTT/api/index.php?route=health

## Storage

The app uses JSON storage by default (located in `storage/json/`). 
If SQLite3 is available, it will automatically use SQLite instead.

## Project Structure

```
BTT/
├── api/          # REST API endpoints
├── public/       # Frontend pages
├── assets/       # CSS, JS, and uploaded images
├── storage/      # Data storage (JSON or SQLite)
└── test/         # Test scripts and seed data
```

## API Endpoints

- `GET/POST/PUT/DELETE api/index.php?route=trips`
- `GET/POST/PUT/DELETE api/index.php?route=backpacks`

## Requirements

- PHP 7.4+
- Apache with mod_rewrite
- Write permissions for storage/ and assets/img/trips/

## Known Limitations

- Single user system (no authentication)
- Basic photo upload (no resizing/optimization)
- One backpack per trip (no gear item management)

## Production Deployment

1. Set `BTT_ENV` to 'production' in app/config.php
2. Remove the test/ folder
3. Ensure proper file permissions
4. Consider adding authentication if needed
