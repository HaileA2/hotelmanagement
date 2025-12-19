/**
 * Main Application Entry Point
 * Initializes the Hotel Management System frontend
 */

// Import necessary modules
import { apiService } from './services/apiService.js';
import { auth } from './auth.js';
import { Navigation } from './navigation.js';
import { renderHome } from './pages/home.js';

// Make apiService globally available for debugging
window.apiService = apiService;

// Main App Class
class HotelManagementApp {
    constructor() {
        this.navigation = new Navigation();
        this.init();
    }

    // Initialize the application
    async init() {
        try {
            // Check authentication status
            await this.checkAuthStatus();
            
            // Initialize navigation and routing
            this.navigation.initRouter();
            
            // Initialize home page if we're on the home page
            if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
                renderHome();
            }
            
            // Add global error handler
            window.addEventListener('error', this.handleGlobalError);
            
            console.log('Application initialized successfully');
        } catch (error) {
            console.error('Failed to initialize application:', error);
            this.showError('Failed to initialize application. Please refresh the page.');
        }
    }

    // Check if user is authenticated
    async checkAuthStatus() {
        try {
            const user = await apiService.verifyToken();
            if (user) {
                auth.setUser(user);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Token verification failed:', error);
            auth.logout();
            return false;
        }
    }

    // Global error handler
    handleGlobalError(error) {
        console.error('Global error:', error);
        
        // Show user-friendly error message
        const errorMessage = error.message || 'An unexpected error occurred';
        this.showError(errorMessage);
    }

    // Show error message to user
    showError(message) {
        // Check if error container exists, if not create one
        let errorContainer = document.getElementById('globalError');
        
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = 'globalError';
            errorContainer.className = 'fixed bottom-4 right-4 max-w-sm p-4 bg-red-100 border-l-4 border-red-500 text-red-700';
            errorContainer.role = 'alert';
            document.body.appendChild(errorContainer);
        }
        
        // Set error message
        errorContainer.innerHTML = `
            <p class="font-bold">Error</p>
            <p>${message}</p>
            <button onclick="this.parentElement.remove()" class="absolute top-0 right-0 px-2 py-1">
                &times;
            </button>
        `;
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorContainer && errorContainer.parentNode) {
                errorContainer.remove();
            }
        }, 5000);
    }

    // Show success message
    showSuccess(message) {
        // Check if success container exists, if not create one
        let successContainer = document.getElementById('globalSuccess');
        
        if (!successContainer) {
            successContainer = document.createElement('div');
            successContainer.id = 'globalSuccess';
            successContainer.className = 'fixed bottom-4 right-4 max-w-sm p-4 bg-green-100 border-l-4 border-green-500 text-green-700';
            successContainer.role = 'alert';
            document.body.appendChild(successContainer);
        }
        
        // Set success message
        successContainer.innerHTML = `
            <p class="font-bold">Success</p>
            <p>${message}</p>
            <button onclick="this.parentElement.remove()" class="absolute top-0 right-0 px-2 py-1">
                &times;
            </button>
        `;
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (successContainer && successContainer.parentNode) {
                successContainer.remove();
            }
        }, 5000);
    }
}

// Initialize the application when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Create global app instance
    window.app = new HotelManagementApp();
    
    // Make auth globally available
    window.auth = auth;
    
    // Add global helper functions
    window.showError = (message) => window.app.showError(message);
    window.showSuccess = (message) => window.app.showSuccess(message);
});

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { HotelManagementApp };
}
