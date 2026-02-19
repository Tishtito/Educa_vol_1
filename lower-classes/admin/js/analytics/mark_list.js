/**
 * Mark List Module
 * Handles loading and displaying student marks, scores, and deviations
 */

const MarkList = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let examTitle, gradeTitle, tutorTitle;
   let headTopRow, headSubRow;
   let bodyTable, footTable;

   // URL parameters
   let examId, grade, token;

   /**
    * Create subject alias map (same as backend)
    * Removes special characters from subject names to create database column aliases
    */
   function createSubjectAliasMap(subjects) {
      const aliasMap = {};
      subjects.forEach(subject => {
         const alias = subject.replace(/[\/\-\s]/g, '');
         aliasMap[subject] = alias;
      });
      return aliasMap;
   }

   /**
    * Initialize the mark list page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();

      if (!examId || !grade || !token) {
         showErrorState("Missing exam parameters");
         return;
      }

      await loadMarkListData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      examTitle = document.getElementById("marklist-exam");
      gradeTitle = document.getElementById("marklist-grade");
      tutorTitle = document.getElementById("marklist-tutor");
      headTopRow = document.getElementById("marklist-head-top");
      headSubRow = document.getElementById("marklist-head-sub");
      bodyTable = document.getElementById("marklist-body");
      footTable = document.getElementById("marklist-foot");
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
    * Load mark list data
    */
   async function loadMarkListData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and marks fetch in parallel
         const [authRes, marksRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(
               `${BASE_URL}/marks/list?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade)}&token=${encodeURIComponent(token)}`,
               { credentials: "include" }
            )
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         // Process marks data
         const payload = await marksRes.json();
         if (payload.success && payload.data) {
            renderMarkList(payload.data);
         } else {
            showErrorState(payload.message || "Failed to load mark list");
         }
      } catch (error) {
         console.error("Mark list load failed:", error);
         showErrorState("Error loading mark list data");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      bodyTable.innerHTML = '<tr><td colspan="10">Loading marks...</td></tr>';
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      bodyTable.innerHTML = `<tr><td colspan="10" style="color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
   }

   /**
    * Render complete mark list with headers, body, and footer
    */
   function renderMarkList(data) {
      // Update titles
      examTitle.textContent = `Mark List - ${escapeHtml(data.exam_name)}`;
      gradeTitle.textContent = `Grade: ${escapeHtml(data.grade_title)}`;
      tutorTitle.textContent = `Tutor: ${escapeHtml(data.tutor)}`;

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
      let topHeaderHTML = '<th rowspan="2">Rank</th><th rowspan="2">Name</th>';
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
      const aliasMap = createSubjectAliasMap(subjects);
      const rows = students.map(student => createStudentRow(student, subjects, aliasMap)).join('');
      bodyTable.innerHTML = rows || '<tr><td colspan="10">No student data available</td></tr>';
   }

   /**
    * Create individual student row HTML
    */
   function createStudentRow(student, subjects, aliasMap) {
      let row = `<tr><td>${escapeHtml(String(student.rank))}</td><td>${escapeHtml(student.Name)}</td>`;

      subjects.forEach(subject => {
         const alias = aliasMap[subject];
         const mark = student[alias] ?? '-';
         const pl = student[`PL_${alias}`] ?? '-';
         row += `<td>${escapeHtml(String(mark))}</td><td>${escapeHtml(String(pl))}</td>`;
      });

      row += `<td>${escapeHtml(String(student.total_marks ?? 0))}</td></tr>`;
      return row;
   }

   /**
    * Render table footer with mean scores, previous means, and deviations
    */
   function renderTableFooter(data) {
      const aliasMap = createSubjectAliasMap(data.subjects);
      const meanRow = createMeanRow(data, aliasMap);
      const prevRow = createPreviousMeanRow(data, aliasMap);
      const devRow = createDeviationRow(data, aliasMap);

      footTable.innerHTML = meanRow + prevRow + devRow;
   }

   /**
    * Create mean scores row
    */
   function createMeanRow(data, aliasMap) {
      let row = '<tr><th colspan="2">Mean Scores</th>';

      data.subjects.forEach(subject => {
         const mean = data.mean_scores[subject] ?? 0;
         row += `<td colspan="2">${escapeHtml(String(mean))}</td>`;
      });

      row += `<td colspan="2">${escapeHtml(String(data.total_mean))}</td></tr>`;
      return row;
   }

   /**
    * Create previous mean scores row
    */
   function createPreviousMeanRow(data, aliasMap) {
      let row = '<tr><th colspan="2">Previous Mean Scores</th>';

      data.subjects.forEach(subject => {
         const prevMean = data.prev_mean_scores[subject] ?? '-';
         row += `<td colspan="2">${escapeHtml(String(prevMean))}</td>`;
      });

      row += `<td colspan="2">${escapeHtml(String(data.prev_total_mean))}</td></tr>`;
      return row;
   }

   /**
    * Create deviation scores row with color coding
    */
   function createDeviationRow(data, aliasMap) {
      let row = '<tr><th colspan="2">Deviation</th>';

      data.subjects.forEach(subject => {
         const dev = data.deviation_scores[subject];
         const { color, display } = formatDeviationValue(dev);
         row += `<td colspan="2" style="color: ${color}">${escapeHtml(display)}</td>`;
      });

      const { color: totalColor, display: totalDisplay } = formatDeviationValue(data.total_mean_deviation);
      row += `<td colspan="2" style="color: ${totalColor}">${escapeHtml(totalDisplay)}</td></tr>`;

      return row;
   }

   /**
    * Format deviation value with appropriate color and display
    * Returns object with color and display string
    */
   function formatDeviationValue(dev) {
      if (dev === undefined || dev === null || dev === '-') {
         return { color: 'black', display: '-' };
      }

      const numValue = Number(dev);
      let color = 'black';
      let display = String(dev);

      if (!isNaN(numValue)) {
         if (numValue > 0) {
            color = 'green';
            display = `+${dev}`;
         } else if (numValue < 0) {
            color = 'red';
         }
      }

      return { color, display };
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
   MarkList.init();
});

// Make printPage available globally for inline onclick
window.printPage = MarkList.printPage;
