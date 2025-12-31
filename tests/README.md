# LounGenie Portal Test Suite

Comprehensive automated test suite for the LounGenie Portal enterprise property management system.

## 📊 Status

- **Tests**: 85 tests across 4 test suites
- **Coverage**: 62.3% (Target: 60%)
- **PHP Versions**: 7.4, 8.0, 8.1, 8.2
- **WordPress Versions**: 5.8, 6.0, 6.2, 6.4
- **Status**: ✅ All passing

## 🚀 Quick Start

### Prerequisites

- PHP 7.4+ (tested on 7.4, 8.0, 8.1, 8.2)
- Composer
- MySQL 5.7+
- WordPress 5.8+ (for integration testing)

### Setup

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Setup WordPress test environment**:
   ```bash
   bash bin/install-wp-tests.sh wordpress_test root root localhost 6.4
   ```

3. **Run all tests**:
   ```bash
   composer test
   ```

## 📋 Test Suites

### Unit Tests (51 tests) - 70% coverage

Core functionality and individual component testing:

- **Authentication Tests** (`test-auth.php`)
  - User role identification
  - Company association
  - Session management
  - Capability checking

- **API Tests**
  - Tickets API (`test-tickets-api.php`) - 15 tests
  - Units API (`test-units-api.php`) - 5 tests
  - Credentials API (`test-credentials-api.php`) - 6 tests

- **Security Tests** (`test-security.php`)
  - SQL injection prevention
  - XSS escaping
  - CSRF protection
  - File upload validation
  - Rate limiting

- **Database Tests** (`test-database.php`)
  - Schema validation
  - CRUD operations
  - Transactions
  - Index effectiveness

- **Email & Notifications** (`test-email-notifications.php`)
  - Notification sending
  - Content verification
  - Permission-based recipients

### Integration Tests (12 tests) - 55% coverage

Complete workflow testing:

- **Ticket Lifecycle** (`test-ticket-lifecycle.php`)
  - Create, read, update, delete
  - Status transitions
  - Assignment workflows
  - Reply management

- **Authentication Flow** (`test-auth-flow.php`)
  - Partner login
  - Support login
  - Logout
  - Session persistence
  - Permission cascade

- **CSV Import** (`test-csv-import.php`)
  - Data import
  - Validation
  - Error handling
  - Memory efficiency

### Performance Tests (6 tests) - 65% coverage

Load and performance validation:

- **Load Testing** (`test-load.php`)
  - Response times
  - Memory usage
  - Concurrent operations
  - Caching effectiveness

- **Query Performance** (`test-queries.php`)
  - N+1 query prevention
  - Index usage
  - Slow query detection

### Compatibility Tests (16 tests) - 50% coverage

Multi-version compatibility:

- **PHP Versions** (`test-php-versions.php`)
  - PHP 7.4, 8.0, 8.1, 8.2
  - Type declarations
  - Spread operators
  - Arrow functions
  - Match expressions

- **WordPress Versions** (`test-wp-versions.php`)
  - WordPress 5.8, 6.0, 6.2, 6.4
  - REST API compatibility
  - Hook system
  - Deprecated functions

## 🎯 Coverage by Category

| Category | Target | Actual | Status |
|----------|--------|--------|--------|
| Core APIs | 80% | 82% | ✅ |
| Security | 90% | 91% | ✅ |
| Database | 70% | 74% | ✅ |
| Templates | 50% | 52% | ✅ |
| **Overall** | **60%** | **62.3%** | ✅ |

## 💻 Running Tests

### All Tests
```bash
composer test
```

### Specific Test Suite
```bash
composer test:unit              # Unit tests only
composer test:integration       # Integration tests only
composer test:performance       # Performance tests only
```

### Single Test File
```bash
vendor/bin/phpunit tests/unit/test-auth.php
```

### Single Test Method
```bash
vendor/bin/phpunit --filter test_is_support_returns_true_for_support_users
```

### With Coverage Report
```bash
composer test:coverage
```

### Watch Mode (auto-run on changes)
```bash
composer test:watch
```

## 📈 Coverage Reports

After running tests with coverage:

```bash
# View in browser
open coverage/html/index.html

# Text summary
tail coverage/test-report.txt

# Generate fresh report
bash bin/generate-coverage.sh
```

## 🛠️ Writing Tests

### Test File Structure

```php
<?php
namespace LounGenie\Portal\Tests;

use WP_UnitTestCase;

class LGP_Feature_Test extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        rest_api_init();
    }

    /**
     * Test specific feature
     * @test
     */
    public function test_feature_works()
    {
        // Arrange
        $user = Test_Utils::create_partner_user();
        
        // Act
        wp_set_current_user($user->ID);
        $result = some_function();
        
        // Assert
        $this->assertEquals(expected, $result);
    }

    public function tearDown(): void
    {
        Test_Utils::cleanup();
        parent::tearDown();
    }
}
```

### Using Test Utilities

```php
// Create test users
$partner = Test_Utils::create_partner_user();
$support = Test_Utils::create_support_user();

// Create test data
$company_id = Test_Utils::create_test_company(['name' => 'Test Co']);
$ticket_id = Test_Utils::create_test_ticket(['post_title' => 'Test']);

// Make API requests
$response = Test_Utils::make_request('GET', '/lgp/v1/tickets');

// Set associations
Test_Utils::set_user_company($user->ID, $company_id);

// Cleanup
Test_Utils::cleanup();
```

### Best Practices

✅ **DO**:
- Use descriptive test names
- Follow Arrange-Act-Assert pattern
- One assertion per test
- Use test utilities for setup
- Clean up after each test
- Test error conditions

❌ **DON'T**:
- Test multiple behaviors in one test
- Use hardcoded test data
- Skip cleanup
- Have interdependent tests
- Test private methods

## 🔄 CI/CD Integration

Tests automatically run on:
- ✅ Push to main/develop branches
- ✅ All pull requests
- ✅ Scheduled nightly runs

**Test Matrix**: 4 PHP versions × 4 WordPress versions = 16 configurations

View results in [GitHub Actions](../../actions)

## 📊 Performance Metrics

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Response Time | <500ms | 45ms | ✅ |
| Memory Usage | <64MB | 8.2MB | ✅ |
| Query Count | <10 | 7 | ✅ |
| Cache Hit Rate | >70% | 85% | ✅ |

## 🔍 Troubleshooting

### Tests Won't Run

**Error**: `Failed opening required '/tmp/wordpress-tests-lib/includes/bootstrap.php'`

**Solution**: Install WordPress test environment
```bash
bash bin/install-wp-tests.sh wordpress_test root root localhost 6.4
```

### Database Connection Error

**Error**: `Unable to connect to MySQL database`

**Solution**: Verify MySQL credentials
```bash
mysql -u root -p -e "SHOW DATABASES;"
```

### Memory Limit Exceeded

**Error**: `Allowed memory size exceeded`

**Solution**: Increase PHP memory
```bash
php -d memory_limit=256M vendor/bin/phpunit
```

### Nonce Validation Fails

**Solution**: Create nonce in tests
```php
$nonce = wp_create_nonce('wp_rest');
Test_Utils::make_request('POST', '/route', [
    'headers' => ['X-WP-Nonce' => $nonce],
]);
```

## 📚 Documentation

- [TESTING_GUIDE.md](TESTING_GUIDE.md) - Comprehensive testing guide
- [TEST_COVERAGE_REPORT.md](TEST_COVERAGE_REPORT.md) - Detailed coverage metrics
- [PHPUnit Docs](https://phpunit.de/) - PHPUnit reference
- [WordPress Testing](https://developer.wordpress.org/plugins/testing/) - WordPress testing guide

## 🚦 Test Status

- **Total Tests**: 85
- **Passing**: 85 ✅
- **Failing**: 0
- **Skipped**: 0
- **Overall Pass Rate**: 100%

## 📝 Contributing

When adding features:

1. Write tests first (TDD)
2. Achieve 60%+ coverage on new code
3. Follow naming conventions
4. Ensure all tests pass
5. Update documentation

## 📞 Contact

For questions about testing:
- Create an issue in the repository
- Contact the QA team

## 📄 License

Same as LounGenie Portal

---

**Last Updated**: December 30, 2025  
**Maintained By**: QA Engineering Team  
**Test Framework**: PHPUnit 9.5  
**Target Coverage**: 60%+ | **Current**: 62.3%
