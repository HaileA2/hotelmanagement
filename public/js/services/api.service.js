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
        return this.request('/user/me.php');
    }

    async updateProfile(userData) {
        return this.request('/user/update_profile.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    // Room endpoints
    async getRooms() {
        return this.request('/room/read.php');
    }

    async getRoom(id) {
        return this.request(`/room/read_one.php?id=${id}`);
    }

    // Booking endpoints
    async getBookings() {
        return this.request('/booking/read.php');
    }

    async createBooking(bookingData) {
        return this.request('/booking/create.php', {
            method: 'POST',
            body: JSON.stringify(bookingData)
        });
    }

    async updateBooking(id, bookingData) {
        return this.request(`/booking/update.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(bookingData)
        });
    }

    async deleteBooking(id) {
        return this.request(`/booking/delete.php?id=${id}`, {
            method: 'DELETE'
        });
    }
}

// Create a singleton instance
export const apiService = new ApiService();
