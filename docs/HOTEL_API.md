## Hotel & Booking API Endpoints

This document describes the local API endpoints for listing hotels, fetching hotel details (including rooms/availability), and creating bookings in this project. These endpoints are intended for internal use by the frontend and require a valid JWT (Bearer) for authentication.

Base URL (local development)
- http://localhost/hotelmanagement

Notes
- All endpoints return JSON and use the shape { "success": boolean, "data": ..., "message": ... } where applicable.
- Authentication: endpoints require a JWT in the Authorization header: `Authorization: Bearer <JWT>`.
- For local development you can generate a test token with `public/get_test_token.php` (development-only helper).

---

### 1) List hotels

- Path
  - GET /api/hotel/list_hotels.php

- Description
  - Returns a paginated list of hotels stored in the local database.

- Headers
  - Authorization: Bearer <JWT>
  - Accept: application/json

- Query parameters (optional)
  - location (string) — filter by location substring
  - page (int) — page number (default: 1)
  - limit (int) — items per page (default: implementation-specific)

- Success response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Hotel Example",
      "location": "Addis Ababa",
      "description": "A comfortable stay",
      "amenities": ["wifi","pool"],
      "price_per_night": 120,
      "rating": 4.5,
      "created_at": "2025-12-01 12:00:00"
    }
  ]
}
```

- Errors
  - 401 Unauthorized — missing/invalid JWT
  - 404 Not Found — no hotels found (returns success:false message)
  - 500 Server Error — on unexpected server/database error

---

### 2) Hotel details (and rooms)

- Path
  - GET /api/hotel/hotel_details.php

- Description
  - Returns detailed hotel information and a list of rooms. If `check_in` and `check_out` are provided, rooms may be filtered for availability.

- Headers
  - Authorization: Bearer <JWT>
  - Accept: application/json

- Query parameters (required/optional)
  - hotel_id (int) — REQUIRED, the hotel id to fetch
  - check_in (date string, optional) — YYYY-MM-DD
  - check_out (date string, optional) — YYYY-MM-DD
  - guests (int, optional) — number of guests to filter capacity

- Success response (200)

```json
{
  "success": true,
  "data": {
    "hotel": {
      "id": 1,
      "name": "Hotel Example",
      "location": "Addis Ababa",
      "description": "A comfortable stay",
      "amenities": ["wifi","pool"],
      "image_url": "https://.../hotel.jpg"
    },
    "rooms": [
      {
        "id": 10,
        "hotel_id": 1,
        "name": "Standard Room",
        "capacity": 2,
        "price": 100,
        "available": true
      }
    ]
  }
}
```

- Errors
  - 400 Bad Request — missing/invalid `hotel_id` or malformed dates
  - 401 Unauthorized — missing/invalid JWT
  - 404 Not Found — hotel not found
  - 500 Server Error — database or unexpected error

---

### 3) Create booking (room)

- Path
  - POST /api/booking/create_booking.php

- Description
  - Creates a booking for a room in the local database. This is a local-only booking endpoint (no external providers by default).

- Headers
  - Authorization: Bearer <JWT>
  - Content-Type: application/json

- Request body (JSON)

Required fields

```json
{
  "room_id": 10,
  "hotel_id": 1,
  "check_in": "2026-01-01",
  "check_out": "2026-01-05",
  "guest_count": 2,
  "total_price": 400
}
```

Optional fields

```json
{
  "special_requests": "Late check-in"
}
```

- Success response (201)

```json
{
  "success": true,
  "message": "Booking created successfully.",
  "booking_id": 123
}
```

- Errors
  - 400 Bad Request — incomplete booking data or room no longer available
  - 401 Unauthorized — missing/invalid JWT
  - 500 Server Error — database failure when saving booking

Notes and tips
- JWT: For development you may use the lightweight helper `public/get_test_token.php` to generate a test token (development-only). Do not ship this helper to production.
- Idempotency: The current implementation does not implement idempotency keys — be careful to avoid duplicate POST retries from clients.
- Validation: Dates are parsed and normalized server-side; ensure dates are provided in a consistent format (YYYY-MM-DD).

Examples (curl)

List hotels (with token):

```bash
TOKEN=$(curl -s http://localhost/hotelmanagement/public/get_test_token.php)
curl -s -H "Authorization: Bearer $TOKEN" \
  "http://localhost/hotelmanagement/api/hotel/list_hotels.php"
```

Hotel details:

```bash
TOKEN=$(curl -s http://localhost/hotelmanagement/public/get_test_token.php)
curl -s -H "Authorization: Bearer $TOKEN" \
  "http://localhost/hotelmanagement/api/hotel/hotel_details.php?hotel_id=1&check_in=2026-01-01&check_out=2026-01-05"
```

Create booking:

```bash
TOKEN=$(curl -s http://localhost/hotelmanagement/public/get_test_token.php)
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"room_id":10,"hotel_id":1,"check_in":"2026-01-01","check_out":"2026-01-05","guest_count":2,"total_price":400}' \
  "http://localhost/hotelmanagement/api/booking/create_booking.php"
```

---

If you want, I can add a small OpenAPI (Swagger) YAML for these endpoints so you can browse them in Swagger UI. Would you like that as the next step?
