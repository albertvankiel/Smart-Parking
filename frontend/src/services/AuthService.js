const STORAGE_KEY = 'parking_auth_token';
const USER_EMAIL_KEY = 'parking_user_email';

export default {
	async login(username, password) {
		try {
			const response = await fetch('/api/login', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({email: username, password: password})
			});

			const data = await response.json();

			if (response.ok) {
				sessionStorage.setItem(STORAGE_KEY, data.token);
				sessionStorage.setItem(USER_EMAIL_KEY, username);
				return { success: true, username };
			} else {
				throw new Error(data.error || 'Invalid credentials');
			}
		} catch (error) {
			throw error;
		}
	},

	logout() {
		sessionStorage.removeItem(STORAGE_KEY);
		sessionStorage.removeItem(USER_EMAIL_KEY);
	},

	isLoggedIn() {
		return !!sessionStorage.getItem(STORAGE_KEY);
	},

	getCurrentUser() {
		return sessionStorage.getItem(USER_EMAIL_KEY) || 'User';
	},

	getToken() {
		return sessionStorage.getItem(STORAGE_KEY);
	}
};