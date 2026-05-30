# Changelog

All notable changes to this package will be documented in this file.

## [1.0.2] - 2026-05-30

### Fixed
- `initializeAlternativePayment()` now correctly calls `gate.solidgate.com/api/v1/init-payment` instead of the pay API
- Improved nested API error message parsing (`error.messages` field maps)

### Added
- `recurringAlternativePayment()` for gate `v1/recurring`
- `getRoutingEventsReport()` and `downloadRoutingEvents()` reporting endpoints
- `PaymentType` helper class with official CIT/MIT payment type constants
- `ErrorMessageFormatter` and `SolidGateResponse::getErrorMessage()`

## [1.0.1] - 2026-05-30

### Fixed
- Corrected HMAC-SHA512 signature generation to match Solidgate official documentation
- Fixed request body signing — now sends the exact JSON string used for the signature
- Fixed GET request signatures (empty body per Solidgate spec)
- Corrected 30+ API endpoint paths to match the official API reference
- Fixed gate API base URL default (`https://gate.solidgate.com/api/`)
- Improved error message extraction for Solidgate `error.messages` format
- Fixed webhook middleware registration for string middleware config
- Added webhook `Merchant` header validation

### Added
- Unit tests for signature validation and HTTP client behaviour
- `phpunit.xml`, `LICENSE`, `.env.example`, and `.gitignore`

## [1.0.0] - 2026-05-XX

### Added
- Modern PHP 8.2+ support with readonly properties
- Comprehensive exception handling with custom exception classes
- Type-safe response objects (`SolidGateResponse`)
- Webhook handling with signature verification middleware
- Webhook controller and event dispatching
- Laravel HTTP client integration (replacing direct Guzzle usage)
- Comprehensive PHPDoc comments throughout
- Request/response logging support
- Better configuration validation
- Support for GET, POST, PUT, PATCH, DELETE HTTP methods
- Proper error handling with detailed exception messages

### Changed
- Updated to PHP 8.2+ requirement
- Replaced Guzzle HTTP client with Laravel's HTTP client
- Improved error handling with specific exception types
- Enhanced configuration file with better documentation
- Updated service provider with route registration
- Improved signature generation and validation

### Fixed
- Fixed signature generation for different HTTP methods
- Fixed configuration validation
- Fixed route registration in service provider
- Improved error messages and exception handling

### Security
- Added webhook signature verification middleware
- Improved signature validation with timing-safe comparison
