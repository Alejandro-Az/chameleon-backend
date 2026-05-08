
## Commands

```bash
# Setup
composer setup                        # Install deps, create .env, generate keys, migrate

# Development
composer dev                          # php artisan serve

# Testing
composer test                         # Clear config cache + run full test suite
php artisan test                      # Run all tests
php artisan test tests/Feature/AuthBlockingTest.php  # Single file
php artisan test --filter=login       # Filter by pattern
php artisan test --coverage           # With coverage report

# Swagger / OpenAPI
php artisan l5-swagger:generate       # Regenerate API documentation

# Maintenance (normally scheduled daily)
php artisan kaan:install              # Bootstrap: migrate + seed + create admin
php artisan kaan:health               # Verify readiness (migrations, config, env)
```