# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

B2B Conector is a PHP-based web application for managing business networking events. It enables administrators to organize events, manage participating companies, create matches between buyers and suppliers, and schedule appointments.

## Architecture

This follows a **MVC (Model-View-Controller)** pattern with a modern layout system:

- **Controllers** (`controllers/`): Handle HTTP requests and business logic
- **Models** (`models/`): Manage data operations and database interactions
- **Views** (`views/`): Render HTML templates and UI components
- **Public** (`public/`): Entry point with assets (CSS, JS, images)
- **Config** (`config/`): Application configuration and database settings
- **Utils** (`utils/`): Utility classes (Logger, Security, Validator, SpreadsheetReader)

### Layout System Architecture

The application uses a **dynamic layout system** with specialized controllers:

- **BaseController**: Core controller with dynamic layout rendering (`admin`, `event`, `auto`)
- **AdminController**: System administrators using Material Design admin layout
- **EventAdminController**: Event administrators with specialized event layout
- **Layout Templates** (`views/layouts/`): Material Design 3 layouts with conditional rendering

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
# Install PHP dependencies
composer install

# Update dependencies
composer update

# View application logs in real-time
tail -f logs/$(date +%Y-%m-%d).log

# View PHP error logs
tail -f logs/php_errors.log

# Set proper file permissions (required for uploads and logs)
chmod 755 uploads/ logs/
chown www-data:www-data uploads/ logs/  # For Apache/Nginx

# Test database connection
php -r "require_once 'config/database.php'; echo 'DB connection OK';"

# Check PHP syntax across the project
find . -name "*.php" -exec php -l {} \; | grep -v "No syntax errors detected"
```

### Database Setup

1. Configure database connection in `config/database.php`
2. Import database schema (check BD_b2b.md for structure)
3. Ensure `uploads/` and `logs/` directories have write permissions

### Testing and Debugging

The codebase uses custom testing utilities rather than formal frameworks:

```bash
# Run authentication tests
php test_auth.php

# Test logger functionality
php test_logger.php

# Test email service
php test_email.php

# Debug system state
php debug.php

# View log files
php ver_log.php
```

**Available Test Files:**
- `test-components.html` - Visual UI component testing
- `test-performance.html` - CSS performance metrics
- `test-admin-layout.php` - Admin layout functionality
- `test-routing.php` - URL routing system testing
- `demo-material.php` - Material Design implementation demo
- `demo-modern.php` - Modern UI implementation demo
- `test-refactored.php` - Refactored code testing
- `test-assets-updated.php` - Asset system testing

### File Structure Navigation

- Entry point: `public/index.php`
- Controllers: `controllers/[Entity]Controller.php`
- Models: `models/[Entity].php`
- Views: `views/[entity]/[action].php`
- Shared components: `views/shared/` and `views/components/`

### CSS/JS Organization

**Modern Asset Architecture** with Material Design 3 integration:

```
public/assets/css/
├── core.css, components.css, layouts.css    # Core system styles
├── material-theme.css                       # Material Design 3 theme
├── admin-layout.css, event-layout.css      # Layout-specific styles
├── modules/                                 # Feature-specific styles
└── components/                              # Reusable UI components
```

**Asset Loading Strategy:**
- Critical CSS inlined for performance (`views/shared/assets.php`)
- Conditional Material Design loading based on `MATERIAL_DESIGN_ENABLED`
- CSS preloading with `onload` fallback for non-critical styles
- Module-specific CSS loaded per page requirements

### Logging and Debugging

- Application logs: `logs/[date].log`
- PHP error logs: `logs/php_errors.log`
- Use `Logger::debug()`, `Logger::info()`, `Logger::warning()`, `Logger::error()`
- Debug mode can be enabled in `config/config.php`

### Common Patterns

1. **Controller Actions**: Follow REST-like conventions (index, create, store, edit, update, delete)
2. **Model Methods**: Use consistent naming (find, findById, create, update, delete, getAll)
3. **Layout Rendering**: Controllers extend BaseController and use `$this->render($view, $data, $layout)`
4. **Flash Messages**: Use `setFlashMessage()` for user feedback
5. **CSRF Protection**: Forms include `<?= generateCsrfToken() ?>`
6. **Material Design**: Use helper functions `materialButton()`, `materialCard()` when `MATERIAL_DESIGN_ENABLED`
7. **Type Safety**: Cast variables to appropriate types for mathematical operations (use `(int)` for numeric calculations)

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

### Key System Architecture Notes

**Bootstrap Process** (`public/index.php`):
1. Loads configuration and initializes Logger early
2. Sets up error handling and database connection
3. Implements session management with CSRF protection
4. Custom routing system with URL-based dispatching

**Controller Hierarchy**:
- `BaseController`: Foundation with layout rendering (`$this->render()`)
- `AdminController`: System admin interface with Material Design
- `EventAdminController`: Event-specific admin interface
- `PublicBaseController`: Public registration forms (no authentication)

**Dependencies**:
- PHPMailer (composer managed) for email functionality
- No frontend build tools - direct CSS/JS serving
- Custom Logger, Security, and Validator utilities

**Material Design Integration**:
- Conditional loading via `MATERIAL_DESIGN_ENABLED` config
- Helper functions: `materialButton()`, `materialCard()`
- Separate theme files: `material-theme.css`, `admin-layout.css`

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.