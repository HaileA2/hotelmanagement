class ApiService {
    constructor() {
        this.baseURL = '/hotel-management-system';
    }

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
        const headers = {
            'Content-Type': 'application/json',
        };

        const token = localStorage.getItem('token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        return headers;
    }

    async handleResponse(response) {
        if (!response.ok) {
            const error = await response.text();
            throw new Error(error || 'API request failed');
        }

        return response.json();
    }

    // Specific methods if needed
    async getCurrentUser() {
        return this.get('/api/user/profile.php');
    }

    async getHotels(params = {}) {
        return this.get('/api/hotel/list_hotels.php', params);
    }

    async getBookings() {
        return this.get('/api/booking/list_bookings.php');
    }

    async createBooking(data) {
        return this.post('/api/booking/create_booking.php', data);
    }

    async cancelBooking(data) {
        return this.post('/api/booking/cancel_booking.php', data);
    }
}

const apiService = new ApiService();
export default apiService;