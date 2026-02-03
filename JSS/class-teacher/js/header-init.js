/**
 * Header Component Event Listeners
 * Handles: search toggle, user profile toggle, dark mode toggle, click-outside behavior
 * Sidebar menu toggle is handled separately in script.js
 * Re-initializes when header component loads dynamically
 */

function initializeHeaderListeners() {
   // Search toggle
   const searchBtn = document.getElementById('search-btn');
   const searchForm = document.querySelector('.search-form');

   if (searchBtn && searchForm) {
      searchBtn.onclick = () => {
         searchForm.classList.toggle('active');
      }
   }

   // User profile toggle
   const userBtn = document.getElementById('user-btn');
   const userProfile = document.querySelector('.profile');

   if (userBtn && userProfile) {
      userBtn.onclick = () => {
         userProfile.classList.toggle('active');
      }
   }

   // Dark/light mode toggle
   const toggleBtn = document.getElementById('toggle-btn');
   
   if (toggleBtn) {
      toggleBtn.onclick = () => {
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
      }

      // Load saved preference on page load
      const savedDarkMode = localStorage.getItem('darkMode') === 'true';
      if (savedDarkMode) {
         document.body.classList.add('dark');
         toggleBtn.classList.remove('fa-sun');
         toggleBtn.classList.add('fa-moon');
      }
   }

   // Close menus when clicking outside
   document.addEventListener('click', (e) => {
      const searchForm = document.querySelector('.search-form');
      const searchBtn = document.getElementById('search-btn');
      const userProfile = document.querySelector('.profile');
      const userBtn = document.getElementById('user-btn');

      if (searchForm && searchBtn) {
         if (!searchForm.contains(e.target) && !searchBtn.contains(e.target)) {
            searchForm.classList.remove('active');
         }
      }

      if (userProfile && userBtn) {
         if (!userProfile.contains(e.target) && !userBtn.contains(e.target)) {
            userProfile.classList.remove('active');
         }
      }
   });

   // Close search and profile when scrolling
   window.onscroll = () => {
      const searchForm = document.querySelector('.search-form');
      const userProfile = document.querySelector('.profile');
      if (searchForm) searchForm.classList.remove('active');
      if (userProfile) userProfile.classList.remove('active');
   }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initializeHeaderListeners);
} else {
   initializeHeaderListeners();
}

// Re-initialize when header component is loaded
document.addEventListener('headerLoaded', initializeHeaderListeners);
