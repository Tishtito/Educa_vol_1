// Class Teacher Login Script

// Show/hide password toggle
document.getElementById('show-password').addEventListener('change', function() {
    const passwordInput = document.getElementById('password');
    passwordInput.type = this.checked ? 'text' : 'password';
});

// Handle form submission
document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Clear previous errors
    document.getElementById('loginError').innerHTML = '';
    document.getElementById('usernameError').textContent = '';
    document.getElementById('passwordError').textContent = '';
    
    // Get form values
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    // Validate inputs
    let hasError = false;
    if (username === '') {
        document.getElementById('usernameError').textContent = 'Please enter your username.';
        hasError = true;
    }
    if (password === '') {
        document.getElementById('passwordError').textContent = 'Please enter your password.';
        hasError = true;
    }
    
    if (hasError) return;
    
    // Show loading state
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitText').style.display = 'none';
    document.getElementById('loadingSpinner').classList.add('show');
    
    try {
        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
        // Send login request
        const response = await fetch('../backend/public/index.php/auth/login', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to exam page
            window.location.href = 'exam.html';
        } else {
            // Show error message
            document.getElementById('loginError').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + escapeHtml(data.message) + '</div>';
        }
    } catch (error) {
        console.error('Login error:', error);
        document.getElementById('loginError').innerHTML = 
            '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.</div>';
    } finally {
        // Hide loading state
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitText').style.display = 'inline';
        document.getElementById('loadingSpinner').classList.remove('show');
    }
});

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
