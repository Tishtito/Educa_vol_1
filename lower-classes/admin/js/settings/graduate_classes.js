/**
 * Graduate Classes Module
 * Handles graduating entire classes to next level or finishing
 */

const GraduateClassesManager = (() => {
   const BASE_URL = "../../backend/public/index.php";
   const CLASSES_ENDPOINT = `${BASE_URL}/settings/classes`;
   const STUDENTS_ENDPOINT = `${BASE_URL}/students/active-by-class`;
   const GRADUATE_ENDPOINT = `${BASE_URL}/settings/classes/graduate-all`;

   // Cache DOM elements
   let fromClassSelect, targetClassSelect, studentsTable, graduateForm;

   /**
    * Initialize the graduate classes page
    */
   async function init() {
      cacheDOMElements();
      attachEventListeners();
      await loadClasses();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      fromClassSelect = document.getElementById('fromClass');
      targetClassSelect = document.getElementById('targetClass');
      studentsTable = document.getElementById('studentsTable');
      graduateForm = document.getElementById('graduateForm');
   }

   /**
    * Attach event listeners
    */
   function attachEventListeners() {
      if (fromClassSelect) {
         fromClassSelect.addEventListener('change', event => {
            loadStudents(event.target.value);
         });
      }

      if (graduateForm) {
         graduateForm.addEventListener('submit', handleGraduateSubmit);
      }
   }

   /**
    * Load classes from backend
    */
   async function loadClasses() {
      try {
         const response = await fetch(CLASSES_ENDPOINT);
         const data = await response.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to load classes', 'error');
            return;
         }

         const classes = data.data || [];
         populateClassSelector(classes);
      } catch (error) {
         console.error('Failed to load classes:', error);
         swal('Error', 'Failed to load classes', 'error');
      }
   }

   /**
    * Populate class selectors with batch rendering
    */
   function populateClassSelector(classes) {
      // Reset selectors
      fromClassSelect.innerHTML = '<option value="">-- Select class --</option>';
      targetClassSelect.innerHTML = '<option value="FINISHED">Finished (End of Lower Class)</option>';

      // Build options using batch rendering
      const options = classes.map(cls => {
         return `<option value="${escapeHtml(cls.class_name)}">${escapeHtml(cls.class_name)}</option>`;
      }).join('');

      // Add from options
      fromClassSelect.innerHTML += options;

      // Add target options (same classes)
      targetClassSelect.innerHTML += options;
   }

   /**
    * Load students for a selected class
    */
   async function loadStudents(className) {
      if (!className) {
         showEmptyState();
         return;
      }

      try {
         showLoadingState();

         const response = await fetch(
            `${STUDENTS_ENDPOINT}?class=${encodeURIComponent(className)}`
         );
         const data = await response.json();

         if (!data.success) {
            showErrorState(data.message || 'Failed to load students');
            return;
         }

         renderStudentsTable(data.data || []);
      } catch (error) {
         console.error('Failed to load students:', error);
         showErrorState('Error loading students');
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (studentsTable) {
         studentsTable.innerHTML = 
            '<tr><td colspan="3" class="empty-state">Loading students...</td></tr>';
      }
   }

   /**
    * Show empty state
    */
   function showEmptyState() {
      if (studentsTable) {
         studentsTable.innerHTML = 
            '<tr><td colspan="3" class="empty-state">Select a class to load students.</td></tr>';
      }
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      if (studentsTable) {
         studentsTable.innerHTML = 
            `<tr><td colspan="3" class="empty-state">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render students table with batch rendering
    */
   function renderStudentsTable(students) {
      if (!studentsTable) return;

      if (students.length === 0) {
         studentsTable.innerHTML = '<tr><td colspan="3" class="empty-state">No students found in this class.</td></tr>';
         return;
      }

      const rows = students.map(student => {
         return `
            <tr>
               <td>${escapeHtml(student.name)}</td>
               <td>${escapeHtml(student.class)}</td>
               <td><span class="status-pill">${escapeHtml(student.status || 'Active')}</span></td>
            </tr>
         `;
      }).join('');

      studentsTable.innerHTML = rows;
   }

   /**
    * Handle graduate form submission
    */
   async function handleGraduateSubmit(event) {
      event.preventDefault();

      const fromValue = fromClassSelect.value;
      const targetValue = targetClassSelect.value;

      if (!fromValue || !targetValue) {
         swal('Missing data', 'Please select both classes.', 'warning');
         return;
      }

      try {
         const response = await fetch(GRADUATE_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
               from_class: fromValue,
               target_class: targetValue
            })
         });

         const data = await response.json();

         if (!data.success) {
            swal('Error', data.message || 'Failed to graduate class', 'error');
            return;
         }

         swal('Success', data.message || 'Class graduated successfully.', 'success');
         await loadStudents(fromValue);
      } catch (error) {
         console.error('Failed to graduate class:', error);
         swal('Error', 'Failed to graduate class', 'error');
      }
   }

   /**
    * Escape HTML to prevent XSS attacks
    */
   function escapeHtml(text) {
      const map = {
         '&': '&amp;',
         '<': '&lt;',
         '>': '&gt;',
         '"': '&quot;',
         "'": '&#039;'
      };
      return String(text).replace(/[&<>"']/g, m => map[m]);
   }

   // Public API
   return {
      init
   };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
   GraduateClassesManager.init();
});
