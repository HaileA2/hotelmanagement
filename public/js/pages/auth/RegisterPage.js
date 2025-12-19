// public/js/pages/auth/RegisterPage.js
import apiService from '../../services/apiService.js';

class RegisterPage {
    constructor() {
        this.form = document.getElementById('registerForm');
        this.passwordInput = document.getElementById('password');
        this.confirmPasswordInput = document.getElementById('confirmPassword');
        this.errorContainer = document.getElementById('errorContainer');
        this.submitButton = this.form?.querySelector('button[type="submit"]');
        
        if (this.form) {
            this.init();
        }
    }

    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        this.passwordInput?.addEventListener('input', () => this.updatePasswordStrength());
        
        // Add password toggle functionality
        const togglePasswordBtns = this.form?.querySelectorAll('.toggle-password');
        togglePasswordBtns?.forEach(btn => {
            btn.addEventListener('click', (e) => this.togglePassword(e.target));
        });
    }

    togglePassword(button) {
        const input = button.closest('.input-group').querySelector('input');
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        button.classList.toggle('fa-eye');
        button.classList.toggle('fa-eye-slash');
    }

    updatePasswordStrength() {
        const strengthMeter = document.getElementById('password-strength');
        if (!strengthMeter) return;

        const strength = this.calculatePasswordStrength(this.passwordInput.value);
        const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];

        strengthMeter.style.width = `${strength * 25}%`;
        strengthMeter.className = `h-1 rounded-full ${strengthColors[strength - 1]}`;
    }

    calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
        return Math.min(5, Math.max(1, strength));
    }

    validateForm() {
        const firstName = this.form.querySelector('#firstName')?.value.trim();
        const lastName = this.form.querySelector('#lastName')?.value.trim();
        const email = this.form.querySelector('#email')?.value.trim();
        const password = this.passwordInput.value;
        const confirmPassword = this.confirmPasswordInput.value;
        const terms = this.form.querySelector('#terms')?.checked;

        // Clear previous errors
        this.showError('');

        // Validate required fields
        if (!firstName || !lastName || !email || !password || !confirmPassword) {
            this.showError('All fields are required');
            return false;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            this.showError('Please enter a valid email address');
            return false;
        }

        // Validate password match
        if (password !== confirmPassword) {
            this.showError('Passwords do not match');
            return false;
        }

        // Validate password strength
        if (password.length < 8) {
            this.showError('Password must be at least 8 characters long');
            return false;
        }

        // Validate terms acceptance
        if (!terms) {
            this.showError('You must accept the terms and conditions');
            return false;
        }

        return true;
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }

        const formData = {
            firstName: this.form.querySelector('#firstName').value.trim(),
            lastName: this.form.querySelector('#lastName').value.trim(),
            email: this.form.querySelector('#email').value.trim(),
            password: this.passwordInput.value,
            accountType: this.form.querySelector('input[name="accountType"]:checked')?.value || 'guest'
        };

        if (this.submitButton) {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating account...';
        }

        try {
            const result = await apiService.register(formData);
            
            if (result.success) {
                this.showError('', 'Registration successful! Please check your email to verify your account.', 'success');
                // Redirect to login page after 3 seconds
                setTimeout(() => {
                    window.location.href = '/hotel-management-system/public/login.html';
                }, 3000);
            } else {
                this.showError(result.message || 'Registration failed. Please try again.');
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showError(error.message || 'An error occurred during registration. Please try again.');
        } finally {
            if (this.submitButton) {
                this.submitButton.disabled = false;
                this.submitButton.innerHTML = 'Create Account';
            }
        }
    }

    showError(message, title = 'Error', type = 'error') {
        if (!this.errorContainer) return;
        
        this.errorContainer.className = `alert alert-${type} mb-4`;
        this.errorContainer.innerHTML = `
            ${title ? `<strong>${title}:</strong> ` : ''}${message}
        `;
        this.errorContainer.style.display = message ? 'block' : 'none';
    }
}

// Initialize the register page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new RegisterPage();
});