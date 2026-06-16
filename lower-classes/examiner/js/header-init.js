/**
 * Header Component Event Listeners
 * Centralized initialization for header functionality
 * Works across all pages that load the header component
 */

function initializeHeaderListeners() {
   // Search toggle
   const searchBtn = document.getElementById('search-btn');
   const searchForm = document.querySelector('.search-form');

   if (searchBtn && searchForm) {
      searchBtn.addEventListener('click', () => {
         searchForm.classList.toggle('active');
      });
   }

   // User profile toggle
   const userBtn = document.getElementById('user-btn');
   const userProfile = document.querySelector('.profile');

   if (userBtn && userProfile) {
      userBtn.addEventListener('click', () => {
         userProfile.classList.toggle('active');
      });
   }

   // Dark/light mode toggle
   const toggleBtn = document.getElementById('toggle-btn');
   
   if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
         document.body.classList.toggle('dark');
         
         // Save preference to localStorage
         const isDark = document.body.classList.contains('dark');
         localStorage.setItem('darkMode', isDark ? 'true' : 'false');
         
         // Update toggle button icon
         if (isDark) {
            toggleBtn.classList.remove('fa-sun');
            toggleBtn.classList.add('fa-moon');
         } else {
            toggleBtn.classList.remove('fa-moon');
            toggleBtn.classList.add('fa-sun');
         }
      });

      // Load saved preference on page load
      const savedDarkMode = localStorage.getItem('darkMode') === 'true';
      if (savedDarkMode) {
         document.body.classList.add('dark');
         toggleBtn.classList.remove('fa-sun');
         toggleBtn.classList.add('fa-moon');
      }
   }

   // Close mobile menu when clicking outside
   document.addEventListener('click', (e) => {
      const sideBar = document.querySelector('.side-bar');
      const menuBtn = document.querySelector('#menu-btn');
      if (sideBar && menuBtn) {
         if (!sideBar.contains(e.target) && !menuBtn.contains(e.target)) {
            sideBar.classList.remove('active');
         }
      }

      if (userProfile && userBtn) {
         if (!userProfile.contains(e.target) && !userBtn.contains(e.target)) {
            userProfile.classList.remove('active');
         }
      }
   });

   // Close search when clicking outside
   if (searchForm && searchBtn) {
      document.addEventListener('click', (e) => {
         if (!searchForm.contains(e.target) && !searchBtn.contains(e.target)) {
            searchForm.classList.remove('active');
         }
      });
   }
}

// Initialize when DOM is ready or when header is loaded
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initializeHeaderListeners);
} else {
   // DOM already loaded, initialize immediately
   initializeHeaderListeners();
}

// Also initialize when header component is dynamically loaded
document.addEventListener('headerLoaded', initializeHeaderListeners);

