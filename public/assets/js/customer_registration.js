// public/assets/js/customer_registration.js
import apiService from './services/apiService.js';

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const togglePassword = document.getElementById('togglePassword');

    // Password toggle functionality
    togglePassword.addEventListener('click', () => {
        const passwordInput = document.getElementById('password');
        const icon = togglePassword.querySelector('i');

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

    // Form submission
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const terms = document.getElementById('terms').checked;

        // Validation
        if (!firstName || !lastName || !email || !password) {
            showAlert('Please fill in all required fields.', 'danger');
            return;
        }

        if (!terms) {
            showAlert('Please agree to the Terms of Service and Privacy Policy.', 'danger');
            return;
        }

        // Password validation
        if (password.length < 8 || !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            showAlert('Password must be at least 8 characters long and contain at least one special character.', 'danger');
            return;
        }

        // Disable button
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating Account...';

        try {
            const response = await fetch('/hotelmanagement/api/auth/customer_registration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    password: password
                })
            });

            const data = await response.json();

            if (response.ok) {
                showAlert('Account created successfully! Please check your email for verification.', 'success');
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showAlert(data.message || 'Registration failed.', 'danger');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        } finally {
            // Re-enable button
            registerBtn.disabled = false;
            registerBtn.innerHTML = 'Create Customer Account';
        }
    });
});

function showAlert(message, type) {
    const alertDiv = document.getElementById('registerAlert');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.classList.remove('hidden');

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.classList.add('hidden');
    }, 5000);
}