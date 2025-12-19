// public/js/services/apiService.js
class ApiService {
    constructor() {
        this.baseUrl = 'http://localhost/hotel-management-system/api';
        this.token = localStorage.getItem('auth_token');
        this.interceptRequest();
    }

    setToken(token) {
        this.token = token;
        if (token) {
            localStorage.setItem('auth_token', token);
        } else {
            localStorage.removeItem('auth_token');
        }
    }

    async handleResponse(response) {
        let data;
        try {
            data = await response.json();
        } catch (e) {
            if (!response.ok) {
                throw new Error('Invalid JSON response from server');
            }
            return {};
        }

        if (!response.ok) {
            const error = new Error(data.message || 'Something went wrong');
            error.status = response.status;
            error.data = data;
            throw error;
        }

        return data;
    }

    interceptRequest() {
        const originalFetch = window.fetch;

        window.fetch = async (input, init = {}) => {
            // Convert relative URLs to absolute
            if (typeof input === 'string' && input.startsWith('/')) {
                input = this.baseUrl + input;
            }

            // Add auth header if token exists
            if (this.token) {
                init.headers = {
                    ...init.headers,
                    'Authorization': `Bearer ${this.token}`
                };
            }

            // Ensure content-type is set for non-GET requests
            if (init.method && init.method !== 'GET' && !init.headers?.['Content-Type']) {
                init.headers = {
                    ...init.headers,
                    'Content-Type': 'application/json'
                };
            }

            try {
                const response = await originalFetch(input, init);
                return response;
            } catch (error) {
                console.error('Network error:', error);
                throw new Error('Network error. Please check your connection.');
            }
        };
    }

    // Auth endpoints
    async login(email, password) {
        const response = await fetch('/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
        const data = await this.handleResponse(response);
        if (data.token) {
            this.setToken(data.token);
        }
        return data;
    }

    async register(userData) {
        const response = await fetch('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
        const data = await this.handleResponse(response);
        return data;
    }

    // Hotel endpoints
    async getHotels() {
        const response = await fetch('/hotel/list_hotels.php');
        return this.handleResponse(response);
    }

    // Booking endpoints
    async getBookings() {
        const response = await fetch('/booking/list_bookings.php');
        return this.handleResponse(response);
    }

    async createBooking(bookingData) {
        const response = await fetch('/booking/create_booking.php', {
            method: 'POST',
            body: JSON.stringify(bookingData)
        });
        return this.handleResponse(response);
    }

    // Room endpoints
    async getRooms() {
        const response = await fetch('/room/list_rooms.php');
        return this.handleResponse(response);
    }

    async createRoom(roomData) {
        const response = await fetch('/room/create_room.php', {
            method: 'POST',
            body: JSON.stringify(roomData)
        });
        return this.handleResponse(response);
    }

    async updateRoom(roomData) {
        const response = await fetch('/room/update_room.php', {
            method: 'PUT',
            body: JSON.stringify(roomData)
        });
        return this.handleResponse(response);
    }

    async deleteRoom(id) {
        const response = await fetch('/room/delete_room.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
        return this.handleResponse(response);
    }

    // Statistics & Reports
    async getBookingStatistics(params = {}) {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`/statistics/bookings.php?${query}`);
        return this.handleResponse(response);
    }

    async getOccupancyReport(params = {}) {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`/reports/occupancy.php?${query}`);
        return this.handleResponse(response);
    }
}

// Create a singleton instance
const apiService = new ApiService();
export default apiService;