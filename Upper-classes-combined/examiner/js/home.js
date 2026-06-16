// Home page (dashboard) initialization

async function loadComponentPromise(componentName, containerId) {
   try {
      const res = await fetch(`components/${componentName}.html`);
      if (!res.ok) throw new Error(`Failed to load ${componentName}`);
      const html = await res.text();
      document.getElementById(containerId).innerHTML = html;
      // Trigger header re-initialization after header loads
      if (containerId === 'headerContainer') {
         document.dispatchEvent(new Event('headerLoaded'));
      }
   } catch (err) {
      console.error(`Error loading ${componentName} component:`, err);
   }
}

async function checkAuth() {
   const res = await fetch('../backend/public/index.php/auth/check', { credentials: 'include' });
   if (!res.ok) {
      window.location.href = 'index.html';
      return null;
   }
   return await res.json();
}

document.addEventListener('DOMContentLoaded', async () => {
   await Promise.all([
      loadComponentPromise('header', 'headerContainer'),
      loadComponentPromise('sidebar', 'sidebarContainer'),
      loadComponentPromise('bottom-navigator', 'bottomNavContainer'),
      loadComponentPromise('footer', 'footerContainer')
   ]);
   // Load examiner data (name and class) from backend
   await loadExaminerData();
});
