import { apiService } from './services/apiService.js';

class Auth {
    constructor() {
        this.user = null;
        this.token = localStorage.getItem('auth_token');
        
        // Bind methods
        this.login = this.login.bind(this);
        this.logout = this.logout.bind(this);
        this.register = this.register.bind(this);
    }

    // Check if user is authenticated
    isAuthenticated() {
        const token = this.getToken();
        return token !== null && token !== 'undefined';
    }

    // Get current user's role
    getCurrentUserRole() {
        const token = this.getToken();
        if (!token) return null;
        
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            return payload.role;
        } catch (e) {
            this.logout();
            return null;
        }
    }

    // Get JWT token
    getToken() {
        return localStorage.getItem('auth_token');
    }

    // Set JWT token
    setToken(token) {
        this.token = token;
        if (token) {
            localStorage.setItem('auth_token', token);
        } else {
            localStorage.removeItem('auth_token');
        }
    }

    // Set user data
    setUser(user) {
        this.user = user;
    }

    // Login user
    async login(email, password) {
        try {
            const data = await apiService.login(email, password);
            this.setUser(data.user);
            this.setToken(data.token);
            return { success: true, data };
        } catch (error) {
            console.error('Login error:', error);
            return { 
                success: false, 
                message: error.message || 'Login failed. Please check your credentials.' 
            };
        }
    }

    // Register new user
    async register(userData) {
        try {
            const data = await apiService.register(userData);
            if (data.token) {
                this.setUser(data.user);
                this.setToken(data.token);
            }
            return { success: true, data };
        } catch (error) {
            console.error('Registration error:', error);
            return {
                success: false,
                message: error.message || 'Registration failed. Please try again.'
            };
        }
    }

    // Logout user
    logout() {
        this.user = null;
        this.token = null;
        apiService.setToken(null);
        window.location.href = '/hotel-management-system/public';
    }

    // Get auth headers for API requests
    getAuthHeader() {
        return {
            'Authorization': `Bearer ${this.getToken()}`,
            'Content-Type': 'application/json'
        };
    }
}

// Create auth instance
const auth = new Auth();

// Make auth globally available for debugging
window.auth = auth;

export { auth };
