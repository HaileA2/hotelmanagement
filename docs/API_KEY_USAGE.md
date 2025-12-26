# API Key Usage Guide

## Overview
External services can integrate with the Hotel Management System using API keys for booking creation only.

## Authentication
Use the `X-API-Key` header with your API key:

```
X-API-Key: HOTEL_API_KEY_2024
```

## Available Endpoints

### Create Booking
**Endpoint:** `POST /hotelmanagement/api/booking/create_booking.php`

**Headers:**
```
Content-Type: application/json
X-API-Key: HOTEL_API_KEY_2024
```

**Request Body:**
```json
{
  "room_id": 1,
  "hotel_id": 1,
  "check_in": "2024-12-25",
  "check_out": "2024-12-27",
  "guest_count": 2,
  "total_price": 200.00,
  "special_requests": "Late check-in requested"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Booking created successfully.",
  "booking_id": 123
}
```

## Security Notes
- API keys can only create bookings
- All other endpoints require JWT authentication
- API key bookings are associated with a system customer account
- Rate limiting and monitoring should be implemented

## Error Responses
```json
{
  "success": false,
  "message": "Invalid API key or insufficient permissions."
}
```

## Contact
For API key requests or technical support, contact the system administrator.