/**
 * Sidebar Menu Toggle Handler
 * Separated responsibility: ONLY handles sidebar menu toggle
 * Other button functionality is handled in header-init.js
 */

function initializeSidebarMenu() {
   let body = document.body;

   if (document.querySelector('#menu-btn')) {
      document.querySelector('#menu-btn').onclick = () => {
         let sideBar = document.querySelector('.side-bar');
         if (sideBar) {
            sideBar.classList.toggle('active');
            body.classList.toggle('active');
         }
      }
   }

   if (document.querySelector('#close-btn')) {
      document.querySelector('#close-btn').onclick = () => {
         let sideBar = document.querySelector('.side-bar');
         if (sideBar) {
            sideBar.classList.remove('active');
            body.classList.remove('active');
         }
      }
   }

   window.onscroll = () => {
      if (window.innerWidth < 1200) {
         let sideBar = document.querySelector('.side-bar');
         if (sideBar) {
            sideBar.classList.remove('active');
            body.classList.remove('active');
         }
      }
   }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', initializeSidebarMenu);
} else {
   initializeSidebarMenu();
}

// Re-initialize when header component is loaded
document.addEventListener('headerLoaded', initializeSidebarMenu);