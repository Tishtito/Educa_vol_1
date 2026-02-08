(async function () {
    const baseUrl = "../backend/public/index.php";
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const successMessage = document.getElementById('successMessage');
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordField = document.getElementById('password');
    const usernameField = document.getElementById('username');

    // Floating label handler
    function handleFloatingLabel(inputElement) {
        const formGroup = inputElement.closest('.form-group');
        const label = formGroup.querySelector('label');
        
        function updateLabelPosition() {
            if (inputElement.value.trim() !== '' || document.activeElement === inputElement) {
                label.classList.add('float');
            } else {
                label.classList.remove('float');
            }
        }

        inputElement.addEventListener('focus', updateLabelPosition);
        inputElement.addEventListener('blur', updateLabelPosition);
        inputElement.addEventListener('input', updateLabelPosition);

        // Initial check
        updateLabelPosition();
    }

    // Initialize floating labels
    handleFloatingLabel(usernameField);
    handleFloatingLabel(passwordField);

    // Password toggle
    if (passwordToggle) {
        passwordToggle.addEventListener('click', (e) => {
            e.preventDefault();
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            e.target.closest('.password-toggle').querySelector('.eye-icon').classList.toggle('show-password');
        });
    }

    // Check if already logged in
    try {
        const authRes = await fetch(`${baseUrl}/auth/check`, { credentials: "include" });
        const auth = await authRes.json();
        if (auth.success) {
            window.location.replace("../pages/home.html");
            return;
        }
    } catch (error) {
        console.error('Auth check failed', error);
    }

    // Form submission
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Reset errors
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
                el.classList.remove('show');
            });
            document.querySelectorAll('.form-group').forEach(el => {
                el.classList.remove('error');
            });
            
            const username = usernameField.value.trim();
            const password = passwordField.value;

            let hasError = false;

            // Validation
            if (!username) {
                document.getElementById('usernameError').textContent = 'Please enter username';
                document.getElementById('usernameError').classList.add('show');
                usernameField.closest('.form-group').classList.add('error');
                hasError = true;
            }

            if (!password) {
                document.getElementById('passwordError').textContent = 'Please enter password';
                document.getElementById('passwordError').classList.add('show');
                passwordField.closest('.form-group').classList.add('error');
                hasError = true;
            }

            if (hasError) return;

            try {
                loginBtn.disabled = true;
                loginBtn.classList.add('loading');

                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);

                const response = await fetch(`${baseUrl}/auth/login`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                const result = await response.json();

                if (result.success) {
                    successMessage.classList.add('show');
                    setTimeout(() => {
                        window.location.replace("../pages/exam.html");
                    }, 2000);
                } else {
                    document.getElementById('usernameError').textContent = result.message || 'Login failed. Please try again.';
                    document.getElementById('usernameError').classList.add('show');
                    loginBtn.disabled = false;
                    loginBtn.classList.remove('loading');
                }
            } catch (error) {
                console.error('Login error', error);
                document.getElementById('usernameError').textContent = 'An error occurred. Please try again.';
                document.getElementById('usernameError').classList.add('show');
                loginBtn.disabled = false;
                loginBtn.classList.remove('loading');
            }
        });
    }
})();
