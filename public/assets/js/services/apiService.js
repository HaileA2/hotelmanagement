class ApiService {
    constructor() {
        // Ensure this matches your actual XAMPP folder name
        this.baseURL = '/hotelmanagement';
    }

    // --- INTERNAL HELPERS (The "Engine") ---
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
            'Authorization': token ? `Bearer ${token}` : ''
        };
    }

    async handleResponse(response) {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'API request failed');
        }
        return data;
    }

    // --- REFACTORED API METHODS (Using the helpers above) ---
    
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

    // BOOKINGS
    async getBookings() {
        return this.get('/api/booking/list_bookings.php');
    }

    async createBooking(data) {
        return this.post('/api/booking/create_booking.php', data);
    }

/**
     * Cancel a specific booking
     * @param {Object} data - Should contain { booking_id: id }
     */
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