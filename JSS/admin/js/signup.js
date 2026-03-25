/**
 * SignupManager - Handle admin account registration
 * Features: Form validation, password strength indicator, signup submission
 */
const SignupManager = (function () {
	// DOM elements
	let signupForm, nameInput, usernameInput, passwordInput, confirmPasswordInput, termsCheckbox;
	let nameError, usernameError, passwordError, confirmError, passwordStrength;
	let signupBtn;

	// Cache DOM elements
	function cacheDOMElements() {
		signupForm = document.getElementById('signupForm');
		nameInput = document.getElementById('name');
		usernameInput = document.getElementById('username');
		passwordInput = document.getElementById('password');
		confirmPasswordInput = document.getElementById('confirmPassword');
		termsCheckbox = document.getElementById('terms');
		nameError = document.getElementById('nameError');
		usernameError = document.getElementById('usernameError');
		passwordError = document.getElementById('passwordError');
		confirmError = document.getElementById('confirmError');
		passwordStrength = document.getElementById('passwordStrength');
		signupBtn = document.getElementById('signupBtn');
	}

	// Toggle password visibility
	function togglePasswordVisibility(fieldId) {
		const field = document.getElementById(fieldId);
		if (!field) return;

		// Find the toggle button and icon
		const passwordGroup = field.closest('.password-group');
		const toggle = passwordGroup ? passwordGroup.querySelector('.toggle-password') : null;
		const icon = toggle ? toggle.querySelector('i') : null;

		if (!icon) return;

		if (field.type === 'password') {
			field.type = 'text';
			icon.classList.remove('fa-eye');
			icon.classList.add('fa-eye-slash');
		} else {
			field.type = 'password';
			icon.classList.remove('fa-eye-slash');
			icon.classList.add('fa-eye');
		}
	}

	// Check password strength
	function checkPasswordStrength() {
		const password = passwordInput.value;

		if (password.length === 0) {
			passwordStrength.style.display = 'none';
			return;
		}

		let strength = 0;
		if (password.length >= 8) strength++;
		if (password.length >= 12) strength++;
		if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
		if (/\d/.test(password)) strength++;
		if (/[\W_]/.test(password)) strength++;

		passwordStrength.style.display = 'block';
		if (strength < 2) {
			passwordStrength.className = 'password-strength weak';
			passwordStrength.textContent = '❌ Weak password - Use 8+ characters, uppercase, lowercase, and numbers';
		} else if (strength < 4) {
			passwordStrength.className = 'password-strength medium';
			passwordStrength.textContent = '⚠️  Medium strength - Add special characters for better security';
		} else {
			passwordStrength.className = 'password-strength strong';
			passwordStrength.textContent = '✓ Strong password';
		}
	}

	// Clear all error messages
	function clearErrors() {
		document.querySelectorAll('.error-message').forEach(el => {
			el.style.display = 'none';
			el.textContent = '';
		});
	}

	// Validate form inputs
	function validateForm(name, username, password, confirmPassword, termsAccepted) {
		let isValid = true;

		if (!name) {
			nameError.textContent = 'Full name is required';
			nameError.style.display = 'block';
			isValid = false;
		} else if (name.length < 3) {
			nameError.textContent = 'Name must be at least 3 characters';
			nameError.style.display = 'block';
			isValid = false;
		}

		if (!username) {
			usernameError.textContent = 'Username is required';
			usernameError.style.display = 'block';
			isValid = false;
		} else if (username.length < 3) {
			usernameError.textContent = 'Username must be at least 3 characters';
			usernameError.style.display = 'block';
			isValid = false;
		} else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
			usernameError.textContent = 'Username can only contain letters, numbers, and underscores';
			usernameError.style.display = 'block';
			isValid = false;
		}

		if (!password) {
			passwordError.textContent = 'Password is required';
			passwordError.style.display = 'block';
			isValid = false;
		} else if (password.length < 6) {
			passwordError.textContent = 'Password must be at least 6 characters';
			passwordError.style.display = 'block';
			isValid = false;
		}

		if (password !== confirmPassword) {
			confirmError.textContent = 'Passwords do not match';
			confirmError.style.display = 'block';
			isValid = false;
		}

		if (!termsAccepted) {
			Swal.fire('Error', 'You must accept the Terms and Conditions', 'error');
			isValid = false;
		}

		return isValid;
	}

	// Handle form submission
	async function handleSignup(event) {
		event.preventDefault();
		clearErrors();

		const name = nameInput.value.trim();
		const username = usernameInput.value.trim();
		const password = passwordInput.value;
		const confirmPassword = confirmPasswordInput.value;
		const termsAccepted = termsCheckbox.checked;

		// Validate form
		if (!validateForm(name, username, password, confirmPassword, termsAccepted)) {
			return;
		}

		// Disable button and show loading state
		signupBtn.disabled = true;
		signupBtn.classList.add('loading');

		try {
			const res = await fetch('../backend/public/index.php/signup/register', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				credentials: 'include',
				body: JSON.stringify({
					name,
					username,
					password,
					confirm_password: confirmPassword
				})
			});

			const data = await res.json();

			if (res.ok && data.success) {
				Swal.fire(
					'Success!',
					'Admin account created successfully. Redirecting to login...',
					'success'
				).then(() => {
					window.location.href = 'login.html';
				});
			} else {
				Swal.fire('Error', data.message || 'Failed to create account', 'error');
			}
		} catch (err) {
			console.error('Error:', err);
			Swal.fire('Error', 'An error occurred: ' + err.message, 'error');
		} finally {
			signupBtn.disabled = false;
			signupBtn.classList.remove('loading');
		}
	}

	// Setup event listeners
	function setupEventListeners() {
		if (signupForm) {
			signupForm.addEventListener('submit', handleSignup);
		}

		if (passwordInput) {
			passwordInput.addEventListener('change', checkPasswordStrength);
			passwordInput.addEventListener('input', checkPasswordStrength);
		}

		// Password visibility toggle listeners
		document.querySelectorAll('.toggle-password').forEach(toggle => {
			toggle.addEventListener('click', function () {
				const passwordGroup = this.closest('.password-group');
				const field = passwordGroup.querySelector('input[type="password"], input[type="text"]');
				if (field) {
					togglePasswordVisibility(field.id);
				}
			});
		});
	}

	// Initialize
	function init() {
		cacheDOMElements();
		setupEventListeners();
	}

	// Expose global functions for inline onclick handlers
	if (typeof window !== 'undefined') {
		window.togglePasswordVisibility = togglePasswordVisibility;
		window.checkPasswordStrength = checkPasswordStrength;
	}

	return {
		init
	};
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
	SignupManager.init();
});
