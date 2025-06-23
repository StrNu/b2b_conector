# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

B2B Conector is a PHP-based web application for managing business networking events. It enables administrators to organize events, manage participating companies, create matches between buyers and suppliers, and schedule appointments.

## Architecture

This follows a classic **MVC (Model-View-Controller)** pattern:

- **Controllers** (`controllers/`): Handle HTTP requests and business logic
- **Models** (`models/`): Manage data operations and database interactions
- **Views** (`views/`): Render HTML templates and UI components
- **Public** (`public/`): Entry point with assets (CSS, JS, images)
- **Config** (`config/`): Application configuration and database settings
- **Utils** (`utils/`): Utility classes (Logger, Security, Validator, SpreadsheetReader)

### Key Components

- **Database Layer**: PDO-based with singleton pattern (`config/database.php`)
- **Authentication**: Session-based with CSRF protection
- **Routing**: Custom router in `public/index.php` with URL-based dispatching
- **Logging**: Custom Logger class with file-based logging (`utils/Logger.php`)
- **Security**: Input validation and sanitization utilities

### Core Entity Relationships

- **Events** contain multiple **Companies** (buyers/suppliers)
- **Companies** have **Requirements** and **Assistants**
- **Matches** are generated between buyer and supplier companies
- **Appointments** are scheduled meetings between matched companies
- **Categories** organize business sectors and requirements

## Development Workflow

### Running the Application

```bash
# Development server (built-in PHP server)
php -S localhost:8000 -t public/

# Production setup (Apache/Nginx should point to public/ directory)
```

### Common Development Commands

```bash
# View application logs in real-time
tail -f logs/$(date +%Y-%m-%d).log

# View PHP error logs
tail -f logs/php_errors.log

# Set proper file permissions
chmod 755 uploads/ logs/
chown www-data:www-data uploads/ logs/  # For Apache/Nginx

# Test database connection
php -r "require_once 'config/database.php'; echo 'DB connection OK';"
```

### Database Setup

1. Configure database connection in `config/database.php`
2. Import database schema (check BD_b2b.md for structure)
3. Ensure `uploads/` and `logs/` directories have write permissions

### File Structure Navigation

- Entry point: `public/index.php`
- Controllers: `controllers/[Entity]Controller.php`
- Models: `models/[Entity].php`
- Views: `views/[entity]/[action].php`
- Shared components: `views/shared/` and `views/components/`

### CSS/JS Organization

CSS and JS are organized by modules:
- `public/assets/css/modules/`: Feature-specific styles (events.css, matches.css, etc.)
- `public/assets/css/components/`: Reusable UI components (buttons.css, tables.css, etc.)
- `public/assets/js/modules/`: Feature-specific JavaScript
- `public/assets/js/components/`: Reusable JS components

### Logging and Debugging

- Application logs: `logs/[date].log`
- PHP error logs: `logs/php_errors.log`
- Use `Logger::debug()`, `Logger::info()`, `Logger::warning()`, `Logger::error()`
- Debug mode can be enabled in `config/config.php`

### Common Patterns

1. **Controller Actions**: Follow REST-like conventions (index, create, store, edit, update, delete)
2. **Model Methods**: Use consistent naming (find, findById, create, update, delete, getAll)
3. **View Inclusion**: Controllers call `include VIEW_DIR . '/path/to/view.php'`
4. **Flash Messages**: Use `setFlashMessage()` for user feedback
5. **CSRF Protection**: Forms include `<?= generateCsrfToken() ?>`

### Security Considerations

- All user inputs are validated and sanitized
- CSRF tokens required for state-changing operations
- File uploads restricted to safe extensions and locations
- Database queries use prepared statements
- Session configuration includes security headers

### Public Registration Routes

The application supports public registration endpoints:
- `/buyers_registration/{event_id}` - Buyer company registration
- `/suppliers_registration/{event_id}` - Supplier company registration

These routes bypass authentication and allow external registration for events.

### Database Schema Reference

Key database tables and their relationships:
- **events**: Main event information with organizer details
- **company**: Buyer/supplier companies participating in events  
- **assistants**: Individual attendees from each company
- **event_categories/event_subcategories**: Business sectors for matching
- **matches**: Generated connections between buyers and suppliers
- **event_schedules**: Scheduled appointments between matched companies
- **requirements**: Buyer purchase requirements 
- **supplier_offers**: Supplier offerings by subcategory

See `BD_b2b.md` for complete schema details.

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.