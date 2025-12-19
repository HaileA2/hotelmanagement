# Service Integration Tests

This directory contains tests for the external service integrations (Tour, Restaurant, and Taxi services).

## Prerequisites

1. PHP 7.4 or higher
2. Composer (for autoloading)
3. PHP cURL extension
4. A running instance of the hotel management system
5. Valid API credentials for the external services

## Setup

1. Copy the `.env.example` file to `.env` in the project root:
   ```bash
   cp .env.example .env
   ```

2. Update the `.env` file with your actual API credentials and configuration.

3. Install dependencies (if any):
   ```bash
   composer install
   ```

## Running Tests

To run all service tests:

```bash
php tests/test_services.php
```

## Test Cases

### Tour Service Tests
- Lists available tours
- Retrieves tour details
- Tests tour booking flow (simulated)
- Tests booking retrieval

### Restaurant Service Tests
- Lists available restaurants
- Retrieves restaurant details
- Checks available time slots

### Taxi Service Tests
- Estimates taxi fare between two points

## Expected Output

When you run the tests, you should see output similar to:

```
Successfully logged in. Token obtained.

=== Testing Tour Services ===

1. Getting available tours...
Found 5 tours

2. Getting tour details for ID: tour123
Tour name: Paris City Tour

3. Testing tour booking (simulated)...
Booking created with ID: book_abc123

4. Getting booking details...
Found 1 bookings for test@example.com


=== Testing Restaurant Services ===

1. Getting list of restaurants...
Found 10 restaurants

2. Getting restaurant details for ID: rest456
Restaurant name: Le Petit Bistro

3. Checking available time slots for 2025-12-20...
Available time slots: 18:00, 18:30, 19:00, 19:30, 20:00


=== Testing Taxi Services ===

1. Estimating taxi fare...
Estimated fare: 15.50 EUR


=== All tests completed ===
```

## Troubleshooting

1. **Authentication Errors**:
   - Verify your JWT secret in `.env` matches the one in your application
   - Ensure the test user credentials in `test_services.php` are valid

2. **API Connection Issues**:
   - Check your internet connection
   - Verify the API base URLs in `.env` are correct
   - Ensure your API keys are valid and have the necessary permissions

3. **Missing Dependencies**:
   - Run `composer install` to install required packages
   - Ensure the PHP cURL extension is installed and enabled

## Adding More Tests

To add more test cases, edit the appropriate test method in `test_services.php`. Each service has its own test method that you can extend with additional test cases.

## Note

- The tests are designed to be non-destructive where possible
- Some tests may create test data (like bookings) that you may want to clean up manually
- For production, consider adding more comprehensive test cases and edge cases
