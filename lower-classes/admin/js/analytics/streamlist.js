/**
 * Stream List Module
 * Handles loading and displaying stream-based mark lists with performance levels
 */

const StreamList = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let examTitle, gradeTitle;
   let headTopRow, headSubRow;
   let bodyTable, footTable;

   // URL parameters
   let examId, grade, token;

   // Performance levels data
   let performanceLevels = [];

   /**
    * Initialize the stream list page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();

      if (!examId || !grade || !token) {
         showErrorState("Missing exam parameters");
         return;
      }

      await loadStreamListData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      examTitle = document.getElementById("streamlist-exam");
      gradeTitle = document.getElementById("streamlist-grade");
      headTopRow = document.getElementById("streamlist-head-top");
      headSubRow = document.getElementById("streamlist-head-sub");
      bodyTable = document.getElementById("streamlist-body");
      footTable = document.getElementById("streamlist-foot");
   }

   /**
    * Extract and validate URL parameters
    */
   function extractURLParameters() {
      const params = new URLSearchParams(window.location.search);
      examId = params.get("exam_id");
      grade = params.get("grade");
      token = params.get("token");
   }

   /**
    * Load stream list data
    */
   async function loadStreamListData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and stream list fetch in parallel
         const [authRes, streamRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(
               `${BASE_URL}/streams/list?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade)}&token=${encodeURIComponent(token)}`,
               { credentials: "include" }
            )
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         // Process stream list data
         const payload = await streamRes.json();
         if (payload.success && payload.data) {
            renderStreamList(payload.data);
         } else {
            showErrorState(payload.message || "Failed to load stream list");
         }
      } catch (error) {
         console.error("Stream list load failed:", error);
         showErrorState("Error loading stream list data");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      bodyTable.innerHTML = '<tr><td colspan="10">Loading stream data...</td></tr>';
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      bodyTable.innerHTML = `<tr><td colspan="10" style="color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
   }

   /**
    * Render complete stream list with headers, body, and footer
    */
   function renderStreamList(data) {
      // Store performance levels for use in calculations
      performanceLevels = data.performance_levels || [];

      // Update titles
      examTitle.textContent = `Mark List - ${escapeHtml(data.exam_name)}`;
      gradeTitle.textContent = `Grade: ${escapeHtml(data.grade)}`;

      // Render table headers
      renderTableHeaders(data.subjects);

      // Render student marks
      renderStudentMarks(data.subjects, data.students);

      // Render footer with statistics
      renderTableFooter(data);
   }

   /**
    * Render table headers (top and sub-headers)
    */
   function renderTableHeaders(subjects) {
      // Build top header row
      let topHeaderHTML = '<th rowspan="2">Rank</th><th rowspan="2">Name</th><th rowspan="2">Class</th>';
      subjects.forEach(subject => {
         topHeaderHTML += `<th colspan="2">${escapeHtml(subject)}</th>`;
      });
      topHeaderHTML += '<th rowspan="2">Total Marks</th>';

      // Build sub-header row
      let subHeaderHTML = '';
      subjects.forEach(() => {
         subHeaderHTML += '<th>Marks</th><th>PL</th>';
      });

      headTopRow.innerHTML = topHeaderHTML;
      headSubRow.innerHTML = subHeaderHTML;
   }

   /**
    * Render student marks rows using batch rendering
    */
   function renderStudentMarks(subjects, students) {
      const rows = students.map(student => createStudentRow(student, subjects)).join('');
      bodyTable.innerHTML = rows || '<tr><td colspan="10">No student data available</td></tr>';
   }

   /**
    * Create individual student row HTML
    */
   function createStudentRow(student, subjects) {
      let row = `<tr><td>${escapeHtml(String(student.rank))}</td><td>${escapeHtml(student.Name)}</td><td>${escapeHtml(student.Class)}</td>`;

      subjects.forEach(subject => {
         const mark = student[subject] ?? '-';
         const pl = getPerformanceLevel(mark);
         row += `<td>${escapeHtml(String(mark))}</td><td>${escapeHtml(pl)}</td>`;
      });

      row += `<td>${escapeHtml(String(student.Total_marks ?? 0))}</td></tr>`;
      return row;
   }

   /**
    * Get performance level abbreviation for a given mark
    */
   function getPerformanceLevel(score) {
      // Handle non-numeric scores
      if (score === '-' || score === null || score === undefined) {
         return '-';
      }

      const numeric = Number(score);
      if (isNaN(numeric)) {
         return '-';
      }

      // Find matching performance level
      const match = performanceLevels.find(pl => 
         numeric >= pl.min_marks && numeric <= pl.max_marks
      );

      return match ? match.ab : '-';
   }

   /**
    * Render table footer with mean scores
    */
   function renderTableFooter(data) {
      const meanRow = createMeanRow(data);
      footTable.innerHTML = meanRow;
   }

   /**
    * Create mean scores row
    */
   function createMeanRow(data) {
      let row = '<tr><th colspan="3">Mean Scores</th>';

      data.subjects.forEach(subject => {
         const mean = data.mean_scores[subject] ?? 0;
         row += `<td colspan="2">${escapeHtml(String(mean))}</td>`;
      });

      row += `<td>${escapeHtml(String(data.total_mean ?? 0))}</td></tr>`;
      return row;
   }

   /**
    * Print the current page
    */
   function printPage() {
      window.print();
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
      init,
      printPage
   };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
   StreamList.init();
});

// Make printPage available globally for inline onclick
window.printPage = StreamList.printPage;
