/**
 * Analytics Dashboard Module
 * Handles loading and displaying exam and stream marklists
 */

const AnalyticsDashboard = (() => {
   const BASE_URL = "../../backend/public/index.php";
   const EXAM_LIST_ID = "exam-marklists";
   const STREAM_LIST_ID = "stream-marklists";

   // Cache DOM elements
   let examList, streamList;

   /**
    * Initialize the analytics dashboard
    */
   async function init() {
      cacheDOMElements();
      await loadAnalyticsData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      examList = document.getElementById(EXAM_LIST_ID);
      streamList = document.getElementById(STREAM_LIST_ID);
   }

   /**
    * Load all analytics data
    */
   async function loadAnalyticsData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and exams fetch in parallel
         const [authRes, examsRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(`${BASE_URL}/analysis/exams`, { credentials: "include" })
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         // Process exams data
         const exams = await examsRes.json();
         if (exams.success && exams.data) {
            renderMarklists(exams.data);
         } else {
            showErrorState("Failed to load marklists");
         }
      } catch (error) {
         console.error("Analytics load failed:", error);
         showErrorState("Error loading analytics data");
      }
   }

   /**
    * Show loading state in containers
    */
   function showLoadingState() {
      if (examList) examList.innerHTML = '<li><p>Loading exam marklists...</p></li>';
      if (streamList) streamList.innerHTML = '<li><p>Loading stream marklists...</p></li>';
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      const errorHTML = `<li style="color: #e74c3c; padding: 12px;"><p>${message}</p></li>`;
      if (examList) examList.innerHTML = errorHTML;
      if (streamList) streamList.innerHTML = errorHTML;
   }

   /**
    * Render exam and stream marklists
    * Batch render using innerHTML with join() for efficiency
    */
   function renderMarklists(examsData) {
      // Batch render exam list
      const examItems = examsData.map(exam => 
         createExamListItem(exam, "exam_details.html")
      ).join('');
      examList.innerHTML = examItems || '<li><p>No exams available</p></li>';

      // Batch render stream list
      const streamItems = examsData.map(exam => 
         createStreamListItem(exam, "stream_details.html")
      ).join('');
      streamList.innerHTML = streamItems || '<li><p>No streams available</p></li>';
   }

   /**
    * Create exam list item HTML
    */
   function createExamListItem(exam, page) {
      const href = `${page}?exam_id=${encodeURIComponent(exam.exam_id)}&token=${encodeURIComponent(exam.token)}`;
      return `
         <a href="${href}">
            <li class="completed">
               <div class="task-title">
                  <i class='bx bx-book'></i>
                  <p>${escapeHtml(exam.exam_name)}<span></span></p>
               </div>
               <i class='bx bx-dots-vertical-rounded'></i>
            </li>
         </a>
      `;
   }

   /**
    * Create stream list item HTML
    */
   function createStreamListItem(exam, page) {
      const href = `${page}?exam_id=${encodeURIComponent(exam.exam_id)}&token=${encodeURIComponent(exam.token)}`;
      return `
         <a href="${href}">
            <li class="completed">
               <div class="task-title">
                  <i class='bx bx-book'></i>
                  <p>${escapeHtml(exam.exam_name)}<span></span></p>
               </div>
               <i class='bx bx-dots-vertical-rounded'></i>
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
   AnalyticsDashboard.init();
});
