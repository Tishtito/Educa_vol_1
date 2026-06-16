/**
 * Exam Details Module
 * Handles loading and displaying grade/class marklists for selected exam
 */

const ExamDetails = (() => {
   const BASE_URL = "../../backend/public/index.php";
   const GRADES_LIST_ID = "grades-list";

   // Cache DOM elements
   let gradesList;

   // URL parameters
   let examId, token;

   /**
    * Initialize the exam details page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();

      if (!examId || !token) {
         showErrorState("Invalid exam parameters provided");
         return;
      }

      await loadExamData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      gradesList = document.getElementById(GRADES_LIST_ID);
   }

   /**
    * Extract and validate URL parameters
    */
   function extractURLParameters() {
      const params = new URLSearchParams(window.location.search);
      examId = params.get("exam_id");
      token = params.get("token");
   }

   /**
    * Load exam data and authenticate user
    */
   async function loadExamData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and grades fetch in parallel
         const [authRes, gradesRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(
               `${BASE_URL}/analysis/grades?exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`,
               { credentials: "include" }
            )
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         // Process grades data
         const grades = await gradesRes.json();
         if (grades.success && grades.data) {
            renderGradesList(grades.data);
         } else {
            showErrorState("Failed to load grades/classes");
         }
      } catch (error) {
         console.error("Exam details load failed:", error);
         showErrorState("Error loading exam details");
      }
   }

   /**
    * Show loading state in container
    */
   function showLoadingState() {
      if (gradesList) {
         gradesList.innerHTML = '<li style="padding: 12px;"><p>Loading grades/classes...</p></li>';
      }
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      if (gradesList) {
         gradesList.innerHTML = `<li style="padding: 12px; color: #e74c3c;"><p>${message}</p></li>`;
      }
   }

   /**
    * Render grades list using batch rendering
    * Uses join() instead of appendChild for better performance
    */
   function renderGradesList(gradesData) {
      const gradeItems = gradesData.map(grade => createGradeListItem(grade)).join('');
      gradesList.innerHTML = gradeItems || '<li style="padding: 12px;"><p>No grades available</p></li>';
   }

   /**
    * Create individual grade list item HTML
    */
   function createGradeListItem(grade) {
      const href = `mark_list.html?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade.grade)}&token=${encodeURIComponent(grade.token)}`;
      return `
         <a href="${href}">
            <li>
               <i class='bx bx-show-alt'></i>
               <span class="info-2">
                  <p>${escapeHtml(grade.grade)}</p>
               </span>
            </li>
         </a>
      `;
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
   ExamDetails.init();
});
