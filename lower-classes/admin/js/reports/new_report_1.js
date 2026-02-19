/**
 * Report Module
 * Handles loading and displaying student report forms with grades and comments
 */

const ReportForm = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let termLabel, studentNameEl, studentClassEl, examYearEl;
   let examTypeLabel, examHeaderEl, tutorNameEl, printDateEl;
   let resultsBody, totalMarksEl, totalOutOfEl;
   let averageMarksEl, averagePLEl, teacherCommentEl, principalCommentEl;

   // URL parameters
   let studentId, examId, token;

   /**
    * Initialize the report form
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
      examTypeLabel = document.getElementById("exam-type-label");
      examHeaderEl = document.getElementById("exam-header");
      tutorNameEl = document.getElementById("tutor-name");
      printDateEl = document.getElementById("print-date");
      resultsBody = document.getElementById("results-body");
      totalMarksEl = document.getElementById("total-marks");
      totalOutOfEl = document.getElementById("total-out-of");
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
            renderReportForm(payload.data);
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
    * Render the complete report form
    */
   function renderReportForm(data) {
      const { student, tutor, exam, results, levels, subjects } = data;

      // Update header information
      termLabel.textContent = escapeHtml(exam.term || '-');
      studentNameEl.textContent = escapeHtml(student.name || '-');
      studentClassEl.textContent = escapeHtml(student.class || '-');
      examYearEl.textContent = exam.exam_year ? ` ${escapeHtml(String(exam.exam_year))}` : '';
      examTypeLabel.textContent = escapeHtml(exam.exam_type || 'Exam');
      examHeaderEl.textContent = `${escapeHtml(exam.exam_type || 'Exam')} out of 100`;
      tutorNameEl.textContent = escapeHtml(tutor || 'Not Assigned');
      printDateEl.textContent = formatPrintDate(new Date());

      // Render results table
      renderResultsTable(subjects, results, levels);

      // Set print date
      printDateEl.textContent = formatPrintDate(new Date());
   }

   /**
    * Render results table with batch rendering
    */
   function renderResultsTable(subjects, results, levels) {
      const subjectKeys = Object.keys(subjects || {});

      if (subjectKeys.length === 0) {
         resultsBody.innerHTML = '<tr><td colspan="8">No subject data available</td></tr>';
         return;
      }

      // Build rows and calculate totals
      let totalMarks = 0;
      const rows = subjectKeys.map(key => {
         const subjectName = subjects[key];
         const score = results[key];
         const performance = getPerformanceData(score, levels);

         if (score !== null && score !== undefined) {
            totalMarks += Number(score);
         }

         return createResultRow(subjectName, score, performance);
      }).join('');

      resultsBody.innerHTML = rows;

      // Calculate and display summary statistics
      const totalOutOf = subjectKeys.length * 100;
      const percentage = totalOutOf > 0 ? (totalMarks / totalOutOf) * 100 : 0;
      const formattedPercentage = percentage.toFixed(2);
      const avgPerformance = getPerformanceData(percentage, levels);
      const comments = generateComments(percentage);

      totalMarksEl.textContent = escapeHtml(String(totalMarks));
      totalOutOfEl.textContent = escapeHtml(String(totalOutOf));
      averageMarksEl.textContent = escapeHtml(formattedPercentage);
      averagePLEl.textContent = escapeHtml(avgPerformance.pl);
      teacherCommentEl.textContent = comments.teacher;
      principalCommentEl.textContent = comments.principal;
   }

   /**
    * Create individual result row HTML
    */
   function createResultRow(subjectName, score, performance) {
      const displayScore = score ?? '-';
      const displayPercentage = score !== null && score !== undefined ? `${score} %` : '-';

      return `
         <tr>
            <td>${escapeHtml(subjectName)}</td>
            <td>${escapeHtml(String(displayScore))}</td>
            <td>${escapeHtml(performance.ab)}</td>
            <td>-</td>
            <td>-</td>
            <td>${escapeHtml(displayPercentage)}</td>
            <td>${escapeHtml(performance.ab)}</td>
            <td>${escapeHtml(performance.pl)}</td>
         </tr>
      `;
   }

   /**
    * Get performance data (pl and ab) for a given score
    */
   function getPerformanceData(score, levels) {
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
   ReportForm.init();
});

// Make printPage available globally for inline onclick
window.printPage = ReportForm.printPage;
