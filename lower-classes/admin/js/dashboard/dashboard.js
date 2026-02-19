/**
 * Dashboard Module
 * Handles loading and displaying dashboard summary and exam data
 */

const Dashboard = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let totalStudentsEl, totalTeachersEl;
   let topStudentsBody, examList;

   /**
    * Initialize the dashboard
    */
   async function init() {
      cacheDOMElements();
      await loadDashboardData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      totalStudentsEl = document.getElementById("total-students");
      totalTeachersEl = document.getElementById("total-teachers");
      topStudentsBody = document.getElementById("top-students-body");
      examList = document.getElementById("exam-list");
   }

   /**
    * Load all dashboard data
    */
   async function loadDashboardData() {
      try {
         // Show loading states
         showLoadingStates();

         // Run all API calls in parallel
         const [authRes, summaryRes, topExamsRes, examsRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(`${BASE_URL}/dashboard/summary`, { credentials: "include" }),
            fetch(`${BASE_URL}/dashboard/top-exams`, { credentials: "include" }),
            fetch(`${BASE_URL}/dashboard/exams`, { credentials: "include" })
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         // Process all responses
         const [summary, topExams, exams] = await Promise.all([
            summaryRes.json(),
            topExamsRes.json(),
            examsRes.json()
         ]);

         // Render dashboard data
         if (summary.success) {
            renderSummary(summary.data);
         }

         if (topExams.success) {
            renderTopExams(topExams.data);
         } else {
            showErrorInTable("Failed to load exams data");
         }

         if (exams.success) {
            renderExamsList(exams.data);
         } else {
            showErrorInExamList("Failed to load exams list");
         }
      } catch (error) {
         console.error("Dashboard load failed:", error);
         showErrorInTable("Error loading dashboard data");
         showErrorInExamList("Error loading exams list");
      }
   }

   /**
    * Show loading states in containers
    */
   function showLoadingStates() {
      if (totalStudentsEl) totalStudentsEl.textContent = "Loading...";
      if (totalTeachersEl) totalTeachersEl.textContent = "Loading...";
      if (topStudentsBody) topStudentsBody.innerHTML = '<tr><td colspan="3">Loading exams...</td></tr>';
      if (examList) examList.innerHTML = '<li><p>Loading exams...</p></li>';
   }

   /**
    * Show error state in table
    */
   function showErrorInTable(message) {
      if (topStudentsBody) {
         topStudentsBody.innerHTML = `<tr><td colspan="3" style="color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Show error state in exam list
    */
   function showErrorInExamList(message) {
      if (examList) {
         examList.innerHTML = `<li style="color: #e74c3c; padding: 12px;"><p>${escapeHtml(message)}</p></li>`;
      }
   }

   /**
    * Render summary statistics
    */
   function renderSummary(data) {
      if (totalStudentsEl) {
         totalStudentsEl.textContent = escapeHtml(String(data.total_students ?? 0));
      }
      if (totalTeachersEl) {
         totalTeachersEl.textContent = escapeHtml(String(data.total_examiners ?? 0));
      }
   }

   /**
    * Render top exams table using batch rendering
    */
   function renderTopExams(examsData) {
      const rows = examsData.map(exam => createExamTableRow(exam)).join('');
      topStudentsBody.innerHTML = rows || '<tr><td colspan="3">No exam data available</td></tr>';
   }

   /**
    * Create individual exam table row HTML
    */
   function createExamTableRow(exam) {
      return `
         <tr>
            <td>${escapeHtml(exam.name)}</td>
            <td>${escapeHtml(exam.date)}</td>
            <td>${escapeHtml(String(exam.total_students))}</td>
         </tr>
      `;
   }

   /**
    * Render exams list using batch rendering
    */
   function renderExamsList(examsData) {
      const items = examsData.map(exam => createExamListItem(exam)).join('');
      examList.innerHTML = items || '<li style="padding: 12px;"><p>No exams available</p></li>';
   }

   /**
    * Create individual exam list item HTML
    */
   function createExamListItem(exam) {
      const href = `../analytics/mss-detail.html?exam_id=${encodeURIComponent(exam.exam_id)}&token=${encodeURIComponent(exam.token)}`;
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
   Dashboard.init();
});
