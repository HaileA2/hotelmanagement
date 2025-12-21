class ApiService {
    constructor() {
        this.baseUrl = 'http://localhost/hotel-management-system/api';
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const token = localStorage.getItem('token');
        
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Something went wrong');
            }

            // For DELETE requests that might not return content
            if (response.status === 204) {
                return {};
            }

            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Auth endpoints
    async login(email, password) {
        return this.request('/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    }

    async register(userData) {
        return this.request('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    // User endpoints
    async getCurrentUser() {
        return this.request('/user/profile.php');
    }

    async updateProfile(userData) {
        return this.request('/user/update_profile.php', {
            method: 'PUT',
            body: JSON.stringify(userData)
        });
    }

    // Hotel endpoints
    async getHotels() {
        return this.request('/hotel/list_hotels.php');
    }

    async createHotel(hotelData) {
        return this.request('/hotel/create_hotel.php', {
            method: 'POST',
            body: JSON.stringify(hotelData)
        });
    }

    // Room endpoints
    async getRooms() {
        return this.request('/room/list_rooms.php');
    }

    async createRoom(roomData) {
        return this.request('/room/create_room.php', {
            method: 'POST',
            body: JSON.stringify(roomData)
        });
    }

    // Booking endpoints
    async getBookings() {
        return this.request('/booking/list_bookings.php');
    }

    async createBooking(bookingData) {
        return this.request('/booking/create_booking.php', {
            method: 'POST',
            body: JSON.stringify(bookingData)
        });
    }

    async cancelBooking(bookingData) {
        return this.request('/booking/cancel_booking.php', {
            method: 'PUT',
            body: JSON.stringify(bookingData)
        });
    }
}

// Create a singleton instance
export const apiService = new ApiService();
