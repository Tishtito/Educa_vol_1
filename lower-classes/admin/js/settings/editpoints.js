/**
 * Grade Boundaries Management Module
 * Handles editing and updating grade point boundaries per subject
 */

const GradeBoundariesManager = (() => {
   const BASE_URL = "../../backend/public/index.php";
   const FIXED_GRADES = ['1', '2', '3', '4'];

   // Performance level mappings - same for all subjects
   const GRADE_MAPPINGS = {
      '4': { pl: 'EXCEEDING EXPECTATIONS', ab: 'EE-4' },
      '3': { pl: 'MEETING EXPECTATIONS', ab: 'ME-3' },
      '2': { pl: 'APPROCHING EXPECTATIONS', ab: 'AE-2' },
      '1': { pl: 'BELOW EXPECTATIONS', ab: 'BE-1' }
   };

   // Cache DOM elements
   let gradeRows, form, cancelBtn, subjectSelect, addAllGradesBtn, newGradeInputs;

   // Store current grades for form handling
   let currentGrades = [];
   let currentSubject = 'Math';
   let newGradesToAdd = {};

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

   /**
    * Initialize the grade boundaries management page
    */
   async function init() {
      cacheDOMElements();
      attachEventListeners();
      renderNewGradeInputs();
      await loadGradeBoundaries();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      gradeRows = document.getElementById('gradeRows');
      form = document.getElementById('gradeForm');
      cancelBtn = document.getElementById('cancelBtn');
      subjectSelect = document.getElementById('subjectSelect');
      addAllGradesBtn = document.getElementById('addAllGradesBtn');
      newGradeInputs = document.getElementById('newGradeInputs');
   }

   /**
    * Render input fields for new grades (1, 2, 3, 4)
    */
   function renderNewGradeInputs() {
      if (!newGradeInputs) return;

      let html = '<div style="font-weight: bold; display: flex; align-items: center; font-size: 14px; padding: 8px 0;">Grade</div>';
      html += '<div style="font-weight: bold; display: flex; align-items: center; font-size: 14px; padding: 8px 0;">Min Marks</div>';
      html += '<div style="font-weight: bold; display: flex; align-items: center; font-size: 14px; padding: 8px 0;">Max Marks</div>';
      html += '<div style="font-weight: bold; display: flex; align-items: center; font-size: 14px; padding: 8px 0;">Performance Level</div>';
      html += '<div style="font-weight: bold; display: flex; align-items: center; font-size: 14px; padding: 8px 0;">Abbreviation</div>';

      FIXED_GRADES.forEach(gradeNum => {
         const mapping = GRADE_MAPPINGS[gradeNum];
         html += `<div style="display: flex; align-items: center; font-weight: bold; padding: 8px; background-color: #f9f9f9; border-radius: 4px;">${gradeNum}</div>`;
         html += `<input type="number" id="newMinMarks_${gradeNum}" placeholder="0" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">`;
         html += `<input type="number" id="newMaxMarks_${gradeNum}" placeholder="100" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">`;
         html += `<input type="text" value="${escapeHtml(mapping.pl)}" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; background-color: #f5f5f5;">`;
         html += `<input type="text" value="${escapeHtml(mapping.ab)}" readonly style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; text-align: center; background-color: #f5f5f5;">`;
      });

      newGradeInputs.innerHTML = html;
   }

   /**
    * Attach event listeners
    */
   function attachEventListeners() {
      if (form) {
         form.addEventListener('submit', handleFormSubmit);
      }

      if (cancelBtn) {
         cancelBtn.addEventListener('click', () => {
            window.location.href = 'settings.html';
         });
      }

      if (subjectSelect) {
         subjectSelect.addEventListener('change', (e) => {
            currentSubject = e.target.value;
            newGradesToAdd = {};
            loadGradeBoundaries();
         });
      }

      if (addAllGradesBtn) {
         addAllGradesBtn.addEventListener('click', handleAddAllGrades);
      }
   }

   /**
    * Handle adding all grades
    */
   function handleAddAllGrades(e) {
      e.preventDefault();

      let hasError = false;
      newGradesToAdd = {};

      FIXED_GRADES.forEach(gradeNum => {
         const minInput = document.getElementById(`newMinMarks_${gradeNum}`);
         const maxInput = document.getElementById(`newMaxMarks_${gradeNum}`);

         if (!minInput || !maxInput) return;

         const minMarks = parseInt(minInput.value || 0);
         const maxMarks = parseInt(maxInput.value || 0);

         if (minMarks < 0 || maxMarks < 0) {
            swal('Validation Error', `Grade ${gradeNum}: Marks cannot be negative`, 'error');
            hasError = true;
            return;
         }

         if (minMarks > maxMarks) {
            swal('Validation Error', `Grade ${gradeNum}: Min marks cannot be greater than max marks`, 'error');
            hasError = true;
            return;
         }

         const mapping = GRADE_MAPPINGS[gradeNum];
         newGradesToAdd[gradeNum] = {
            grade: gradeNum,
            min_marks: minMarks,
            max_marks: maxMarks,
            pl: mapping.pl,
            ab: mapping.ab
         };
      });

      if (hasError) return;

      if (Object.keys(newGradesToAdd).length === 0) {
         swal('Validation Error', 'Please enter marks for at least one grade', 'error');
         return;
      }

      swal('Success', 'All grades added. Click "Save All Changes" to confirm.', 'success');
   }

   /**
    * Load grade boundaries from backend for the selected subject
    */
   async function loadGradeBoundaries() {
      try {
         showLoadingState();

         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         const listRes = await fetch(`${BASE_URL}/settings/point-boundaries?subject=${encodeURIComponent(currentSubject)}`, { credentials: "include" });
         const list = await listRes.json();

         if (!list.success) {
            showErrorState("Failed to load grade boundaries");
            return;
         }

         if (!list.data || list.data.length === 0) {
            showEmptyState();
            currentGrades = [];
            return;
         }

         currentGrades = list.data;
         renderGradeBoundaries(list.data);
      } catch (error) {
         console.error('Failed to load grade boundaries:', error);
         showErrorState("Error loading grade boundaries");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (gradeRows) {
         gradeRows.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
      }
   }

   /**
    * Show empty state
    */
   function showEmptyState() {
      if (gradeRows) {
         gradeRows.innerHTML = '<tr><td colspan="5" class="text-center">No grade boundaries found for this subject. Add one below.</td></tr>';
      }
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      if (gradeRows) {
         gradeRows.innerHTML = `<tr><td colspan="5" class="text-center" style="color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render grade boundaries table with batch rendering
    */
   function renderGradeBoundaries(grades) {
      if (!gradeRows) return;

      const rows = grades.map(row => {
         return `
            <tr>
               <td>
                  <input type="text" class="settings-input" data-field="grade" data-id="${escapeHtml(String(row.id))}" value="${escapeHtml(row.grade)}" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
               </td>
               <td>
                  <input type="number" class="settings-input editable" data-field="min_marks" data-id="${escapeHtml(String(row.id))}" value="${escapeHtml(String(row.min_marks))}" required style="background-color: #fff; border: 2px solid #4CAF50;">
               </td>
               <td>
                  <input type="number" class="settings-input editable" data-field="max_marks" data-id="${escapeHtml(String(row.id))}" value="${escapeHtml(String(row.max_marks))}" required style="background-color: #fff; border: 2px solid #4CAF50;">
               </td>
               <td>
                  <input type="text" class="settings-input" data-field="pl" data-id="${escapeHtml(String(row.id))}" value="${escapeHtml(row.pl || '')}" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
               </td>
               <td>
                  <input type="text" class="settings-input" data-field="ab" data-id="${escapeHtml(String(row.id))}" value="${escapeHtml(row.ab || '')}" readonly style="background-color: #f5f5f5; cursor: not-allowed; text-align: center;">
               </td>
            </tr>
         `;
      }).join('');

      gradeRows.innerHTML = rows;
   }

   /**
    * Handle form submission
    */
   async function handleFormSubmit(event) {
      event.preventDefault();

      try {
         // Gather all input values from the form (existing grades)
         const inputs = gradeRows.querySelectorAll('input[data-id]');
         const payload = { subject: currentSubject, grades: [], new_grades: Object.values(newGradesToAdd) };

         inputs.forEach(input => {
            const id = input.getAttribute('data-id');
            const field = input.getAttribute('data-field');

            // Find or create the grade object
            let gradeObj = payload.grades.find(g => g.id === Number(id));
            if (!gradeObj) {
               gradeObj = { id: Number(id) };
               payload.grades.push(gradeObj);
            }

            gradeObj[field] = field === 'grade' ? input.value : Number(input.value);
         });

         // Submit the update
         const saveRes = await fetch(`${BASE_URL}/settings/point-boundaries`, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
         });

         const save = await saveRes.json();

         if (!save.success) {
            swal('Update failed', save.message || 'Unable to update grades.', 'error');
            return;
         }

         newGradesToAdd = {};
         swal({
            title: 'Saved',
            text: `Grade boundaries for ${currentSubject} updated successfully.`,
            icon: 'success',
            button: 'OK',
         }).then(() => {
            window.location.href = 'settings.html';
         });
      } catch (error) {
         console.error('Failed to update grades:', error);
         swal('Update failed', 'Unable to update grades.', 'error');
      }
   }

   // Public API
   return {
      init
   };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
   GradeBoundariesManager.init();
});
