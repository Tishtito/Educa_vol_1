/**
 * Combined Report Module
 * Handles loading and displaying combined mid-term and end-term student report forms
 */

const CombinedReportForm = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let termLabel, studentNameEl, studentClassEl, examYearEl;
   let tutorNameEl, printDateEl;
   let resultsBody;
   let totalMidEl, totalEndEl, totalOutOfEl, totalOutOf2El;
   let averageMarksEl, averagePLEl, teacherCommentEl, principalCommentEl;

   // URL parameters
   let studentId, examId, token;

   /**
    * Initialize the combined report form
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
      termLabel = document.getElementById("term-label");
      studentNameEl = document.getElementById("student-name");
      studentClassEl = document.getElementById("student-class");
      examYearEl = document.getElementById("exam-year");
      tutorNameEl = document.getElementById("tutor-name");
      printDateEl = document.getElementById("print-date");
      resultsBody = document.getElementById("results-body");
      totalMidEl = document.getElementById("total-mid");
      totalEndEl = document.getElementById("total-end");
      totalOutOfEl = document.getElementById("total-out-of");
      totalOutOf2El = document.getElementById("total-out-of-2");
      averageMarksEl = document.getElementById("average-marks");
      averagePLEl = document.getElementById("average-pl");
      teacherCommentEl = document.getElementById("teacher-comment");
      principalCommentEl = document.getElementById("principal-comment");
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
    * Load combined report data
    */
   async function loadReportData() {
      try {
         // Show loading state
         showLoadingState();

         // Run auth check and report fetch in parallel
         const [authRes, dataRes] = await Promise.all([
            fetch(`${BASE_URL}/auth/check`, { credentials: "include" }),
            fetch(
               `${BASE_URL}/reports/report-combined?student_id=${encodeURIComponent(studentId)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`,
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
            renderCombinedReport(payload.data);
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
      if (resultsBody) {
         resultsBody.innerHTML = '<tr><td colspan="8">Loading report data...</td></tr>';
      }
   }

   /**
    * Show error state with message
    */
   function showErrorState(message) {
      if (resultsBody) {
         resultsBody.innerHTML = `<tr><td colspan="8" style="color: #e74c3c;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render the complete combined report
    */
   function renderCombinedReport(data) {
      const { student, tutor, term, exam_year, mid_results, end_results, levels, subjects } = data;

      // Update header information
      termLabel.textContent = escapeHtml(term || '-');
      studentNameEl.textContent = escapeHtml(student.name || '-');
      studentClassEl.textContent = escapeHtml(student.class || '-');
      examYearEl.textContent = exam_year ? ` ${escapeHtml(String(exam_year))}` : '';
      tutorNameEl.textContent = escapeHtml(tutor || 'Not Assigned');
      printDateEl.textContent = formatPrintDate(new Date());

      // Render results table
      renderResultsTable(subjects, mid_results, end_results, levels);
   }

   /**
    * Render results table with batch rendering
    */
   function renderResultsTable(subjects, midResults, endResults, levels) {
      const subjectKeys = Object.keys(subjects || {});

      if (subjectKeys.length === 0) {
         resultsBody.innerHTML = '<tr><td colspan="8">No subject data available</td></tr>';
         return;
      }

      // Build rows and calculate totals
      let totalMarksMid = 0;
      let totalMarksEnd = 0;
      let totalMarksAvg = 0;

      const rows = subjectKeys.map(key => {
         const subjectName = subjects[key];
         const midScore = midResults ? midResults[key] : null;
         const endScore = endResults ? endResults[key] : null;

         const midPerf = getPerformanceLevel(midScore, levels);
         const endPerf = getPerformanceLevel(endScore, levels);

         const midVal = midScore ?? 0;
         const endVal = endScore ?? 0;
         const avgScore = (midScore !== null || endScore !== null) ? (Number(midVal) + Number(endVal)) / 2 : null;
         const avgPerf = getPerformanceLevel(avgScore, levels);

         // Track totals
         if (midScore !== null && midScore !== undefined) totalMarksMid += Number(midScore);
         if (endScore !== null && endScore !== undefined) totalMarksEnd += Number(endScore);
         if (avgScore !== null && avgScore !== undefined) totalMarksAvg += Number(avgScore);

         return createResultRow(subjectName, midScore, midPerf, endScore, endPerf, avgScore, avgPerf);
      }).join('');

      resultsBody.innerHTML = rows;

      // Calculate and display summary statistics
      const totalOutOf = subjectKeys.length * 100;
      const percentage = totalOutOf > 0 ? (totalMarksAvg / totalOutOf) * 100 : 0;
      const formattedPercentage = percentage.toFixed(2);
      const avgPerformance = getPerformanceLevel(percentage, levels);
      const comments = generateComments(percentage);

      totalMidEl.textContent = escapeHtml(String(totalMarksMid));
      totalEndEl.textContent = escapeHtml(String(totalMarksEnd));
      totalOutOfEl.textContent = escapeHtml(String(totalOutOf));
      totalOutOf2El.textContent = escapeHtml(String(totalOutOf));
      averageMarksEl.textContent = escapeHtml(formattedPercentage);
      averagePLEl.textContent = escapeHtml(avgPerformance.pl);
      teacherCommentEl.textContent = comments.teacher;
      principalCommentEl.textContent = comments.principal;
   }

   /**
    * Create individual result row HTML
    */
   function createResultRow(subjectName, midScore, midPerf, endScore, endPerf, avgScore, avgPerf) {
      const displayMid = midScore ?? '-';
      const displayEnd = endScore ?? '-';
      const displayAvg = avgScore !== null ? avgScore.toFixed(1) + '%' : '-';

      return `
         <tr>
            <td>${escapeHtml(subjectName)}</td>
            <td>${escapeHtml(String(displayMid))}</td>
            <td>${escapeHtml(midPerf.ab)}</td>
            <td>${escapeHtml(String(displayEnd))}</td>
            <td>${escapeHtml(endPerf.ab)}</td>
            <td>${escapeHtml(displayAvg)}</td>
            <td>${escapeHtml(avgPerf.ab)}</td>
            <td>${escapeHtml(avgPerf.pl)}</td>
         </tr>
      `;
   }

   /**
    * Get performance level (pl and ab) for a given score
    */
   function getPerformanceLevel(score, levels) {
      if (score === null || score === undefined) {
         return { pl: '-', ab: '-' };
      }

      const numScore = Number(score);
      for (const level of levels) {
         if (numScore >= level.min_marks && numScore <= level.max_marks) {
            return { pl: level.pl, ab: level.ab };
         }
      }

      return { pl: 'UNKNOWN', ab: 'UNKNOWN' };
   }

   /**
    * Generate teacher and principal comments based on percentage
    */
   function generateComments(percentage) {
      let teacher = '';
      let principal = '';

      if (percentage >= 80) {
         teacher = "Excellent performance! You have demonstrated outstanding mastery of the subjects. Keep up the good work!";
      } else if (percentage >= 70) {
         teacher = "Very good performance. You're doing well, but there's still room for improvement in some areas.";
      } else if (percentage >= 60) {
         teacher = "Good effort. Continue working hard and pay more attention to the subjects where you scored lower.";
      } else if (percentage >= 50) {
         teacher = "Average performance. You need to put in more effort, especially in your weaker subjects.";
      } else {
         teacher = "Below average performance. Immediate improvement is needed. Please seek extra help from your teachers.";
      }

      if (percentage >= 85) {
         principal = "Exceptional work! You're a model student for the school. Maintain this excellent standard.";
      } else if (percentage >= 75) {
         principal = "Commendable performance. With consistent effort, you can achieve even greater results.";
      } else if (percentage >= 60) {
         principal = "Satisfactory performance. Focus on improving your weaker areas to reach your full potential.";
      } else if (percentage >= 50) {
         principal = "Your results indicate you need to work harder. We believe you can do better with more dedication.";
      } else {
         principal = "We're concerned about your performance. Please meet with your grade teacher to discuss improvement strategies.";
      }

      return { teacher, principal };
   }

   /**
    * Format date for print display (e.g., "17th Feb 2026")
    */
   function formatPrintDate(date) {
      const day = date.getDate();
      const suffix = (d) => {
         if (d > 3 && d < 21) return 'th';
         switch (d % 10) {
            case 1:
               return 'st';
            case 2:
               return 'nd';
            case 3:
               return 'rd';
            default:
               return 'th';
         }
      };
      const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      return `${day}${suffix(day)} ${months[date.getMonth()]} ${date.getFullYear()}`;
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
   CombinedReportForm.init();
});

// Make printPage available globally for inline onclick
window.printPage = CombinedReportForm.printPage;
