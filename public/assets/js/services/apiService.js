class ApiService {
    constructor() {
        // Ensure this matches your actual XAMPP folder name
        this.baseURL = '/hotelmanagement';
    }

    // --- INTERNAL HELPERS ---
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${this.baseURL}${url}?${queryString}` : `${this.baseURL}${url}`;
        const response = await fetch(fullUrl, {
            method: 'GET',
            headers: this.getHeaders(),
        });
        return this.handleResponse(response);
    }

    async post(url, data = {}) {
        const response = await fetch(`${this.baseURL}${url}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(data),
        });
        return this.handleResponse(response);
    }

    async put(url, data = {}) {
        const response = await fetch(`${this.baseURL}${url}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify(data),
        });
        return this.handleResponse(response);
    }

    async delete(url) {
        const response = await fetch(`${this.baseURL}${url}`, {
            method: 'DELETE',
            headers: this.getHeaders(),
        });
        return this.handleResponse(response);
    }

    getHeaders() {
        const token = localStorage.getItem('token');
        return {
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` }),
        };
    }

    async handleResponse(response) {
        const contentType = response.headers.get("content-type");

        // Check if the response is actually JSON
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error("Server returned non-JSON response:", text);
            throw new Error("Server Error: The server did not return valid JSON.");
        }

        const data = await response.json();

        // Normalize legacy response shapes where some endpoints return { status: 'success' }
        if (data && typeof data === 'object' && ('status' in data) && !('success' in data)) {
            data.success = String(data.status).toLowerCase() === 'success';
        }

        // Normalize common message fields
        if (data && typeof data === 'object') {
            if (!data.message && data.msg) data.message = data.msg;
            if (!data.message && data.error) data.message = data.error;
        }

        if (!response.ok) {
            // Handle HTTP errors (and PHP-level success:false by reading message)
            throw new Error((data && data.message) || 'API request failed');
        }

        return data;
    }

    // --- API METHODS ---

    // ROOMS
    async getRooms(hotelId = null) {
        return this.get('/api/room/list_rooms.php', hotelId ? { hotel_id: hotelId } : {});
    }

    async addRoom(roomData) {
        return this.post('/api/room/create_room.php', roomData);
    }

    async updateRoom(roomId, roomData) {
        return this.put(`/api/room/update_room.php?id=${roomId}`, roomData);
    }

    async deleteRoom(roomId) {
        return this.delete(`/api/room/delete_room.php?id=${roomId}`);
    }

    // HOTELS
    async getHotels(params = {}) {
        return this.get('/api/hotel/list_hotels.php', params);
    }

    async getHotelDetails(hotelId) {
        return this.get('/api/hotel/hotel_details.php', { hotel_id: hotelId });
    }

    // TOURS
    async getTours(params = {}) {
        return this.get('/api/services/tours.php', params);
    }

    async bookTour(data) {
        return this.post('/api/services/tours.php', data);
    }

    // BOOKINGS
    async getBookings() {
        return this.get('/api/booking/list_bookings.php');
    }

    async createBooking(data) {
        return this.post('/api/booking/create_booking.php', data);
    }

    async cancelBooking(data) {
        return this.post('/api/booking/cancel_booking.php', data);
    }

    // USER
    async getCurrentUserProfile() {
        return this.get('/api/user/profile.php');
    }
}

const apiService = new ApiService();
export default apiService;
