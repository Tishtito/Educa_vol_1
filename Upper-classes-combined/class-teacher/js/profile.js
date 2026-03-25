// Profile page script

// Load components
async function loadComponentPromise(componentPath, containerId) {
   try {
      const response = await fetch(componentPath);
      const html = await response.text();
      document.getElementById(containerId).innerHTML = html;
      
      // Trigger header re-initialization if this is the header
      if (containerId === 'headerContainer') {
         document.dispatchEvent(new Event('headerLoaded'));
      }
   } catch (error) {
      console.error(`Error loading ${componentPath}:`, error);
   }
}

// Initialize page
window.addEventListener('DOMContentLoaded', async () => {
   await Promise.all([
      loadComponentPromise('components/header.html', 'headerContainer'),
      loadComponentPromise('components/sidebar.html', 'sidebarContainer'),
      loadComponentPromise('components/bottom-navigator.html', 'bottomNavContainer'),
      loadComponentPromise('components/footer.html', 'footerContainer'),
      loadTeacherData()
   ]);

   // Load profile data
   await loadProfile();
});

/**
 * Load teacher profile data
 */
async function loadProfile() {
   try {
      const response = await fetch('../backend/public/index.php/profile', { 
         credentials: 'include' 
      });

      if (!response.ok) {
         if (response.status === 401) {
            window.location.href = 'index.html';
            return;
         }
         throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
         displayProfile(data);
      } else {
         document.getElementById('profileContainer').innerHTML = 
            `<p class="error-message">${escapeHtml(data.message)}</p>`;
      }
   } catch (error) {
      console.error('Error loading profile:', error);
      document.getElementById('profileContainer').innerHTML = 
         `<p class="error-message">Error loading profile. Please refresh the page.</p>`;
   }
}

/**
 * Display teacher profile
 */
function displayProfile(data) {
   const classInfo = data.class || {};
   const teacherName = teacherDataGlobal.name || 'N/A';

   let html = `
      <div class="user">
         <img src="../photos/user1.png" alt="Teacher Profile">
         <div>
            <h3 class="name">${escapeHtml(teacherName)}</h3>
            <p>Class Teacher</p>
            <a href="#" class="inline-btn">Update Profile</a>
         </div>
      </div>

      <div class="box-container">
         <div class="box">
            <div class="flex">
               <i class="fas fa-bookmark"></i>
               <div>
                  <span class="title">${escapeHtml(classInfo.title || 'N/A')}</span>
                  <p>Number of Pupils: <span>${classInfo.totalStudents || 0}</span></p>
               </div>
            </div>
            <a href="students.html" class="inline-btn">View Pupils</a>
         </div>
      </div>
   `;

   document.getElementById('profileContainer').innerHTML = html;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
   const div = document.createElement('div');
   div.textContent = text;
   return div.innerHTML;
}
