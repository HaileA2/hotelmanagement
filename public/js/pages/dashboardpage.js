// public/js/pages/DashboardPage.js
import apiService from '../services/apiService.js';

class DashboardPage {
    constructor() {
        this.init();
    }

    async init() {
        try {
            // Check if user is authenticated
            if (!apiService.token) {
                window.location.href = '/hotel-management-system/public/login.html';
                return;
            }

            // Load all data in parallel
            const [bookings, hotels, rooms] = await Promise.all([
                this.fetchBookings(),
                this.fetchHotels(),
                this.fetchRooms()
            ]);

            this.renderDashboard(bookings, hotels, rooms);
            this.initCharts(bookings);
            this.setupEventListeners();
        } catch (error) {
            console.error('Error initializing dashboard:', error);
            this.showError('Failed to load dashboard data. Please try again later.');
        }
    }

    async fetchBookings() {
        try {
            const response = await apiService.getBookings();
            return response.data || [];
        } catch (error) {
            console.error('Error fetching bookings:', error);
            return [];
        }
    }

    async fetchHotels() {
        try {
            const response = await apiService.getHotels();
            return response.data || [];
        } catch (error) {
            console.error('Error fetching hotels:', error);
            return [];
        }
    }

    async fetchRooms() {
        try {
            const response = await apiService.getRooms();
            return response.data || [];
        } catch (error) {
            console.error('Error fetching rooms:', error);
            return [];
        }
    }

    renderDashboard(bookings, hotels, rooms) {
        this.updateStats(bookings, hotels, rooms);
        this.renderRecentBookings(bookings);
        this.renderUpcomingCheckIns(bookings);
    }

    updateStats(bookings, hotels, rooms) {
        // Update stats cards
        const totalBookings = bookings.length;
        const availableRooms = rooms.filter(room => room.status === 'available').length;
        const todayCheckIns = bookings.filter(booking => {
            const checkInDate = new Date(booking.checkInDate).toDateString();
            const today = new Date().toDateString();
            return checkInDate === today && booking.status === 'confirmed';
        }).length;
        const monthlyRevenue = bookings
            .filter(booking => {
                const bookingDate = new Date(booking.bookingDate);
                const now = new Date();
                return bookingDate.getMonth() === now.getMonth() && 
                       bookingDate.getFullYear() === now.getFullYear();
            })
            .reduce((sum, booking) => sum + (parseFloat(booking.totalAmount) || 0), 0);

        // Update DOM elements
        document.getElementById('totalBookings').textContent = totalBookings;
        document.getElementById('availableRooms').textContent = availableRooms;
        document.getElementById('todayCheckIns').textContent = todayCheckIns;
        document.getElementById('monthlyRevenue').textContent = `$${monthlyRevenue.toFixed(2)}`;
    }

    renderRecentBookings(bookings) {
        const tbody = document.querySelector('#recentBookings');
        if (!tbody) return;

        // Sort by most recent first
        const recentBookings = [...bookings]
            .sort((a, b) => new Date(b.bookingDate) - new Date(a.bookingDate))
            .slice(0, 5);

        tbody.innerHTML = recentBookings.map(booking => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${booking.bookingId || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${booking.guestName || 'N/A'}</div>
                    <div class="text-sm text-gray-500">${booking.roomType || ''}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${new Date(booking.checkInDate).toLocaleDateString()}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        ${this.getStatusBadgeClass(booking.status)}">
                        ${booking.status || 'N/A'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="booking-details.html?id=${booking.id}" class="text-indigo-600 hover:text-indigo-900">View</a>
                </td>
            </tr>
        `).join('') || `
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    No recent bookings found.
                </td>
            </tr>`;
    }

    renderUpcomingCheckIns(bookings) {
        const container = document.getElementById('upcomingCheckIns');
        if (!container) return;

        const today = new Date();
        const nextWeek = new Date();
        nextWeek.setDate(today.getDate() + 7);

        const upcomingCheckIns = bookings
            .filter(booking => {
                const checkInDate = new Date(booking.checkInDate);
                return checkInDate >= today && 
                       checkInDate <= nextWeek && 
                       booking.status === 'confirmed';
            })
            .sort((a, b) => new Date(a.checkInDate) - new Date(b.checkInDate));

        container.innerHTML = upcomingCheckIns.map(booking => `
            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-user text-indigo-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${booking.guestName || 'Guest'}</div>
                        <div class="text-sm text-gray-500">${booking.roomType || 'Room'}</div>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    ${new Date(booking.checkInDate).toLocaleDateString()}
                </div>
            </div>
        `).join('') || '<p class="text-gray-500 text-sm py-4 text-center">No upcoming check-ins</p>';
    }

    getStatusBadgeClass(status) {
        const statusClasses = {
            'confirmed': 'bg-green-100 text-green-800',
            'pending': 'bg-yellow-100 text-yellow-800',
            'cancelled': 'bg-red-100 text-red-800',
            'completed': 'bg-blue-100 text-blue-800'
        };
        return statusClasses[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
    }

    initCharts(bookings) {
        // Initialize ApexCharts
        if (typeof ApexCharts === 'undefined') {
            console.warn('ApexCharts is not loaded. Charts will not be rendered.');
            return;
        }

        // Revenue Chart
        const revenueChartEl = document.getElementById('revenueChart');
        if (revenueChartEl) {
            const revenueData = this.prepareRevenueData(bookings);
            const revenueOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Revenue',
                    data: revenueData.months.map(month => month.revenue)
                }],
                xaxis: {
                    categories: revenueData.months.map(month => month.month),
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return '$' + value.toLocaleString();
                        },
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    }
                },
                colors: ['#3B82F6'],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
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
                        formatter: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            };

            this.revenueChart = new ApexCharts(revenueChartEl, revenueOptions);
            this.revenueChart.render();
        }

        // Occupancy Chart
        const occupancyChartEl = document.getElementById('occupancyChart');
        if (occupancyChartEl) {
            const occupancyData = this.prepareOccupancyData(bookings);
            const occupancyOptions = {
                chart: {
                    type: 'donut',
                    height: 350
                },
                series: [occupancyData.occupied, occupancyData.available],
                labels: ['Occupied', 'Available'],
                colors: ['#3B82F6', '#E5E7EB'],
                legend: {
                    position: 'bottom'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%'
                        }
                    }
                }
            };

            this.occupancyChart = new ApexCharts(occupancyChartEl, occupancyOptions);
            this.occupancyChart.render();
        }
    }

    prepareRevenueData(bookings) {
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
            const bookingDate = new Date(booking.bookingDate);
            const bookingMonth = bookingDate.getMonth();
            const bookingYear = bookingDate.getFullYear();
            
            // Only count bookings from current year
            if (bookingYear === currentYear) {
                const monthIndex = now.getMonth() - (5 - (months.length - 1 - (now.getMonth() - bookingMonth)));
                if (monthIndex >= 0 && monthIndex < months.length) {
                    months[monthIndex].revenue += parseFloat(booking.totalAmount) || 0;
                }
            }
        });

        return { months };
    }

    prepareOccupancyData(bookings) {
        const totalRooms = 50; // This should come from your API
        const occupiedRooms = bookings.filter(booking => 
            booking.status === 'confirmed' && 
            new Date(booking.checkInDate) <= new Date() && 
            new Date(booking.checkOutDate) >= new Date()
        ).length;

        return {
            occupied: occupiedRooms,
            available: Math.max(0, totalRooms - occupiedRooms)
        };
    }

    setupEventListeners() {
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                apiService.setToken(null);
                window.location.href = '/hotel-management-system/public/login.html';
            });
        }

        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                window.location.reload();
            });
        }
    }

    showError(message) {
        const errorContainer = document.getElementById('errorContainer');
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.classList.remove('hidden');
            setTimeout(() => {
                errorContainer.classList.add('hidden');
            }, 5000);
        }
    }
}

// Initialize the dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DashboardPage();
});