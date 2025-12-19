import { auth } from './auth.js';
import apiService from './services/apiService.js';
import { initRoomsPage } from './pages/rooms.js';

const config = {
    apiBaseUrl: 'http://localhost/hotel-management-system/api'
};

// UI Components
const UI = {
    // Navigation
    initNavigation() {
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                navMenu.classList.toggle('hidden');
            });
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-container')) {
                navMenu.classList.add('hidden');
            }
        });
    },

    // Show loading state
    showLoading(element) {
        if (element) {
            element.innerHTML = `
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>
            `;
        }
    },

    // Show error message
    showError(message, element) {
        if (element) {
            element.innerHTML = `
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-bold">Error</p>
                            <p class="text-sm">${message}</p>
                        </div>
                    </div>
                </div>
            `;
        }
    },

    // Format date
    formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    },

    // Format currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }
};

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    UI.initNavigation();

    // Load the appropriate page based on the URL
    const path = window.location.pathname;
    const page = path.split('/').pop().replace('.html', '');

    if (page === 'login') {
        initLoginPage();
    } else if (page === 'register') {
        initRegisterPage();
    } else if (page === 'dashboard') {
        initDashboardPage();
    } else if (page === 'bookings') {
        initBookingsPage();
    } else if (page === 'rooms') {
        initRoomsPage();
    } else if (page === 'hotels' || page === 'index' || page === '') {
        initHomePage();
    }
});

// Initialize Login Page
function initLoginPage() {
    const loginForm = document.getElementById('loginForm');
    const errorContainer = document.getElementById('errorContainer');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = loginForm.querySelector('input[name="email"]').value.trim();
            const password = loginForm.querySelector('input[name="password"]').value;
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // Validate inputs
            if (!email || !password) {
                UI.showError('Please fill in all fields', errorContainer);
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Signing in...';

            try {
                // Call real login API
                const result = await auth.login(email, password);

                if (result.success) {
                    // Redirect to dashboard
                    window.location.href = 'dashboard.html';
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                console.error('Login error:', error);
                UI.showError(error.message || 'Invalid email or password.', errorContainer);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
}

// Initialize Register Page
// Initialize Register Page
function initRegisterPage() {
    const registerForm = document.getElementById('registerForm');
    const errorContainer = document.getElementById('errorContainer');

    if (registerForm) {
        // Password toggles
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordIcon = document.getElementById('passwordIcon');
        const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                passwordIcon.classList.toggle('fa-eye');
                passwordIcon.classList.toggle('fa-eye-slash');
            });
        }

        if (toggleConfirmPassword && confirmPasswordInput) {
            toggleConfirmPassword.addEventListener('click', () => {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                confirmPasswordIcon.classList.toggle('fa-eye');
                confirmPasswordIcon.classList.toggle('fa-eye-slash');
            });
        }

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Get form values
            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');

            // Basic validation
            if (!firstNameInput?.value || !lastNameInput?.value || !emailInput?.value || !passwordInput?.value) {
                UI.showError('Please fill in all required fields', errorContainer);
                return;
            }

            if (passwordInput.value !== confirmPasswordInput?.value) {
                UI.showError('Passwords do not match', errorContainer);
                return;
            }

            const formData = {
                first_name: firstNameInput.value.trim(),
                last_name: lastNameInput.value.trim(),
                email: emailInput.value.trim(),
                password: passwordInput.value,
                role: 'user' // Default role
            };

            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Creating account...';

            try {
                // Call real register API
                const result = await auth.register(formData);

                if (result.success) {
                    // Redirect to login with success message
                    window.location.href = 'login.html?registered=true';
                } else {
                    throw new Error(result.message);
                }

            } catch (error) {
                console.error('Registration error:', error);
                UI.showError(error.message || 'Registration failed.', errorContainer);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
}

// Initialize Dashboard Page
function initDashboardPage() {
    // Check if user is logged in (for demo, we'll just check if we're on the dashboard)
    if (window.location.pathname.includes('dashboard.html')) {
        // Load dashboard data
        loadDashboardData();
    }
}

// Load dashboard data
async function loadDashboardData() {
    const statsContainer = document.getElementById('statsContainer');
    const recentBookingsContainer = document.getElementById('recentBookings');

    if (statsContainer) UI.showLoading(statsContainer);
    if (recentBookingsContainer) UI.showLoading(recentBookingsContainer);

    try {
        // Fetch real data
        const [bookingStats, rooms, occupancyReport] = await Promise.all([
            apiService.getBookingStatistics(),
            apiService.getRooms(),
            apiService.getOccupancyReport()
        ]);

        // Initialize Charts
        initCharts(bookingStats.data || [], rooms.data || []);

        // Process stats
        const stats = {
            totalBookings: bookingStats.summary?.total_bookings || 0,
            availableRooms: rooms.data?.filter(r => r.status === 'Available').length || 0,
            totalRevenue: bookingStats.summary?.total_revenue || 0,
            occupancyRate: (occupancyReport.data?.[0]?.['Occupancy Rate %'] || 0) + '%'
        };

        // Process recent bookings
        const recentBookings = bookingStats.data?.slice(0, 5).map(b => ({
            id: b.id,
            guest: b.customer_email,
            room: b.room_type,
            checkIn: b.check_in,
            checkOut: b.check_out,
            status: b.status.charAt(0).toUpperCase() + b.status.slice(1)
        })) || [];

        // Render stats
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Bookings</p>
                                <p class="text-2xl font-bold">${stats.totalBookings}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-door-open text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Available Rooms</p>
                                <p class="text-2xl font-bold">${stats.availableRooms}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <i class="fas fa-dollar-sign text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Revenue</p>
                                <p class="text-2xl font-bold">${UI.formatCurrency(stats.totalRevenue)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Occupancy Rate</p>
                                <p class="text-2xl font-bold">${stats.occupancyRate}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Render recent bookings
        if (recentBookingsContainer) {
            recentBookingsContainer.innerHTML = `
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Bookings</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${recentBookings.map(booking => `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">${booking.guest}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${booking.room}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${UI.formatDate(booking.checkIn)}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${UI.formatDate(booking.checkOut)}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                ${booking.status === 'Confirmed' ? 'bg-green-100 text-green-800' :
                    booking.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'}">
                                                ${booking.status}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                            <a href="#" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">24</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                    </a>
                                    <a href="#" aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        1
                                    </a>
                                    <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        2
                                    </a>
                                    <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        3
                                    </a>
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right h-5 w-5"></i>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

    } catch (error) {
        console.error('Error loading dashboard data:', error);
        if (statsContainer) UI.showError('Failed to load dashboard data', statsContainer);
        if (recentBookingsContainer) UI.showError('Failed to load recent bookings', recentBookingsContainer);
    }
}

// Chart Logic
function initCharts(bookings, rooms) {
    // Initialize ApexCharts
    if (typeof ApexCharts === 'undefined') {
        console.warn('ApexCharts is not loaded. Charts will not be rendered.');
        return;
    }

    // Revenue Chart
    const revenueChartEl = document.getElementById('revenueChart');
    if (revenueChartEl) {
        // Destroy existing chart if it exists
        if (revenueChartEl._chart) {
            revenueChartEl._chart.destroy();
        }

        const revenueData = prepareRevenueData(bookings);
        const revenueOptions = {
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Revenue',
                data: revenueData.months.map(month => month.revenue)
            }],
            xaxis: {
                categories: revenueData.months.map(month => month.month),
                labels: {
                    style: { colors: '#6B7280', fontSize: '12px' }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) { return '$' + value.toLocaleString(); },
                    style: { colors: '#6B7280', fontSize: '12px' }
                }
            },
            colors: ['#3B82F6'],
            stroke: { width: 2, curve: 'smooth' },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100]
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) { return '$' + value.toLocaleString(); }
                }
            }
        };

        const chart = new ApexCharts(revenueChartEl, revenueOptions);
        chart.render();
        revenueChartEl._chart = chart;
    }

    // Occupancy Chart
    const occupancyChartEl = document.getElementById('occupancyChart');
    if (occupancyChartEl) {
        // Destroy existing chart if it exists
        if (occupancyChartEl._chart) {
            occupancyChartEl._chart.destroy();
        }

        const occupancyData = prepareOccupancyData(bookings, rooms);
        const occupancyOptions = {
            chart: { type: 'donut', height: 350 },
            series: [occupancyData.occupied, occupancyData.available],
            labels: ['Occupied', 'Available'],
            colors: ['#3B82F6', '#E5E7EB'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: { donut: { size: '65%' } }
            }
        };

        const chart = new ApexCharts(occupancyChartEl, occupancyOptions);
        chart.render();
        occupancyChartEl._chart = chart;
    }
}

function prepareRevenueData(bookings) {
    const months = [];
    const now = new Date();
    const currentYear = now.getFullYear();

    // Initialize last 6 months
    for (let i = 5; i >= 0; i--) {
        const date = new Date(currentYear, now.getMonth() - i, 1);
        months.push({
            month: date.toLocaleString('default', { month: 'short' }),
            revenue: 0
        });
    }

    // Calculate revenue per month
    bookings.forEach(booking => {
        const bookingDate = new Date(booking.created_at); // Note: API uses created_at
        const bookingMonth = bookingDate.getMonth();
        const bookingYear = bookingDate.getFullYear();

        // Only count bookings from relevant months
        if (bookingYear === currentYear || bookingYear === currentYear - 1) {
            const monthDiff = (now.getFullYear() - bookingYear) * 12 + (now.getMonth() - bookingMonth);
            if (monthDiff >= 0 && monthDiff < 6) {
                const index = 5 - monthDiff;
                if (index >= 0 && index < 6) {
                    months[index].revenue += parseFloat(booking.total_amount) || 0;
                }
            }
        }
    });

    return { months };
}

function prepareOccupancyData(bookings, rooms) {
    const totalRooms = rooms.length || 50;
    const now = new Date();

    // Count active bookings (checked_in or confirmed and within date range)
    const occupiedCount = bookings.filter(booking => {
        const checkIn = new Date(booking.check_in);
        const checkOut = new Date(booking.check_out);
        const status = booking.status.toLowerCase();

        return (status === 'confirmed' || status === 'checked_in') &&
            checkIn <= now &&
            checkOut >= now;
    }).length;

    return {
        occupied: occupiedCount,
        available: Math.max(0, totalRooms - occupiedCount)
    };
}

// Initialize Bookings Page
function initBookingsPage() {
    // Similar to dashboard, but focused on bookings
    loadBookings();
}

// Load Bookings
async function loadBookings() {
    const bookingsContainer = document.getElementById('bookingsContainer');

    if (bookingsContainer) {
        UI.showLoading(bookingsContainer);

        try {
            // Fetch real bookings
            const result = await apiService.getBookingStatistics();

            // Transform data for UI
            const bookings = result.data?.map(b => ({
                id: b.id,
                bookingNumber: `BK-${String(b.id).padStart(3, '0')}`,
                guest: b.customer_email,
                room: b.room_type,
                checkIn: b.check_in,
                checkOut: b.check_out,
                status: b.status.charAt(0).toUpperCase() + b.status.slice(1),
                total: b.total_amount
            })) || [];

            // Render bookings
            bookingsContainer.innerHTML = `
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">All Bookings</h3>
                        <div class="flex space-x-2">
                            <div class="relative">
                                <select class="block appearance-none bg-white border border-gray-300 text-gray-700 py-2 px-4 pr-8 rounded leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option>All Status</option>
                                    <option>Confirmed</option>
                                    <option>Pending</option>
                                    <option>Cancelled</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                                <i class="fas fa-plus mr-2"></i> New Booking
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                ${bookings.map(booking => `
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            ${booking.bookingNumber}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">${booking.guest}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${booking.room}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${UI.formatDate(booking.checkIn)}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">${UI.formatDate(booking.checkOut)}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium">${UI.formatCurrency(booking.total)}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                ${booking.status === 'Confirmed' ? 'bg-green-100 text-green-800' :
                    booking.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'}">
                                                ${booking.status}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <button class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">24</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                    </a>
                                    <a href="#" aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        1
                                    </a>
                                    <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        2
                                    </a>
                                    <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        3
                                    </a>
                                    <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right h-5 w-5"></i>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add event listeners for action buttons
            document.querySelectorAll('.view-booking').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const bookingId = button.getAttribute('data-booking-id');
                    viewBooking(bookingId);
                });
            });

        } catch (error) {
            console.error('Error loading bookings:', error);
            UI.showError('Failed to load bookings', bookingsContainer);
        }
    }
}

// View Booking Details
function viewBooking(bookingId) {
    // In a real app, this would fetch booking details and show a modal
    console.log('Viewing booking:', bookingId);
    alert(`Viewing booking #${bookingId}. This would open a modal in a real application.`);
}

// Initialize Home Page
function initHomePage() {
    // For the home page, we'll just make sure the navigation is working
    // and load any featured content
    loadFeaturedHotels();
}

// Load Featured Hotels
async function loadFeaturedHotels() {
    const featuredHotelsContainer = document.getElementById('featuredHotels');

    if (featuredHotelsContainer) {
        try {
            // Fetch real hotels
            const result = await apiService.getHotels();

            // Transform data for UI
            const hotels = result.data?.map(h => ({
                id: h.id,
                name: h.name,
                location: h.address + ', ' + h.city,
                price: 0, // Base price depends on room types, not in hotel list API usually
                rating: h.rating || 4.5,
                image: 'https://source.unsplash.com/random/800x600/?hotel' // Placeholder image
            })) || [];

            // Render featured hotels
            featuredHotelsContainer.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    ${hotels.map(hotel => `
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="relative h-48 overflow-hidden">
                                <img src="${hotel.image}" alt="${hotel.name}" class="w-full h-full object-cover">
                                <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-800 text-xs font-bold px-2 py-1 m-2 rounded-full flex items-center">
                                    <i class="fas fa-star mr-1"></i> ${hotel.rating}
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">${hotel.name}</h3>
                                <p class="text-gray-600 mb-4">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i> ${hotel.location}
                                </p>
                                <div class="flex justify-between items-center">
                                    <span class="text-2xl font-bold text-blue-600">${UI.formatCurrency(hotel.price)}</span>
                                    <span class="text-sm text-gray-500">per night</span>
                                </div>
                                <button class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                                    Book Now
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div class="mt-12 text-center">
                    <a href="#" class="inline-block bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-6 border border-gray-300 rounded-lg shadow">
                        View All Hotels
                    </a>
                </div>
            `;

        } catch (error) {
            console.error('Error loading featured hotels:', error);
            featuredHotelsContainer.innerHTML = `
                <div class="text-center py-12">
                    <div class="text-red-500 text-4xl mb-4">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Failed to load hotels</h3>
                    <p class="text-gray-600 mb-6">We're having trouble loading our featured hotels. Please try again later.</p>
                    <button onclick="loadFeaturedHotels()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                        <i class="fas fa-sync-alt mr-2"></i> Try Again
                    </button>
                </div>
            `;
        }
    }
}

// Make UI globally available for debugging
window.UI = UI;
