/** public/assets/js/users.js **/
import apiService from './services/apiService.js';
import { authService } from './services/auth.service.js';

let users = [];

document.addEventListener('DOMContentLoaded', () => {
    bindUI();
    loadUsers();
    renderAdminBarIfAllowed();
});

function bindUI() {
    // No filters for users
}

async function loadUsers() {
    const container = document.getElementById('usersList');
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading users...</p>
        </div>`;

    try {
        const body = await apiService.getUsers();

        if (!body.success) throw new Error(body.message || 'Failed to fetch users');

        users = body.records || [];
        renderUsers(users);
        document.getElementById('resultsCount').textContent = `${users.length} users found`;

    } catch (err) {
        console.error('Frontend Error:', err);
        container.innerHTML = `
            <div class="alert alert-warning m-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Oops!</strong> ${err.message}
            </div>`;
    }
}

function renderUsers(usersToRender) {
    const container = document.getElementById('usersList');
    container.innerHTML = '';

    if (usersToRender.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No users found.</div>';
        return;
    }

    const table = document.createElement('div');
    table.className = 'table-responsive';
    table.innerHTML = `
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
            </tbody>
        </table>
    `;

    const tbody = table.querySelector('#usersTableBody');
    usersToRender.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.first_name || ''} ${user.last_name || ''}</td>
            <td>${user.email}</td>
            <td>${user.phone || ''}</td>
            <td><span class="badge bg-${getRoleBadgeColor(user.role)}">${user.role}</span></td>
            <td><span class="badge bg-${getStatusBadgeColor(user.status)}">${user.status}</span></td>
            <td>${new Date(user.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-user" data-id="${user.id}">Edit</button>
                <button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="${user.id}">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    container.appendChild(table);

    // Bind edit/delete buttons
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.target.getAttribute('data-id');
            openEditUser(id);
        });
    });

    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.target.getAttribute('data-id');
            confirmAndDeleteUser(id);
        });
    });
}

function getRoleBadgeColor(role) {
    switch (role) {
        case 'admin': return 'danger';
        case 'manager': return 'warning';
        case 'customer': return 'success';
        default: return 'secondary';
    }
}

function getStatusBadgeColor(status) {
    switch (status) {
        case 'active': return 'success';
        case 'suspended': return 'danger';
        case 'pending': return 'warning';
        default: return 'secondary';
    }
}

// Inject admin bar and modal
function renderAdminBarIfAllowed() {
    const user = authService.getCurrentUser();
    if (!user) return;
    const role = (user.role || '').toLowerCase();
    if (role !== 'admin') return;

    // Show Add User button
    const addBtn = document.getElementById('addUserBtn');
    if (addBtn) {
        addBtn.classList.remove('d-none');
        addBtn.addEventListener('click', () => showUserModal());
    }

    injectUserModal();
}

function injectUserModal() {
    if (document.getElementById('userModal')) return;

    const modal = document.createElement('div');
    modal.innerHTML = `
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="userModalTitle">Add User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="userModalAlert" style="display:none"></div>
            <form id="userForm">
              <input type="hidden" id="userId" />
              <div class="mb-3"><label class="form-label">Email</label><input id="userEmail" type="email" class="form-control" required></div>
              <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">First Name</label><input id="userFirstName" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Last Name</label><input id="userLastName" class="form-control"></div>
              </div>
              <div class="mb-3"><label class="form-label">Password</label><input id="userPassword" type="password" class="form-control"></div>
              <div class="mb-3"><label class="form-label">Role</label><select id="userRole" class="form-control" required>
                <option value="customer">Customer</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select></div>
              <div class="mb-3" id="professionalDetailsDiv" style="display:none;"><label class="form-label">Professional Details</label><textarea id="userProfessionalDetails" class="form-control" placeholder="JSON format for manager details"></textarea></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" id="userSaveBtn" class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(modal);

    // Bind role change
    document.getElementById('userRole').addEventListener('change', (e) => {
        const detailsDiv = document.getElementById('professionalDetailsDiv');
        detailsDiv.style.display = e.target.value === 'manager' ? 'block' : 'none';
    });

    // Bind save handler
    document.getElementById('userSaveBtn').addEventListener('click', async () => {
        const token = authService.getToken();
        const user = authService.getCurrentUser();
        const role = (user && user.role) ? String(user.role).toLowerCase() : null;
        if (!token || !user || role !== 'admin') {
            showModalAlert('You must be logged in as Admin to perform this action.', 'danger');
            return;
        }

        const id = document.getElementById('userId').value;
        const email = document.getElementById('userEmail').value.trim();
        const firstName = document.getElementById('userFirstName').value.trim();
        const lastName = document.getElementById('userLastName').value.trim();
        const phone = document.getElementById('userPhone').value.trim();
        const password = document.getElementById('userPassword').value;
        const userRole = document.getElementById('userRole').value;
        const userStatus = document.getElementById('userStatus').value;
        const professionalDetails = document.getElementById('userProfessionalDetails').value.trim();

        if (!email || !firstName) {
            showModalAlert('Email and First Name are required.', 'danger');
            return;
        }

        try {
            if (id) {
                // Update
                const payload = {
                    id: parseInt(id),
                    email,
                    first_name: firstName,
                    last_name: lastName,
                    phone,
                    role: userRole,
                    status: userStatus,
                    professional_details: professionalDetails || null
                };
                if (password) {
                    payload.password = password;
                }
                await apiService.updateUser(payload);
                showModalAlert('User updated successfully.', 'success');
            } else {
                // Create
                if (!password) {
                    showModalAlert('Password is required for new users.', 'danger');
                    return;
                }
                const payload = {
                    email,
                    first_name: firstName,
                    last_name: lastName,
                    phone,
                    password,
                    role: userRole,
                    status: userStatus,
                    professional_details: professionalDetails || null
                };
                await apiService.createUser(payload);
                showModalAlert('User created successfully.', 'success');
            }

            // Close and refresh
            setTimeout(() => {
                const modalEl = document.getElementById('userModal');
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                bsModal.hide();
                loadUsers();
            }, 700);
        } catch (err) {
            showModalAlert(err.message || 'Unable to save user.', 'danger');
        }
    });
}

function showModalAlert(msg, type = 'info') {
    const el = document.getElementById('userModalAlert');
    if (!el) return;
    el.style.display = 'block';
    el.className = `alert alert-${type}`;
    el.textContent = msg;
}

async function openEditUser(id) {
    try {
        // For edit, we need to get user details. Since list_users doesn't return password, we'll populate what we have
        const user = users.find(u => u.id == id);
        if (!user) throw new Error('User not found');

        document.getElementById('userId').value = user.id;
        document.getElementById('userEmail').value = user.email || '';
        document.getElementById('userFirstName').value = user.first_name || '';
        document.getElementById('userLastName').value = user.last_name || '';
        document.getElementById('userPhone').value = user.phone || '';
        document.getElementById('userPassword').value = ''; // Don't show password
        document.getElementById('userRole').value = user.role || 'customer';
        document.getElementById('userStatus').value = user.status || 'active';
        document.getElementById('userProfessionalDetails').value = user.professional_details || '';

        // Trigger role change
        const event = new Event('change');
        document.getElementById('userRole').dispatchEvent(event);

        document.getElementById('userModalTitle').textContent = 'Edit User';
        document.getElementById('userModalAlert').style.display = 'none';
        const modalEl = document.getElementById('userModal');
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    } catch (err) {
        alert(err.message || 'Unable to load user for editing');
    }
}

async function confirmAndDeleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
    try {
        await apiService.deleteUser(id);
        loadUsers();
    } catch (err) {
        alert(err.message || 'Unable to delete user');
    }
}

function showUserModal() {
    document.getElementById('userId').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userFirstName').value = '';
    document.getElementById('userLastName').value = '';
    document.getElementById('userPhone').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = 'customer';
    document.getElementById('userStatus').value = 'active';
    document.getElementById('userProfessionalDetails').value = '';
    document.getElementById('professionalDetailsDiv').style.display = 'none';
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userModalAlert').style.display = 'none';
    const modalEl = document.getElementById('userModal');
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}