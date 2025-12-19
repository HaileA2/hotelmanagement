import { apiService } from './api.service.js';

class AuthService {
    constructor() {
        this.currentUser = null;
        this.loadUserFromStorage();
    }

    async login(email, password) {
        try {
            const response = await apiService.login(email, password);
            
            // Save token and user data
            localStorage.setItem('token', response.token);
            localStorage.setItem('user', JSON.stringify(response.user));
            this.currentUser = response.user;
            
            return response.user;
        } catch (error) {
            console.error('Login failed:', error);
            throw error;
        }
    }

    async register(userData) {
        try {
            const response = await apiService.register(userData);
            return response.user;
        } catch (error) {
            console.error('Registration failed:', error);
            throw error;
        }
    }

    async logout() {
    try {
        // Call the logout API
        await fetch('http://localhost/hotel-management-system/api/auth/logout.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        // Clear all auth data
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        // Redirect to login page
        window.location.href = '/hotel-management-system/public/login.html';
    }
}

    isAuthenticated() {
        return !!this.getToken();
    }

    getToken() {
        return localStorage.getItem('token');
    }

    loadUserFromStorage() {
        const userData = localStorage.getItem('user');
        if (userData) {
            this.currentUser = JSON.parse(userData);
        }
        return this.currentUser;
    }

    getCurrentUser() {
        if (!this.currentUser) {
            this.loadUserFromStorage();
        }
        return this.currentUser;
    }

    // Check if user has required role
    hasRole(requiredRole) {
        const user = this.getCurrentUser();
        return user && user.role === requiredRole;
    }

    // Check if user has any of the required roles
    hasAnyRole(requiredRoles) {
        const user = this.getCurrentUser();
        return user && requiredRoles.includes(user.role);
    }

    async updateProfile(profileData) {
        try {
            const response = await apiService.updateProfile(profileData);
            
            // Update stored user data
            if (response.user) {
                this.currentUser = response.user;
                localStorage.setItem('user', JSON.stringify(response.user));
            }
            
            return response;
        } catch (error) {
            console.error('Failed to update profile:', error);
            throw error;
        }
    }
}

// Create a singleton instance
export const authService = new AuthService();
