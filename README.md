# Brokerio

A full-stack PHP application for property research and management using the LAMP stack (Linux, Apache, MySQL, PHP) with JavaScript for interactive mapping.

## Features

- **Property Intake**: Add properties with automatic geocoding via OpenStreetMap Nominatim API
- **Address Enrichment**: Automatically extracts latitude, longitude, and additional metadata
- **Interactive Map**: View properties on an interactive Leaflet map
- **Notes System**: Add and manage notes for each property
- **RESTful API**: JSON API endpoints for property and note management

## Project Structure

```
brokerio/
├── index.php             # Main entry point with property form
├── README.md             # This file
├── AI_PROPOSAL.md        # AI/LLM enhancement proposal
├── api/
│   ├── db.php            # Database connection configuration
│   ├── property.php      # GET endpoint for property details
│   └── add_note.php      # POST endpoint for adding notes
├── public/
│   ├── map.html          # Interactive map page
│   ├── styles.css        # Styles for map page
│   └── scripts.js        # Scrips for map page
│
└── sql/
    └── schema.sql        # Database schema
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Apache web server with mod_rewrite enabled (optional)
- Internet connection (for geocoding API)

## Installation

### 1. Database Setup

```bash
# Login to MySQL
mysql -u root -p

# Run the schema file
source /var/www/html/brokerio/sql/schema.sql
```

Or manually:

```bash
mysql -u root -p < /var/www/html/brokerio/sql/schema.sql
```

### 2. Database Configuration

Edit `api/db.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'brokerio');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

Alternativaly, you may use PhpMyAdmin.

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'phpmyadmin');
define('DB_USER', 'phpmyadmin_username');
define('DB_PASS', 'phpmyadmin_password');
```

### 3. Web Server Configuration

### Apache

Ensure your document root points to the project directory, or configure a virtual host:

```apache
<VirtualHost *:80>
    ServerName brokerio.local
    DocumentRoot /var/www/html/brokerio
    
    <Directory /var/www/html/brokerio>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### PHP Built-in Server (Development)

```bash
cd /var/www/html/brokerio
php -S localhost:8000
```

Then visit: `http://localhost:8000`

### 4. File Permissions

Ensure the web server can read all files:

```bash
chmod -R 755 /var/www/html/brokerio
```

## Usage

### Adding a Property

1. Navigate to `index.php` in your browser
2. Enter a property name and address
3. Click "Add Property"
4. The system will:
   - Geocode the address using OpenStreetMap
   - Extract coordinates and metadata
   - Save to the database
   - Display confirmation with a link to view on map

### Viewing on Map

1. After adding a property, click "View on Map"
2. Or navigate directly to: `public/map.html?id={PROPERTY_ID}`
3. The map will:
   - Center on the property location
   - Display a marker with popup
   - Show property details in the sidebar
   - Allow adding notes

### Adding Notes

1. On the map page, scroll to the notes section
2. Enter a note in the textarea
3. Click "Add Note"
4. The note will appear in the list immediately

## API Endpoints

### GET /api/property.php?id={id}

Returns property details and all associated notes.

**Response:**
```json
{
  "property": {
    "id": 1,
    "name": "Downtown Office Building",
    "address": "123 Main St, New York, NY 10001",
    "latitude": "40.7128",
    "longitude": "-74.0060",
    "extra_field": "{\"confidence\":8.5,\"type\":\"building\"}",
    "created_at": "2024-01-15 10:30:00"
  },
  "notes": [
    {
      "id": 1,
      "property_id": 1,
      "note": "Great location near subway",
      "created_at": "2024-01-15 11:00:00"
    }
  ]
}
```

### POST /api/add_note.php

Adds a new note to a property.

**Request Body:**
```json
{
  "property_id": 1,
  "note": "This is a note about the property"
}
```

**Response:**
```json
{
  "success": true,
  "note": {
    "id": 2,
    "property_id": 1,
    "note": "This is a note about the property",
    "created_at": "2024-01-15 12:00:00"
  }
}
```

## Geocoding API

The system uses **OpenStreetMap Nominatim** for geocoding, which is:
- Free and open-source
- No API key required
- Rate-limited (please be respectful)

**Note:** Nominatim requires a User-Agent header. The system sets this automatically.

If you need higher rate limits or more features, you can modify the `geocodeAddress()` function in `index.php` to use:
- Google Geocoding API (requires API key)
- Mapbox Geocoding API (requires API key)
- U.S. Census Geocoder (free, U.S. addresses only)

## Security Notes

- All database queries use prepared statements to prevent SQL injection
- Input is sanitized using `htmlspecialchars()` for XSS prevention
- API endpoints validate input and return appropriate HTTP status codes
- Consider adding authentication/authorization for production use

## Troubleshooting

### Database Connection Error

- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `api/db.php`
- Ensure database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### Geocoding Fails

- Check internet connection
- Verify the address format is correct
- OpenStreetMap Nominatim may be rate-limiting (wait a few seconds)
- Check PHP error logs: `tail -f /var/log/apache2/error.log`

### Map Not Displaying

- Check browser console for JavaScript errors
- Verify Leaflet CDN is accessible
- Ensure property ID is valid in URL

## Development

### Testing the API

```bash
# Get property details
curl http://localhost/brokerio/api/property.php?id=1

# Add a note
curl -X POST http://localhost/brokerio/api/add_note.php \
  -H "Content-Type: application/json" \
  -d '{"property_id":1,"note":"Test note"}'
```

## License

This project is provided as-is for evaluation purposes.

