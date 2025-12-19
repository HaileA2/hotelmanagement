import { auth } from '../auth.js';
import { showAlert } from '../utils/alert.js';

export function renderHome() {
    console.log('Rendering home page...');
    
    // If user is already logged in, redirect to hotels page
    if (auth.isAuthenticated()) {
        console.log('User is already authenticated, redirecting to /hotels');
        window.location.hash = '/hotels';
        return;
    }

    // Set up login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        console.log('Login form found, adding event listener');
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Login form submitted');
            
            const email = document.getElementById('email')?.value;
            const password = document.getElementById('password')?.value;
            
            if (!email || !password) {
                showAlert('Please enter both email and password', 'error');
                return;
            }
            
            const submitButton = loginForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton?.innerHTML;
            
            try {
                // Show loading state
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = 'Signing in...';
                }
                
                console.log('Attempting login with:', { email });
                await auth.login(email, password);
                
                console.log('Login successful, redirecting...');
                showAlert('Login successful!', 'success');
                window.location.href = '/hotel-management-system/public/hotels.html';
                
            } catch (error) {
                console.error('Login error:', error);
                const message = error.response?.data?.message || 'Login failed. Please check your credentials and try again.';
                showAlert(message, 'error');
            } finally {
                // Reset button state
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText || 'Sign In';
                }
            }
        });
    } else {
        console.warn('Login form not found on the page');
    }
}

// Initialize home page when loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (window.location.pathname === '/' || window.location.pathname.endsWith('index.html')) {
            renderHome();
        }
    });
} else {
    // DOMContentLoaded has already fired
    if (window.location.pathname === '/' || window.location.pathname.endsWith('index.html')) {
        renderHome();
    }
}
