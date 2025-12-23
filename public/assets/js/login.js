// public/assets/js/login.js
import { authService } from '../js/services/auth.service.js';

document.addEventListener('DOMContentLoaded', function() {
    // Check if already logged in
    if (authService.isAuthenticated()) {
        window.location.href = 'index.html';
        return;
    }

    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginAlert = document.getElementById('loginAlert');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
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

    // Handle form submission
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const rememberMe = document.getElementById('rememberMe').checked;

        // Validation
        if (!email || !password) {
            showAlert('Please enter both email and password', 'danger');
            return;
        }

        if (!isValidEmail(email)) {
            showAlert('Please enter a valid email address', 'danger');
            return;
        }

        // Show loading state
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...';

        try {
            await authService.login(email, password);

            // Redirect based on user role
            const user = authService.getCurrentUser();
            if (user.role === 'Admin' || user.role === 'Manager') {
                window.location.href = 'dashboard.html';
            } else {
                window.location.href = 'index.html';
            }
        } catch (error) {
            showAlert(error.message || 'Login failed. Please check your credentials.', 'danger');
        } finally {
            // Reset button state
            loginBtn.disabled = false;
            loginBtn.innerHTML = 'Sign In';
        }
    });
});

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('loginAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}