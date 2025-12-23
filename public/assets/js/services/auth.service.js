class AuthService {
    constructor() {
        this.baseURL = '/hotelmanagement/api/auth';
    }

    async login(email, password) {
        try {
            const response = await fetch(`${this.baseURL}/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password }),
            });

            const data = await response.json();

            if (data.success) {
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                return data;
            } else {
                throw new Error(data.message || 'Login failed');
            }
        } catch (error) {
            throw error;
        }
    }

    async register(userData) {
        try {
            const response = await fetch(`${this.baseURL}/register.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData),
            });

            const data = await response.json();

            if (data.success) {
                return data;
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        } catch (error) {
            throw error;
        }
    }

    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }

    isAuthenticated() {
        const token = localStorage.getItem('token');
        return !!token;
    }

    getCurrentUser() {
        const user = localStorage.getItem('user');
        if (!user) return null;
        try {
            return JSON.parse(user);
        } catch (e) {
            return JSON.parse(decodeURIComponent(user));
        }
    }

    getToken() {
        return localStorage.getItem('token');
    }
}

export const authService = new AuthService();