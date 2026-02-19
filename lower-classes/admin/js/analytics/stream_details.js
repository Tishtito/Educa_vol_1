/**
 * Stream Details Module
 * Handles loading and displaying stream/class marklists for selected exam
 */

const StreamDetails = (() => {
   const BASE_URL = "../../backend/public/index.php";
   const STREAM_GRADES_ID = "stream-grades";

   // Cache DOM elements
   let streamGradesList;

   // URL parameters
   let examId, token;

   /**
    * Initialize the stream details page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();

      if (!examId || !token) {
         showErrorState("Invalid exam parameters provided");
         return;
      }

      await loadStreamData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      streamGradesList = document.getElementById(STREAM_GRADES_ID);
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
    * Load stream data
    */
   async function loadStreamData() {
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
            renderStreamGradesList(grades.data);
         } else {
            showErrorState("Failed to load stream/classes");
         }
      } catch (error) {
         console.error("Stream details load failed:", error);
         showErrorState("Error loading stream details");
      }
   }

   /**
    * Show loading state in container
    */
   function showLoadingState() {
      if (streamGradesList) {
         streamGradesList.innerHTML = '<li style="padding: 12px;"><p>Loading stream/classes...</p></li>';
      }
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      if (streamGradesList) {
         streamGradesList.innerHTML = `<li style="padding: 12px; color: #e74c3c;"><p>${escapeHtml(message)}</p></li>`;
      }
   }

   /**
    * Render stream grades list using batch rendering
    * Uses join() instead of appendChild for better performance
    */
   function renderStreamGradesList(gradesData) {
      const gradeItems = gradesData.map(grade => createStreamGradeListItem(grade)).join('');
      streamGradesList.innerHTML = gradeItems || '<li style="padding: 12px;"><p>No stream/classes available</p></li>';
   }

   /**
    * Create individual stream grade list item HTML
    */
   function createStreamGradeListItem(grade) {
      const href = `streamlist.html?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade.grade)}&token=${encodeURIComponent(grade.token)}`;
      return `
         <a href="${href}">
            <li>
               <i class='bx bx-show-alt'></i>
               <span class="info-2">
                  <p>Grade - ${escapeHtml(grade.grade)}</p>
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
   StreamDetails.init();
});
