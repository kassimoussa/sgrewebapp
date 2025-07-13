# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application for employee and employer management with an API backend. The system manages employees, employers, contracts, and monthly confirmations through both web admin interface and mobile API endpoints.

## Development Commands

### Starting Development Environment
```bash
# Start all services (server, queue, logs, and frontend build)
composer run dev
```

### Individual Commands
```bash
# Start Laravel development server
php artisan serve

# Start queue worker
php artisan queue:listen --tries=1

# Watch logs in real-time
php artisan pail --timeout=0

# Frontend development (Vite)
npm run dev

# Build frontend for production
npm run build
```

### Database Operations
```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create model with migration
php artisan make:model ModelName -m
```

### Testing
```bash
# Run all tests
composer run test
# OR
php artisan test

# Run specific test
php artisan test --filter TestName

# Run tests with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Clear application cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Architecture Overview

### Core Models and Relationships
- **Employee**: Central employee entity with personal info, documents, and contracts
- **Employer**: Employer entity with documents and contracts
- **Contrat**: Links employees and employers with contract details
- **ConfirmationMensuelle**: Monthly confirmation records for contracts
- **Nationality**: Reference data for nationalities
- **DocumentEmployee/DocumentEmployer**: File attachments for employees/employers

### API Structure
- **API v1**: RESTful API under `/api/v1/` prefix
- **Authentication**: Laravel Sanctum for API token authentication
- **Public routes**: Registration, login, nationalities list
- **Protected routes**: Employee/employer management, documents, contracts

### Frontend Architecture
- **Livewire**: Used for admin interface components
- **Bootstrap 5**: UI framework
- **Sass**: Styling with custom admin theme
- **Vite**: Asset bundling and hot reloading

### Key Directories
- `app/Http/Controllers/Api/`: API endpoints for mobile app
- `app/Http/Controllers/Admin/`: Web admin controllers  
- `app/Livewire/Admin/`: Livewire components for admin interface
- `app/Models/`: Eloquent models
- `resources/views/livewire/admin/`: Livewire component views
- `routes/api.php`: API routes
- `routes/web.php`: Web routes
- `database/migrations/`: Database schema
- `storage/app/public/`: File uploads (photos, documents)

### Configuration Notes
- Uses SQLite database (`database/database.sqlite`)
- File uploads stored in `storage/app/public/`
- Queue system configured (check `.env` for driver)
- Laravel Sanctum for API authentication
- Spatie Laravel Permission for role management

### Testing Configuration
- PHPUnit with SQLite in-memory database for tests
- Test files in `tests/Feature/` and `tests/Unit/`
- Tests use array drivers for cache, mail, and sessions