import { auth } from './auth.js';

// Navigation component
export class Navigation {
    constructor() {
        this.routes = {
            '#/': 'Home',
            '#/hotels': 'Hotels',
            '#/bookings': 'My Bookings',
            '#/login': 'Login',
            '#/register': 'Register',
            '#/profile': 'Profile',
            '#/logout': 'Logout'
        };
        
        // Bind methods
        this.handleRouteChange = this.handleRouteChange.bind(this);
    }

    // Render navigation based on current route
    render(path) {
        path = path || window.location.hash || '#/';
        const isAuthenticated = auth.isAuthenticated();
        const userRole = isAuthenticated ? auth.getCurrentUserRole() : null;
        
        let navItems = [
            { path: '#/', label: 'Home' },
            { path: '#/hotels', label: 'Hotels' }
        ];

        // Add role-specific navigation
        if (isAuthenticated) {
            navItems.push({ path: '#/bookings', label: 'My Bookings' });
            
            if (userRole === 'Admin' || userRole === 'Manager') {
                navItems.push({ path: '#/dashboard', label: 'Dashboard' });
            }
            
            navItems.push(
                { path: '#/profile', label: 'Profile' },
                { path: '#', label: 'Logout', onClick: () => auth.logout() }
            );
        } else {
            navItems.push(
                { path: '#/login', label: 'Login' },
                { path: '#/register', label: 'Register' }
            );
        }

        // Generate navigation HTML
        const navHtml = `
            <nav class="bg-white shadow-lg">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="flex-shrink-0 flex items-center">
                                <a href="#/" class="text-xl font-bold text-blue-600">
                                    HotelMS
                                </a>
                            </div>
                            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                                ${navItems.slice(0, -2).map(item => this.renderNavItem(item)).join('')}
                            </div>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:items-center">
                            ${navItems.slice(-2).map(item => this.renderNavItem(item, true)).join('')}
                        </div>
                    </div>
                </div>
            </nav>
        `;

        // Insert navigation into the page
        const navElement = document.getElementById('nav');
        if (navElement) {
            navElement.innerHTML = navHtml;
        }
    }

    // Render a single navigation item
    renderNavItem(item, isButton = false) {
        const isActive = window.location.hash === item.path;
        const activeClass = isActive ? 'text-blue-600 border-blue-500' : 'text-gray-700 hover:text-blue-600';
        
        if (item.onClick) {
            return `
                <button onclick="${item.onClick}" 
                        class="ml-4 px-3 py-2 rounded-md text-sm font-medium ${activeClass} hover:bg-gray-100">
                    ${item.label}
                </button>
            `;
        }
        
        if (isButton) {
            return `
                <a href="${item.path}" data-route class="ml-4 px-3 py-2 rounded-md text-sm font-medium ${isActive ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-700 hover:text-white'}">
                    ${item.label}
                </a>
            `;
        }
        
        return `
            <a href="${item.path}" data-route class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium ${isActive ? 'border-blue-500' : 'border-transparent hover:border-gray-300'}">
                ${item.label}
            </a>
        `;
    }

    // Initialize router
    initRouter() {
        // Initial render
        this.handleRouteChange();
        
        // Listen for hash changes
        window.addEventListener('hashchange', this.handleRouteChange);
        
        // Handle click events on navigation links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-route]');
            if (link) {
                e.preventDefault();
                const href = link.getAttribute('href');
                if (href) {
                    window.location.hash = href;
                }
                window.location.hash = path;
            }
        });
        
        // Initial render
        this.handleRouteChange();
    }
    
    // Handle route changes
    handleRouteChange() {
        const path = window.location.hash || '#/';
        this.render(path);
    }

    // Navigate to a specific route
    navigateTo(path) {
        if (path.startsWith('http')) {
            window.location.href = path;
            return;
        }

        window.history.pushState({}, '', path);
        this.render();
        this.loadPage(path);
    }

    // Load page content based on route
    async loadPage(path) {
        const contentElement = document.getElementById('content');
        if (!contentElement) return;

        // Show loading state
        contentElement.innerHTML = '<div class="spinner"></div>';

        try {
            let html = '';
            
            // Simple client-side routing
            if (path.endsWith('/login')) {
                html = await this.loadLoginPage();
            } else if (path.endsWith('/register')) {
                html = await this.loadRegisterPage();
            } else if (path.endsWith('/hotels')) {
                html = await this.loadHotelsPage();
            } else if (path.endsWith('/bookings')) {
                html = await this.loadBookingsPage();
            } else if (path.endsWith('/profile')) {
                html = await this.loadProfilePage();
            } else {
                // Default home page
                html = `
                    <div class="text-center py-10">
                        <h1 class="text-4xl font-bold text-gray-800 mb-4">Welcome to Hotel Management System</h1>
                        <p class="text-xl text-gray-600">Book your perfect stay with us</p>
                        <div class="mt-8">
                            <a href="/hotel-management-system/public/hotels" 
                               class="btn btn-primary inline-block">
                                Browse Hotels
                            </a>
                        </div>
                    </div>
                `;
            }
            
            contentElement.innerHTML = html;
            this.attachEventListeners();
        } catch (error) {
            console.error('Error loading page:', error);
            contentElement.innerHTML = `
                <div class="alert alert-error">
                    Error loading page. Please try again later.
                </div>
            `;
        }
    }

    // Load login page
    async loadLoginPage() {
        return `
            <div class="max-w-md mx-auto mt-10 bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
                <form id="loginForm" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required 
                               class="form-input mt-1" placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required 
                               class="form-input mt-1" placeholder="Enter your password">
                    </div>
                    <div>
                        <button type="submit" class="w-full btn btn-primary">
                            Sign In
                        </button>
                    </div>
                    <div class="text-center text-sm">
                        Don't have an account? 
                        <a href="/hotel-management-system/public/register" 
                           class="text-blue-600 hover:underline">Register here</a>
                    </div>
                </form>
                <div id="loginError" class="mt-4 text-red-600 text-sm hidden"></div>
            </div>
        `;
    }

    // Load register page
    async loadRegisterPage() {
        return `
            <div class="max-w-md mx-auto mt-10 bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-center mb-6">Create an Account</h2>
                <form id="registerForm" class="space-y-6">
                    <div>
                        <label for="regEmail" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="regEmail" name="email" required 
                               class="form-input mt-1" placeholder="Enter your email">
                    </div>
                    <div>
                        <label for="regPassword" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="regPassword" name="password" required 
                               class="form-input mt-1" placeholder="Create a password">
                    </div>
                    <div>
                        <label for="regConfirmPassword" class="block text-sm font-medium text-gray-700">
                            Confirm Password
                        </label>
                        <input type="password" id="regConfirmPassword" name="confirmPassword" required 
                               class="form-input mt-1" placeholder="Confirm your password">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Account Type</label>
                        <select id="role" name="role" class="form-input mt-1">
                            <option value="Customer">Customer</option>
                            <option value="Manager">Hotel Manager</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full btn btn-primary">
                            Create Account
                        </button>
                    </div>
                    <div class="text-center text-sm">
                        Already have an account? 
                        <a href="/hotel-management-system/public/login" 
                           class="text-blue-600 hover:underline">Sign in here</a>
                    </div>
                </form>
                <div id="registerError" class="mt-4 text-red-600 text-sm hidden"></div>
            </div>
        `;
    }

    // Load hotels page
    async loadHotelsPage() {
        try {
            const response = await axios.get('http://localhost/hotel-management-system/api/hotel/list_hotels.php');
            const hotels = response.data.data || [];
            
            if (hotels.length === 0) {
                return `
                    <div class="text-center py-10">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">No Hotels Found</h2>
                        <p class="text-gray-600">There are currently no hotels available.</p>
                    </div>
                `;
            }

            const hotelsHtml = hotels.map(hotel => `
                <div class="card mb-6">
                    <div class="md:flex">
                        <div class="md:flex-shrink-0">
                            <img class="h-48 w-full md:w-48 object-cover" 
                                 src="https://via.placeholder.com/300x200" 
                                 alt="${hotel.name}">
                        </div>
                        <div class="p-8">
                            <div class="uppercase tracking-wide text-sm text-blue-600 font-semibold">
                                ${hotel.location}
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">${hotel.name}</h3>
                            <p class="mt-2 text-gray-600">${hotel.description || 'No description available.'}</p>
                            <div class="mt-4">
                                <a href="/hotel-management-system/public/hotels/${hotel.id}" 
                                   class="btn btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            return `
                <div class="py-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-8">Our Hotels</h1>
                    <div class="grid gap-6">
                        ${hotelsHtml}
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading hotels:', error);
            return `
                <div class="alert alert-error">
                    Error loading hotels. Please try again later.
                </div>
            `;
        }
    }

    // Load bookings page
    async loadBookingsPage() {
        if (!auth.isAuthenticated()) {
            return `
                <div class="text-center py-10">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Please Sign In</h2>
                    <p class="text-gray-600 mb-4">You need to be signed in to view your bookings.</p>
                    <a href="/hotel-management-system/public/login" 
                       class="btn btn-primary">
                        Sign In
                    </a>
                </div>
            `;
        }

        try {
            const response = await axios.get('http://localhost/hotel-management-system/api/booking/booking_history.php');
            const bookings = response.data.data || [];
            
            if (bookings.length === 0) {
                return `
                    <div class="text-center py-10">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">No Bookings Found</h2>
                        <p class="text-gray-600">You haven't made any bookings yet.</p>
                        <div class="mt-4">
                            <a href="/hotel-management-system/public/hotels" 
                               class="btn btn-primary">
                                Browse Hotels
                            </a>
                        </div>
                    </div>
                `;
            }

            const bookingsHtml = bookings.map(booking => `
                <div class="card mb-6 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold">${booking.hotel_name}</h3>
                            <p class="text-gray-600">Room: ${booking.room_type}</p>
                            <p class="text-gray-600">
                                ${new Date(booking.check_in).toDateString()} - ${new Date(booking.check_out).toDateString()}
                            </p>
                            <span class="inline-block mt-2 px-3 py-1 text-sm font-semibold rounded-full 
                                ${booking.status === 'Confirmed' ? 'bg-green-100 text-green-800' : 
                                  booking.status === 'Cancelled' ? 'bg-red-100 text-red-800' : 
                                  'bg-yellow-100 text-yellow-800'}">
                                ${booking.status}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold">$${booking.total_price}</p>
                            <p class="text-sm text-gray-500">${booking.nights} nights</p>
                        </div>
                    </div>
                </div>
            `).join('');

            return `
                <div class="py-6">
                    <div class="flex justify-between items-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">My Bookings</h1>
                        <a href="/hotel-management-system/public/hotels" 
                           class="btn btn-primary">
                            Book a Room
                        </a>
                    </div>
                    <div class="space-y-4">
                        ${bookingsHtml}
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading bookings:', error);
            return `
                <div class="alert alert-error">
                    Error loading your bookings. Please try again later.
                </div>
            `;
        }
    }

    // Load profile page
    async loadProfilePage() {
        if (!auth.isAuthenticated()) {
            return `
                <div class="text-center py-10">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Please Sign In</h2>
                    <p class="text-gray-600 mb-4">You need to be signed in to view your profile.</p>
                    <a href="/hotel-management-system/public/login" 
                       class="btn btn-primary">
                        Sign In
                    </a>
                </div>
            `;
        }

        try {
            const response = await axios.get('http://localhost/hotel-management-system/api/user/profile.php');
            const user = response.data.data;
            
            return `
                <div class="max-w-3xl mx-auto py-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>
                    
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div class="px-4 py-5 sm:px-6 bg-gray-50">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Personal Information
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Your account details and preferences.
                            </p>
                        </div>
                        <div class="border-t border-gray-200">
                            <dl>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">
                                        Email
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                        ${user.email}
                                    </dd>
                                </div>
                                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">
                                        Role
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                        ${user.role}
                                    </dd>
                                </div>
                                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500">
                                        Member Since
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                        ${new Date(user.created_at).toLocaleDateString()}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <button id="logoutBtn" class="btn btn-secondary">
                            Sign Out
                        </button>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading profile:', error);
            return `
                <div class="alert alert-error">
                    Error loading your profile. Please try again later.
                </div>
            `;
        }
    }

    // Attach event listeners to dynamic elements
    attachEventListeners() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }

        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', this.handleRegister.bind(this));
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => auth.logout());
        }
    }

    // Handle login form submission
    async handleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorElement = document.getElementById('loginError');

        try {
            const result = await auth.login(email, password);
            if (result.success) {
                window.location.href = '/hotel-management-system/public';
            } else {
                errorElement.textContent = result.message || 'Login failed. Please try again.';
                errorElement.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Login error:', error);
            errorElement.textContent = 'An error occurred. Please try again.';
            errorElement.classList.remove('hidden');
        }
    }

    // Handle register form submission
    async handleRegister(e) {
        e.preventDefault();
        const email = document.getElementById('regEmail').value;
        const password = document.getElementById('regPassword').value;
        const confirmPassword = document.getElementById('regConfirmPassword').value;
        const role = document.getElementById('role').value;
        const errorElement = document.getElementById('registerError');

        // Basic validation
        if (password !== confirmPassword) {
            errorElement.textContent = 'Passwords do not match.';
            errorElement.classList.remove('hidden');
            return;
        }

        try {
            const result = await auth.register({
                email,
                password,
                role,
                details: ''
            });

            if (result.success) {
                // Auto-login after registration
                const loginResult = await auth.login(email, password);
                if (loginResult.success) {
                    window.location.href = '/hotel-management-system/public';
                }
            } else {
                errorElement.textContent = result.message || 'Registration failed. Please try again.';
                errorElement.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Registration error:', error);
            errorElement.textContent = 'An error occurred. Please try again.';
            errorElement.classList.remove('hidden');
        }
    }
}

// Initialize navigation when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const navigation = new Navigation();
    navigation.initRouter();
    
    // Make navigation available globally for debugging
    window.navigation = navigation;
});
