// User management page JavaScript
import { authService } from '../js/services/auth.service.js';
import apiService from '../js/services/apiService.js';

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication and role
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    const user = authService.getCurrentUser();
    if (user.role !== 'Admin') {
        window.location.href = 'index.html';
        return;
    }

    // Update UI for logged-in admin
    updateAuthUI(user);

    // Load users
    loadUsers();

    // Setup event listeners
    document.getElementById('saveUserBtn').addEventListener('click', saveUser);
    document.getElementById('role').addEventListener('change', function() {
        const professionalDetails = document.getElementById('professionalDetails');
        professionalDetails.style.display = this.value === 'Manager' ? 'block' : 'none';
    });

    // Setup logout
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        authService.logout();
    });
});

function updateAuthUI(user) {
    document.getElementById('userName').textContent = user.first_name || 'Admin';
}

async function loadUsers() {
    const container = document.getElementById('usersContainer');

    try {
        // Note: The API doesn't have a list users endpoint yet, so we'll show a placeholder
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5>User Management</h5>
                    <p class="text-muted">User management functionality will be implemented here.</p>
                    <p class="text-muted small">This would allow admins to view, add, edit, and manage user accounts.</p>
                    <div class="mt-3">
                        <div class="row text-start">
                            <div class="col-md-4">
                                <h6>Current Users:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-user-shield text-primary me-2"></i>Admin User (admin@example.com)</li>
                                    <li><i class="fas fa-user-tie text-success me-2"></i>Manager accounts</li>
                                    <li><i class="fas fa-user text-info me-2"></i>Customer accounts</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Features:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-plus me-2"></i>Add new users</li>
                                    <li><i class="fas fa-edit me-2"></i>Edit user details</li>
                                    <li><i class="fas fa-ban me-2"></i>Deactivate accounts</li>
                                    <li><i class="fas fa-key me-2"></i>Reset passwords</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Role Management:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-crown text-warning me-2"></i>Admin: Full access</li>
                                    <li><i class="fas fa-briefcase text-success me-2"></i>Manager: Hotel management</li>
                                    <li><i class="fas fa-user text-info me-2"></i>Customer: Booking access</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading users:', error);
        showAlert('Failed to load users. Please try again.', 'danger');
    }
}

function saveUser() {
    // Get form data
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('email').value.trim();
    const role = document.getElementById('role').value;
    const professional = document.getElementById('professional').value.trim();

    // Validation
    if (!firstName || !lastName || !email || !role) {
        showAlert('Please fill in all required fields.', 'danger');
        return;
    }

    if (!isValidEmail(email)) {
        showAlert('Please enter a valid email address.', 'danger');
        return;
    }

    // Prepare user data
    const userData = {
        first_name: firstName,
        last_name: lastName,
        email: email,
        role: role
    };

    if (role === 'Manager' && professional) {
        userData.professional_details = professional;
    }

    // In a real implementation, this would call the API
    console.log('Saving user:', userData);
    showAlert('User saved successfully! (This is a placeholder)', 'success');

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
    if (modal) {
        modal.hide();
    }

    // Reset form
    document.getElementById('userForm').reset();
    document.getElementById('professionalDetails').style.display = 'none';
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('usersAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}