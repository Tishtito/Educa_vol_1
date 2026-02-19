// Load classes on page load
window.addEventListener('DOMContentLoaded', async function() {
   await loadClasses();
});

async function loadClasses() {
   try {
      const res = await fetch('../backend/public/index.php/auth/classes', {
         credentials: 'include'
      });
      
      if (!res.ok) {
         console.error('Failed to load classes');
         return;
      }
      
      const data = await res.json();
      const classSelect = document.getElementById('classAssigned');
      
      if (data.success && Array.isArray(data.classes)) {
         data.classes.forEach(cls => {
            const option = document.createElement('option');
            option.value = cls.class_name;
            option.textContent = cls.class_name;
            classSelect.appendChild(option);
         });
      }
   } catch (err) {
      console.error('Error loading classes:', err);
   }
}

function togglePasswordVisibility(fieldId) {
   const field = document.getElementById(fieldId);
   const icon = event.target.closest('.toggle-password').querySelector('i');
   
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

function checkPasswordStrength() {
   const password = document.getElementById('password').value;
   const strengthDiv = document.getElementById('passwordStrength');
   
   if (password.length === 0) {
      strengthDiv.style.display = 'none';
      return;
   }
   
   let strength = 0;
   if (password.length >= 8) strength++;
   if (password.length >= 12) strength++;
   if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
   if (/\d/.test(password)) strength++;
   if (/[\W_]/.test(password)) strength++;
   
   strengthDiv.style.display = 'block';
   if (strength < 2) {
      strengthDiv.className = 'password-strength weak';
      strengthDiv.textContent = '❌ Weak password - Use 8+ characters, uppercase, lowercase, and numbers';
   } else if (strength < 4) {
      strengthDiv.className = 'password-strength medium';
      strengthDiv.textContent = '⚠️  Medium strength - Add special characters for better security';
   } else {
      strengthDiv.className = 'password-strength strong';
      strengthDiv.textContent = '✓ Strong password';
   }
}

function clearErrors() {
   document.querySelectorAll('.error-message').forEach(el => {
      el.style.display = 'none';
      el.textContent = '';
   });
}

async function handleSignup(event) {
   event.preventDefault();
   clearErrors();
   
   const name = document.getElementById('name').value.trim();
   const username = document.getElementById('username').value.trim();
   const role = document.querySelector('input[name="role"]:checked')?.value;
   const classAssigned = document.getElementById('classAssigned').value;
   const password = document.getElementById('password').value;
   const confirmPassword = document.getElementById('confirmPassword').value;
   const termsAccepted = document.getElementById('terms').checked;
   
   // Client-side validation
   if (!name) {
      document.getElementById('nameError').textContent = 'Full name is required';
      document.getElementById('nameError').style.display = 'block';
      return;
   }
   
   if (name.length < 3) {
      document.getElementById('nameError').textContent = 'Name must be at least 3 characters';
      document.getElementById('nameError').style.display = 'block';
      return;
   }
   
   if (!username) {
      document.getElementById('usernameError').textContent = 'Username is required';
      document.getElementById('usernameError').style.display = 'block';
      return;
   }
   
   if (username.length < 3) {
      document.getElementById('usernameError').textContent = 'Username must be at least 3 characters';
      document.getElementById('usernameError').style.display = 'block';
      return;
   }
   
   if (!/^[a-zA-Z0-9_]+$/.test(username)) {
      document.getElementById('usernameError').textContent = 'Username can only contain letters, numbers, and underscores';
      document.getElementById('usernameError').style.display = 'block';
      return;
   }
   
   if (!role) {
      document.getElementById('roleError').textContent = 'Please select a role';
      document.getElementById('roleError').style.display = 'block';
      return;
   }
   
   if (!classAssigned) {
      document.getElementById('classError').textContent = 'Please select a class';
      document.getElementById('classError').style.display = 'block';
      return;
   }
   
   if (!password) {
      document.getElementById('passwordError').textContent = 'Password is required';
      document.getElementById('passwordError').style.display = 'block';
      return;
   }
   
   if (password.length < 6) {
      document.getElementById('passwordError').textContent = 'Password must be at least 6 characters';
      document.getElementById('passwordError').style.display = 'block';
      return;
   }
   
   if (password !== confirmPassword) {
      document.getElementById('confirmError').textContent = 'Passwords do not match';
      document.getElementById('confirmError').style.display = 'block';
      return;
   }
   
   if (!termsAccepted) {
      Swal.fire('Error', 'You must accept the Terms and Conditions', 'error');
      return;
   }
   
   // Submit to server
   const btn = document.getElementById('signupBtn');
   btn.disabled = true;
   btn.classList.add('loading');
   
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
            confirm_password: confirmPassword,
            role,
            class_assigned: classAssigned
         })
      });
      
      const data = await res.json();
      
      if (res.ok && data.success) {
         Swal.fire(
            'Success!',
            'Account created successfully. Redirecting to login...',
            'success'
         ).then(() => {
            window.location.href = 'index.html';
         });
      } else {
         Swal.fire('Error', data.message || 'Failed to create account', 'error');
      }
   } catch (err) {
      console.error('Error:', err);
      Swal.fire('Error', 'An error occurred: ' + err.message, 'error');
   } finally {
      btn.disabled = false;
      btn.classList.remove('loading');
   }
}
