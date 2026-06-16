/**
 * MSS Detail Module
 * Handles loading and displaying Mean Standard Score (MSS) data by grade
 */

const MSSDetail = (() => {
   const BASE_URL = "../../backend/public/index.php";
   const MSS_LIST_ID = "mss-list";

   // Cache DOM elements
   let mssList;

   // URL parameters
   let examId, token;

   /**
    * Initialize the MSS detail page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();

      if (!examId || !token) {
         showErrorState("Invalid exam parameters provided");
         return;
      }

      await loadMSSData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      mssList = document.getElementById(MSS_LIST_ID);
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
    * Load MSS data
    */
   async function loadMSSData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and MSS fetch in parallel
         const [authRes, mssRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(
               `${BASE_URL}/mss?exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`,
               { credentials: "include" }
            )
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         // Process MSS data
         const mss = await mssRes.json();
         if (mss.success && mss.data) {
            renderMSSList(mss.data);
         } else {
            showErrorState("Failed to load MSS data");
         }
      } catch (error) {
         console.error("MSS load failed:", error);
         showErrorState("Error loading MSS data");
      }
   }

   /**
    * Show loading state in container
    */
   function showLoadingState() {
      if (mssList) {
         mssList.innerHTML = '<li style="padding: 12px;"><p>Loading MSS data...</p></li>';
      }
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      if (mssList) {
         mssList.innerHTML = `<li style="padding: 12px; color: #e74c3c;"><p>${escapeHtml(message)}</p></li>`;
      }
   }

   /**
    * Render MSS list using batch rendering
    * Uses join() instead of appendChild for better performance
    */
   function renderMSSList(mssData) {
      const items = mssData.map(item => createMSSListItem(item)).join('');
      mssList.innerHTML = items || '<li style="padding: 12px;"><p>No MSS data available</p></li>';
   }

   /**
    * Create individual MSS list item HTML
    */
   function createMSSListItem(item) {
      return `
         <li>
            <i class='bx bx-check-circle'></i>
            <span class="info-2">
               <p>${escapeHtml(item.grade)} <span><b>${escapeHtml(String(item.mean))}</b></span></p>
            </span>
         </li>
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
   MSSDetail.init();
});
