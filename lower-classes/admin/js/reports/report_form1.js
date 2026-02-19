/**
 * Performance Report Module
 * Handles loading and displaying single exam performance reports with percentages
 */

const PerformanceReport = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let studentNameEl, studentClassEl, tutorNameEl;
   let termLabelEl, examYearEl;
   let subjectsBody, totalMarksEl;

   // URL parameters
   let studentId, examId, token;

   /**
    * Initialize the performance report
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();

      if (!studentId || !examId || !token) {
         showErrorState("Missing report parameters");
         return;
      }

      await loadReportData();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      studentNameEl = document.getElementById("student-name");
      studentClassEl = document.getElementById("student-class");
      tutorNameEl = document.getElementById("tutor-name");
      termLabelEl = document.getElementById("term-label");
      examYearEl = document.getElementById("exam-year");
      subjectsBody = document.getElementById("subjects-body");
      totalMarksEl = document.getElementById("total-marks");
   }

   /**
    * Extract and validate URL parameters
    */
   function extractURLParameters() {
      const params = new URLSearchParams(window.location.search);
      studentId = params.get("student_id");
      examId = params.get("exam_id");
      token = params.get("token");
   }

   /**
    * Load report data
    */
   async function loadReportData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and report fetch in parallel
         const [authRes, dataRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(
               `${BASE_URL}/reports/report-single?student_id=${encodeURIComponent(studentId)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`,
               { credentials: "include" }
            )
         ]);

         // Check authentication
         const auth = await authRes.json();
         if (!auth.authenticated) {
            window.location.replace("../../Pages/login.html");
            return;
         }

         // Process report data
         const payload = await dataRes.json();
         if (payload.success && payload.data) {
            renderReport(payload.data);
         } else {
            showErrorState(payload.message || "Failed to load report");
         }
      } catch (error) {
         console.error("Report load failed:", error);
         showErrorState("Error loading report data");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (subjectsBody) {
         subjectsBody.innerHTML = '<tr><td colspan="4">Loading report data...</td></tr>';
      }
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      if (subjectsBody) {
         subjectsBody.innerHTML = `<tr><td colspan="4" style="color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render the complete report
    */
   function renderReport(data) {
      const { student, tutor, exam, results, levels, subjects } = data;

      // Update header information
      studentNameEl.textContent = escapeHtml(student.name || '-');
      studentClassEl.textContent = escapeHtml(student.class || '-');
      tutorNameEl.textContent = escapeHtml(tutor || 'Not Assigned');
      termLabelEl.textContent = escapeHtml(exam.term || '-');
      examYearEl.textContent = escapeHtml(String(exam.exam_year || '-'));

      // Render subjects table
      renderSubjectsTable(subjects, results, levels);
   }

   /**
    * Render subjects table with batch rendering
    */
   function renderSubjectsTable(subjects, results, levels) {
      const subjectKeys = Object.keys(subjects || {});

      if (subjectKeys.length === 0) {
         subjectsBody.innerHTML = '<tr><td colspan="4">No subject data available</td></tr>';
         return;
      }

      // Build rows and calculate totals
      let totalMarks = 0;

      const rows = subjectKeys.map((key, index) => {
         const subjectName = subjects[key];
         const value = results[key];

         // Track total
         if (value !== null && value !== undefined) {
            totalMarks += Number(value);
         }

         return createSubjectRow(index, subjectName, value, levels);
      }).join('');

      subjectsBody.innerHTML = rows;

      // Update total
      totalMarksEl.textContent = escapeHtml(String(totalMarks));
   }

   /**
    * Create individual subject row HTML
    */
   function createSubjectRow(index, subjectName, value, levels) {
      const perf = getPerformanceLevel(value, levels);
      const displayValue = value ?? '-';

      return `
         <tr>
            <td class="no">${index + 1}</td>
            <td class="text-left"><h3>${escapeHtml(subjectName)}</h3></td>
            <td class="unit">${escapeHtml(String(displayValue))}</td>
            <td class="total">${escapeHtml(perf)}</td>
         </tr>
      `;
   }

   /**
    * Get performance level for a given score
    */
   function getPerformanceLevel(score, levels) {
      if (score === null || score === undefined) {
         return 'UNKNOWN';
      }

      const numScore = Number(score);
      for (const level of levels) {
         if (numScore >= level.min_marks && numScore <= level.max_marks) {
            return level.pl;
         }
      }

      return 'UNKNOWN';
   }

   /**
    * Print the report
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
   PerformanceReport.init();

   // Attach print button handler
   const printButton = document.getElementById('printInvoice');
   if (printButton) {
      printButton.addEventListener('click', () => {
         PerformanceReport.printPage();
      });
   }
});
