document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;
    
    if (!form || !submitButton) {
        console.error('Registration form or submit button not found');
        return;
    }

    // Toggle password visibility
    const togglePassword = (inputId, toggleId) => {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(toggleId);
        
        if (passwordInput && toggleIcon) {
            toggleIcon.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                toggleIcon.classList.toggle('fa-eye');
                toggleIcon.classList.toggle('fa-eye-slash');
            });
        }
    };

    // Initialize password toggles
    togglePassword('password', 'togglePassword');
    togglePassword('confirmPassword', 'toggleConfirmPassword');

    // Show error message
    const showError = (field, message) => {
        let errorElement;
        
        if (field === 'register') {
            // General form error
            const errorContainer = document.getElementById('registerError');
            const errorMessage = document.getElementById('errorMessage');
            
            if (errorContainer && errorMessage) {
                errorMessage.textContent = message;
                errorContainer.classList.remove('hidden');
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            // Field-specific error
            errorElement = document.getElementById(`${field}Error`);
            const inputElement = document.getElementById(field);
            
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
                
                if (inputElement) {
                    inputElement.classList.add('border-red-500');
                    inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }
    };

    // Show success message
    const showSuccess = (message) => {
        // Hide any existing error messages
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
        });

        // Show success message
        const successContainer = document.getElementById('registerSuccess');
        const successMessage = document.getElementById('successMessage');
        
        if (successContainer && successMessage) {
            successMessage.textContent = message;
            successContainer.classList.remove('hidden');
            successContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Clear form
        form.reset();
    };

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Reset error states
        document.querySelectorAll('.error-message').forEach(el => {
            el.classList.add('hidden');
        });
        
        // Reset input borders
        document.querySelectorAll('input, select').forEach(input => {
            input.classList.remove('border-red-500');
        });
        
        // Get form data
        const formData = {
            first_name: document.getElementById('firstName')?.value.trim(),
            last_name: document.getElementById('lastName')?.value.trim(),
            email: document.getElementById('email')?.value.trim(),
            phone: document.getElementById('phone')?.value.trim(),
            address: document.getElementById('address')?.value.trim(),
            city: document.getElementById('city')?.value.trim(),
            state: document.getElementById('state')?.value.trim(),
            postal_code: document.getElementById('postalCode')?.value.trim(),
            country: document.getElementById('country')?.value.trim(),
            password: document.getElementById('password')?.value,
            confirmPassword: document.getElementById('confirmPassword')?.value,
            terms: document.getElementById('terms')?.checked
        };

        // Client-side validation
        let isValid = true;
        
        // Required fields
        const requiredFields = [
            { id: 'firstName', name: 'First name' },
            { id: 'lastName', name: 'Last name' },
            { id: 'email', name: 'Email' },
            { id: 'password', name: 'Password' },
            { id: 'confirmPassword', name: 'Confirm password' },
            { id: 'terms', name: 'Terms and conditions' }
        ];

        requiredFields.forEach(field => {
            if (!formData[field.id] || (field.id === 'terms' && !formData.terms)) {
                showError(field.id, `${field.name} is required`);
                isValid = false;
            }
        });

        // Email format
        if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            showError('email', 'Please enter a valid email address');
            isValid = false;
        }

        // Password strength
        if (formData.password && formData.password.length < 8) {
            showError('password', 'Password must be at least 8 characters long');
            isValid = false;
        }

        // Password match
        if (formData.password && formData.confirmPassword && formData.password !== formData.confirmPassword) {
            showError('confirmPassword', 'Passwords do not match');
            isValid = false;
        }

        if (!isValid) return;

        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating account...';

        try {
            // Prepare data for API (exclude confirmPassword and terms)
            const { confirmPassword, terms, ...userData } = formData;

            // Make API call
            const response = await fetch('http://localhost/hotel-management-system/api/auth/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Registration failed. Please try again.');
            }

            // Show success message
            showSuccess('Registration successful! Redirecting to login...');

            // Redirect to login after a short delay
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);

        } catch (error) {
            console.error('Registration error:', error);
            showError('register', error.message || 'An error occurred during registration. Please try again.');
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
});
