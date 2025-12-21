// Register page JavaScript
import { authService } from '../js/services/auth.service.js';

document.addEventListener('DOMContentLoaded', function() {
    // Check if already logged in
    if (authService.isAuthenticated()) {
        window.location.href = 'index.html';
        return;
    }

    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const registerAlert = document.getElementById('registerAlert');
    const togglePassword = document.getElementById('togglePassword');
    const roleSelect = document.getElementById('role');
    const professionalDetails = document.getElementById('professionalDetails');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Show/hide professional details based on role
    roleSelect.addEventListener('change', function() {
        if (this.value === 'manager') {
            professionalDetails.style.display = 'block';
            document.getElementById('professional').required = true;
        } else {
            professionalDetails.style.display = 'none';
            document.getElementById('professional').required = false;
        }
    });

    // Handle form submission
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;
        const professional = document.getElementById('professional').value.trim();
        const terms = document.getElementById('terms').checked;

        // Validation
        if (!firstName || !lastName || !email || !password) {
            showAlert('Please fill in all required fields', 'danger');
            return;
        }

        if (!isValidEmail(email)) {
            showAlert('Please enter a valid email address', 'danger');
            return;
        }

        if (password.length < 8) {
            showAlert('Password must be at least 8 characters long', 'danger');
            return;
        }

        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            showAlert('Password must contain at least one special character', 'danger');
            return;
        }

        if (role === 'manager' && !professional) {
            showAlert('Professional details are required for managers', 'danger');
            return;
        }

        if (!terms) {
            showAlert('Please accept the terms and conditions', 'danger');
            return;
        }

        // Show loading state
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating account...';

        try {
            const userData = {
                first_name: firstName,
                last_name: lastName,
                email: email,
                password: password,
                role: role
            };

            if (role === 'manager') {
                userData.professional_details = professional;
            }

            await authService.register(userData);

            showAlert('Registration successful! Redirecting to login...', 'success');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } catch (error) {
            showAlert(error.message || 'Registration failed. Please try again.', 'danger');
        } finally {
            // Reset button state
            registerBtn.disabled = false;
            registerBtn.innerHTML = 'Create Account';
        }
    });
});

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('registerAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds for success
    if (type === 'success') {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}