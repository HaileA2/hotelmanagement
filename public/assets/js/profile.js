// Profile page JavaScript
import { authService } from '../js/services/auth.service.js';
import { apiService } from '../js/services/api.service.js';

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    if (!authService.isAuthenticated()) {
        window.location.href = 'login.html';
        return;
    }

    // Update UI for logged-in user
    updateAuthUI();

    // Load user profile
    loadUserProfile();

    // Setup form submission
    document.getElementById('profileForm').addEventListener('submit', updateProfile);

    // Setup logout
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        authService.logout();
    });

    // Setup delete account (placeholder)
    document.getElementById('deleteAccountBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            alert('Account deletion feature would be implemented here.');
        }
    });
});

function updateAuthUI() {
    const user = authService.getCurrentUser();
    if (user) {
        document.getElementById('loginBtn').style.display = 'none';
        document.getElementById('registerBtn').style.display = 'none';
        document.getElementById('userMenu').style.display = 'block';
        document.getElementById('userName').textContent = user.first_name || 'User';
    }
}

async function loadUserProfile() {
    try {
        const response = await apiService.getCurrentUser();
        const user = response.user || authService.getCurrentUser();

        // Populate form fields
        document.getElementById('firstName').value = user.first_name || '';
        document.getElementById('lastName').value = user.last_name || '';
        document.getElementById('email').value = user.email || '';
        document.getElementById('phone').value = user.phone || '';
        document.getElementById('role').value = getRoleDisplayName(user.role);

        // Show professional details for managers
        if (user.role === 'Manager') {
            document.getElementById('professionalSection').style.display = 'block';
            document.getElementById('professional').value = user.professional_details || '';
        }

        // Update account summary
        document.getElementById('fullName').textContent = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'User';
        document.getElementById('accountEmail').textContent = user.email || '';
        document.getElementById('accountRole').textContent = getRoleDisplayName(user.role);
        document.getElementById('memberSince').textContent = `Member since: ${formatDate(user.created_at)}`;

    } catch (error) {
        console.error('Error loading profile:', error);
        showAlert('Failed to load profile information. Please try again.', 'danger');
    }
}

async function updateProfile(e) {
    e.preventDefault();

    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const professional = document.getElementById('professional').value.trim();

    // Validation
    if (!firstName || !lastName) {
        showAlert('Please fill in all required fields.', 'danger');
        return;
    }

    // Show loading state
    const saveBtn = document.getElementById('saveProfileBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';

    try {
        const profileData = {
            first_name: firstName,
            last_name: lastName,
            phone: phone
        };

        const user = authService.getCurrentUser();
        if (user.role === 'Manager') {
            profileData.professional_details = professional;
        }

        await apiService.updateProfile(profileData);

        showAlert('Profile updated successfully!', 'success');

        // Update local user data
        const updatedUser = { ...user, ...profileData };
        localStorage.setItem('user', JSON.stringify(updatedUser));

        // Update UI
        updateAuthUI();

    } catch (error) {
        console.error('Error updating profile:', error);
        showAlert('Failed to update profile. Please try again.', 'danger');
    } finally {
        // Reset button state
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes';
    }
}

function getRoleDisplayName(role) {
    switch (role) {
        case 'Admin': return 'Administrator';
        case 'Manager': return 'Hotel Manager';
        case 'Customer': return 'Customer';
        default: return role;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short'
    });
}

function showAlert(message, type = 'danger') {
    const alert = document.getElementById('profileAlert');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}