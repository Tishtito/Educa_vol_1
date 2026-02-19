/**
 * Report Select Module
 * Handles displaying grades for a selected exam
 */

const ReportSelect = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let gradesList, reportLabel;

   // URL parameters
   let examId, examType, examToken;

   // Mapping configuration
   const tableMap = {
      'Mid-Term': 'report_table.html',
      'End-Term': 'report_table.html',
      'Opener': 'report_table.html',
      'Weekly': 'report_table.html'
   };

   const labelMap = {
      'Mid-Term': 'Mid-Term',
      'End-Term': 'End of Term',
      'Opener': 'Opener',
      'Weekly': 'Weekly'
   };

   /**
    * Initialize the report select page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();
      updatePageLabel();

      if (!examId || !examToken) {
         showErrorState("Invalid or missing exam parameters");
         return;
      }

      await loadGrades();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      gradesList = document.getElementById('grades-list');
      reportLabel = document.getElementById('report-label');
   }

   /**
    * Extract and validate URL parameters
    */
   function extractURLParameters() {
      const params = new URLSearchParams(window.location.search);
      examId = params.get('exam_id');
      examType = params.get('exam_type');
      examToken = params.get('token');
   }

   /**
    * Update page label based on exam type
    */
   function updatePageLabel() {
      const labelText = labelMap[examType] || 'Report';
      if (reportLabel) {
         reportLabel.textContent = labelText;
      }
   }

   /**
    * Load grades for the exam
    */
   async function loadGrades() {
      try {
         showLoadingState();

         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         const gradesRes = await fetch(
            `${BASE_URL}/reports/grades?exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(examToken)}`,
            { credentials: "include" }
         );

         const grades = await gradesRes.json();

         if (grades.success && grades.data && grades.data.length > 0) {
            renderGradesList(grades.data);
         } else {
            showErrorState("No grades found");
         }
      } catch (error) {
         console.error('Failed to load grades:', error);
         showErrorState("Error loading grades data");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (gradesList) {
         gradesList.innerHTML = '<li class="no-task">Loading grades...</li>';
      }
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      if (gradesList) {
         gradesList.innerHTML = `<li class="no-task">${escapeHtml(message)}</li>`;
      }
   }

   /**
    * Render grades list with batch rendering
    */
   function renderGradesList(grades) {
      const tableTarget = tableMap[examType] || 'report_table.html';

      const links = grades.map(grade => {
         const href = `${tableTarget}?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade.grade)}&exam_type=${encodeURIComponent(examType)}&token=${encodeURIComponent(grade.token)}&exam_token=${encodeURIComponent(examToken)}`;

         return `
            <a href="${escapeHtml(href)}">
               <li>
                  <i class='bx bx-show-alt'></i>
                  <span class="info-2">
                     <p>${escapeHtml(grade.grade)}</p>
                  </span>
               </li>
            </a>
         `;
      }).join('');

      if (gradesList) {
         gradesList.innerHTML = links;
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
   ReportSelect.init();
});
