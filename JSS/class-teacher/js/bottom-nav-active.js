// Bottom Navigator Active Link Handler

/**
 * Set active class on the correct nav item based on current page
 */
function setActiveNavItem() {
   // Get the current page filename
   const currentPage = window.location.pathname.split('/').pop() || 'home.html';
   
   // Select all nav items
   const navItems = document.querySelectorAll('.bottom-navigator .nav-item');
   
   // Remove active class from all and set it on the matching item
   navItems.forEach(item => {
      const href = item.getAttribute('href');
      
      // Check if this nav item matches the current page
      if (href === currentPage || (href === 'home.html' && currentPage === '')) {
         item.classList.add('active');
      } else {
         item.classList.remove('active');
      }
   });
}

// Run when DOM is ready
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', () => {
      // Give components time to load, then set active state
      setTimeout(setActiveNavItem, 100);
   });
} else {
   // DOM already loaded
   setTimeout(setActiveNavItem, 100);
}

// Also update active state if the bottom navigator is dynamically updated
const observer = new MutationObserver(() => {
   setActiveNavItem();
});

// Start observing when the bottom navigator is added
setTimeout(() => {
   const bottomNav = document.querySelector('.bottom-navigator');
   if (bottomNav) {
      observer.observe(bottomNav, { childList: true, subtree: true });
   }
}, 200);
