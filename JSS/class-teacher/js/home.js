// Home page (dashboard) script

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

async function loadDashboard() {
   try {
      const response = await fetch('../backend/public/index.php/dashboard', { credentials: 'include' });
      
      if (!response.ok) {
         if (response.status === 401) {
            window.location.href = 'index.html';
            return;
         }
         throw new Error(`Failed to load dashboard data: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
         // Load teacher names in header and sidebar
         await loadTeacherData();

         // Update class title
         const classTitleElement = document.getElementById('classTitle');
         if (classTitleElement) classTitleElement.textContent = data.title || 'Class Dashboard';

         // Update mean scores
         const means = data.means || {};
         const elements = {
            'englishMean': means.English,
            'kiswahiliMean': means.Kiswahili,
            'mathMean': means.Math,
            'creativeMean': means.Creative,
            'religiousMean': means.Religious,
            'agricultureMean': means.Agriculture,
            'technicalMean': means.Technical,
            'sstMean': means.SST,
            'scienceMean': means.Science,
            'mssMean': means.total_mean
         };

         for (const [id, value] of Object.entries(elements)) {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '-';
         }
      } else {
         throw new Error(data.message || 'Unknown error');
      }
   } catch (error) {
      console.error('Error loading dashboard:', error);
      showErrorAlert('Error loading dashboard. Please refresh the page.');
   }
}

/**
 * Show error alert
 */
function showErrorAlert(message) {
   Swal.fire({
      title: 'Error!',
      text: message,
      icon: 'error',
      confirmButtonText: 'OK'
   });
}

/**
 * Show error when exam is not selected
 */
function showExamNotSelectedError() {
   Swal.fire({
      title: 'No Exam Selected',
      text: 'Please select an exam first',
      icon: 'error',
      confirmButtonText: 'Go to Exams',
      allowOutsideClick: false,
      allowEscapeKey: false
   }).then(() => {
      window.location.href = 'exam.html';
   });
}

document.addEventListener('DOMContentLoaded', async () => {
   await Promise.all([
      loadComponentPromise('header', 'headerContainer'),
      loadComponentPromise('sidebar', 'sidebarContainer'),
      loadComponentPromise('bottom-navigator', 'bottomNavContainer'),
      loadComponentPromise('footer', 'footerContainer')
   ]);
   await loadDashboard();
});
