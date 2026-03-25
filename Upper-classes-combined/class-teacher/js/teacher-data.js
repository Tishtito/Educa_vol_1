/**
 * Load teacher data from backend and populate names in header and sidebar
 * This function can be called on any page that has header and sidebar components
 */
let teacherDataGlobal = { name: 'Teacher', class_assigned: '' }; // Global teacher data store

async function loadTeacherData() {
   try {
      const response = await fetch('../backend/public/index.php/dashboard');
      
      if (!response.ok) {
         if (response.status === 401) {
            window.location.href = 'index.html';
            return;
         }
         throw new Error('Failed to load teacher data');
      }

      const data = await response.json();

      if (data.success) {
         const teacherName = data.teacher?.name || 'Teacher';
         const classAssigned = data.teacher?.class_assigned || '';
         
         teacherDataGlobal.name = teacherName; // Store globally
         teacherDataGlobal.class_assigned = classAssigned; // Store class assignment
         
         const nameElement = document.getElementById('teacherName');
         const sidebarNameElement = document.getElementById('sidebarTeacherName');
         
         if (nameElement) nameElement.textContent = teacherName;
         if (sidebarNameElement) sidebarNameElement.textContent = teacherName;
      }
   } catch (error) {
      console.error('Error loading teacher data:', error);
   }
}
