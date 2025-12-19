import { auth } from '../../auth.js';

export class LoginPage {
    static init() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            return new LoginPage();
        }
        return null;
    }
    constructor() {
        this.form = document.getElementById('loginForm');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.errorElement = document.getElementById('loginError');
        this.submitButton = this.form?.querySelector('button[type="submit"]');
        
        if (this.form) {
            this.initializeEventListeners();
        }
    }

    initializeEventListeners() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const email = this.emailInput.value.trim();
        const password = this.passwordInput.value;

        // Basic validation
        if (!email || !password) {
            this.showError('Please fill in all fields');
            return;
        }

        // Disable submit button
        if (this.submitButton) {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Signing in...';
        }

        try {
            const result = await auth.login(email, password);
            
            if (result.success) {
                this.showError('', 'Login successful! Redirecting...', 'success');
                // Redirect to dashboard or home page after successful login
                setTimeout(() => {
                    window.location.href = '/hotel-management-system/public';
                }, 1500);
            } else {
                this.showError(result.message || 'Login failed. Please check your credentials.');
            }
        } catch (error) {
            console.error('Login error:', error);
            const errorMessage = error.message || 'An error occurred during login. Please try again.';
            this.showError(errorMessage);
        } finally {
            // Re-enable submit button
            if (this.submitButton) {
                this.submitButton.disabled = false;
                this.submitButton.innerHTML = 'Sign in';
            }
        }
    }

    showError(message, title = 'Error', type = 'error') {
        if (!this.errorElement) return;
        
        this.errorElement.innerHTML = `
            <div class="${type === 'error' ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'} 
                         border-l-4 p-4 mb-4 rounded" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-bold">${title}</p>
                        <p class="text-sm">${message}</p>
                    </div>
                </div>
            </div>
        `;
        
        this.errorElement.classList.remove('hidden');
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                this.errorElement.classList.add('hidden');
            }, 5000);
        }
    }

}

// Auto-initialize if this is the login page
document.addEventListener('DOMContentLoaded', () => {
    LoginPage.init();
});
