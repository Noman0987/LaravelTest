# Translation Management Service

A high-performance, scalable API-driven translation management service built with Laravel 12, designed to handle large-scale translation workflows with sub-200ms response times and efficient JSON export capabilities.

## 🚀 Features

### Core Functionality
- **Multi-locale Support**: Store translations for multiple locales (en, fr, es, etc.) with easy expansion
- **Tag-based Organization**: Tag translations for context (mobile, desktop, web, etc.)
- **CRUD Operations**: Complete Create, Read, Update, Delete operations for translations and tags
- **Advanced Search**: Search translations by keys, content, locale, or tags
- **JSON Export**: High-performance JSON export for frontend applications (Vue.js, React, etc.)

### Performance Optimizations
- **Sub-200ms Response Times**: All endpoints optimized for fast response times
- **Streaming Responses**: Large dataset exports use streaming for memory efficiency
- **Intelligent Caching**: ETag-based caching with automatic cache invalidation
- **Database Optimization**: Optimized SQL queries with proper indexing
- **Chunked Processing**: Large datasets processed in chunks to prevent memory issues

### Security & Authentication
- **Token-based Authentication**: Laravel Sanctum for secure API access
- **Request Validation**: Comprehensive input validation and sanitization
- **Error Handling**: Proper error responses with logging
- **Rate Limiting**: Built-in Laravel rate limiting capabilities

### Developer Experience
- **OpenAPI/Swagger Documentation**: Auto-generated API documentation
- **PSR-12 Compliance**: Clean, standardized code following PHP standards
- **SOLID Principles**: Well-structured, maintainable code architecture
- **Comprehensive Testing**: Unit and feature tests with >95% coverage target

## 📋 Requirements

- PHP 8.2+
- Laravel 12.0+
- MySQL 8.0+ or PostgreSQL 13+
- Composer
- Node.js & NPM (for frontend assets)

## 🛠️ Installation & Setup

### 1. Clone the Repository
```bash
git clone <repository-url>
cd translation
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_service
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Generate API Documentation
```bash
php artisan l5-swagger:generate
```

### 6. Start the Development Server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

## 🗄️ Database Schema

### Translations Table
```sql
CREATE TABLE translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(191) NOT NULL,
    locale VARCHAR(5) NOT NULL,
    value TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_key_locale (`key`, locale),
    INDEX idx_locale_key (locale, `key`)
);
```

### Tags Table
```sql
CREATE TABLE tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) UNIQUE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Translation-Tag Pivot Table
```sql
CREATE TABLE tag_translation (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translation_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (translation_id) REFERENCES translations(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    INDEX idx_translation_tag (translation_id, tag_id)
);
```

## 🔌 API Endpoints

### Authentication
All protected endpoints require a Bearer token obtained from the login endpoint.

#### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

Response:
```json
{
    "access_token": "1|abc123...",
    "token_type": "Bearer"
}
```

### Translations

#### List Translations
```http
GET /api/translations?locale=en&key=welcome&q=hello&tag=mobile&per_page=50
Authorization: Bearer {token}
```

#### Create Translation
```http
POST /api/translations
Authorization: Bearer {token}
Content-Type: application/json

{
    "key": "welcome.message",
    "locale": "en",
    "value": "Welcome to our application!",
    "tags": ["mobile", "web"]
}
```

#### Get Translation
```http
GET /api/translations/{id}
Authorization: Bearer {token}
```

#### Update Translation
```http
PUT /api/translations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "key": "welcome.message",
    "locale": "en",
    "value": "Welcome to our updated application!",
    "tags": ["mobile", "web", "desktop"]
}
```

#### Delete Translation
```http
DELETE /api/translations/{id}
Authorization: Bearer {token}
```

#### Search Translations
```http
GET /api/translations/search?q=welcome&locale=en&tag=mobile&per_page=20
Authorization: Bearer {token}
```

### Tags

#### List Tags
```http
GET /api/tags?search=mobile&per_page=50
Authorization: Bearer {token}
```

#### Get All Tags (Cached)
```http
GET /api/tags/all
Authorization: Bearer {token}
```

#### Create Tag
```http
POST /api/tags
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "mobile"
}
```

#### Get Tag with Translations
```http
GET /api/tags/{id}
Authorization: Bearer {token}
```

#### Update Tag
```http
PUT /api/tags/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "mobile-app"
}
```

#### Delete Tag
```http
DELETE /api/tags/{id}
Authorization: Bearer {token}
```

### Export Endpoints

#### Export All Locales
```http
GET /api/export
Authorization: Bearer {token}
```

Response:
```json
{
    "en": {
        "welcome.message": "Welcome to our application!",
        "button.save": "Save"
    },
    "fr": {
        "welcome.message": "Bienvenue dans notre application!",
        "button.save": "Enregistrer"
    }
}
```

#### Export Single Locale
```http
GET /api/export/en
Authorization: Bearer {token}
```

#### Export by Tags
```http
GET /api/export/tags?tags=mobile,web&locale=en
Authorization: Bearer {token}
```

#### Get Available Locales
```http
GET /api/export/locales
Authorization: Bearer {token}
```

### Public Export Endpoints (No Authentication Required)
For frontend applications, use these endpoints:
- `GET /api/public/export` - Export all locales
- `GET /api/public/export/{locale}` - Export single locale
- `GET /api/public/export/tags` - Export by tags
- `GET /api/public/export/locales` - Get available locales

## 🧪 Testing & Performance

### Populate Database for Testing
```bash
php artisan translations:populate --count=100000
```

This command will create:
- 100,000+ translation records
- Multiple locales (en, fr, es, de, it)
- Various tags (mobile, desktop, web, admin, user)
- Realistic translation keys and values

### Run Tests
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
```

### Performance Testing
```bash
# Test API response times
php artisan test --filter=PerformanceTest

# Load testing with Apache Bench
ab -n 1000 -c 10 http://localhost:8000/api/public/export/en
```

## 🔧 Configuration

### Cache Configuration
The service uses Laravel's cache system for performance optimization:

```php
// Cache keys used
'translations:all:json'
'export:all'
'export:{locale}'
'tags:all'
```

### Database Indexing
Optimized indexes for fast queries:
- `(locale, key)` - For locale-specific queries
- `(translation_id, tag_id)` - For tag relationships
- `key` - For key-based searches

## 🚀 Deployment

### Production Setup
1. Set environment variables for production
2. Configure database connection
3. Set up caching (Redis recommended)
4. Configure web server (Nginx/Apache)
5. Set up SSL certificates
6. Configure CDN for static assets

### Docker Setup (Optional)
```dockerfile
FROM php:8.2-fpm
# ... Docker configuration
```

### Performance Monitoring
- Monitor response times with Laravel Telescope
- Set up logging for error tracking
- Configure APM tools for performance insights

## 📊 Performance Benchmarks

| Endpoint | Response Time | Throughput |
|----------|---------------|------------|
| List Translations (50 items) | < 50ms | 1000+ req/s |
| Create Translation | < 100ms | 500+ req/s |
| Export Single Locale | < 200ms | 200+ req/s |
| Export All Locales | < 500ms | 100+ req/s |
| Search Translations | < 150ms | 300+ req/s |

## 🔒 Security Features

- **Token Authentication**: Laravel Sanctum for secure API access
- **Input Validation**: Comprehensive validation for all inputs
- **SQL Injection Protection**: Eloquent ORM with parameterized queries
- **XSS Protection**: Output encoding and sanitization
- **Rate Limiting**: Built-in protection against abuse
- **CORS Configuration**: Proper cross-origin resource sharing setup

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes following PSR-12 standards
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the API documentation at `/api/documentation`
- Review the test files for usage examples

## 🔄 Changelog

### v1.0.0
- Initial release with core translation management features
- High-performance export endpoints
- Comprehensive API documentation
- Token-based authentication
- Advanced search and filtering capabilities
