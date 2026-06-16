/**
 * Report Table Module
 * Handles displaying students and report management per grade/exam
 */

const ReportTable = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let studentsList, reportLabel, downloadBtn, printBtn, printFrame;

   // URL parameters
   let examId, grade, examType, gradeToken, examToken;

   // Student data
   let studentData = [];

   // Mapping configuration
   const modeMap = {
      'Mid-Term': {
         viewPage: 'report_form1.html',
         printMode: 'print',
         includeExamType: true,
         reportFormPage: 'report_form1.html'
      },
      'End-Term': {
         viewPage: 'report_form.html',
         printMode: 'print',
         includeExamType: true,
         reportFormPage: 'report_form.html'
      },
      'Opener': {
         viewPage: 'report_form1.html',
         printMode: 'print',
         includeExamType: false,
         reportFormPage: 'report_form1.html'
      },
      'Weekly': {
         viewPage: 'report_form1.html',
         printMode: 'print',
         includeExamType: false,
         reportFormPage: 'report_form1.html'
      }
   };

   const labelMap = {
      'Mid-Term': 'Mid-Term',
      'End-Term': 'End of Term',
      'Opener': 'Opener',
      'Weekly': 'Weekly'
   };

   /**
    * Initialize the report table page
    */
   async function init() {
      cacheDOMElements();
      extractURLParameters();
      updatePageLabel();
      attachPrintHandler();

      if (!examId || !grade || !gradeToken || !examToken) {
         showErrorState("Invalid class or exam selected");
         return;
      }

      await loadStudents();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      studentsList = document.getElementById('students-body');
      reportLabel = document.getElementById('report-label');
      downloadBtn = document.getElementById('downloadAll');
      printBtn = document.getElementById('printAll');
      printFrame = document.getElementById('printFrame');
   }

   /**
    * Extract and validate URL parameters
    */
   function extractURLParameters() {
      const params = new URLSearchParams(window.location.search);
      examId = params.get('exam_id');
      grade = params.get('grade');
      examType = params.get('exam_type');
      gradeToken = params.get('token');
      examToken = params.get('exam_token');
   }

   /**
    * Update page label based on exam type
    */
   function updatePageLabel() {
      const reportName = labelMap[examType] || 'Report';
      if (reportLabel) {
         reportLabel.textContent = reportName;
      }
      document.title = `Reports - ${reportName}`;
   }

   /**
    * Load students for the grade/exam
    */
   async function loadStudents() {
      try {
         showLoadingState();

         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
         }

         const listRes = await fetch(
            `${BASE_URL}/reports/students?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade)}&token=${encodeURIComponent(gradeToken)}`,
            { credentials: "include" }
         );

         const list = await listRes.json();

         if (list.success && list.data && list.data.length > 0) {
            studentData = list.data;
            renderStudentsList(list.data);
            setupActionButtons();
         } else {
            showErrorState("No students found");
         }
      } catch (error) {
         console.error('Failed to load students:', error);
         showErrorState("Error loading students data");
      }
   }

   /**
    * Show loading state
    */
   function showLoadingState() {
      if (studentsList) {
         studentsList.innerHTML = '<tr><td colspan="4" style="text-align:center;">Loading...</td></tr>';
      }
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      if (studentsList) {
         studentsList.innerHTML = `<tr><td colspan="4" style="text-align:center;">${escapeHtml(message)}</td></tr>`;
      }
   }

   /**
    * Render students list with batch rendering
    */
   function renderStudentsList(students) {
      const mode = modeMap[examType] || modeMap['End-Term'];

      const rows = students.map((student, index) => {
         let viewHref = `${mode.viewPage}?student_id=${encodeURIComponent(student.student_id)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(examToken)}`;
         if (mode.includeExamType && examType) {
            viewHref += `&exam_type=${encodeURIComponent(examType)}`;
         }

         return `
            <tr>
               <td>${index + 1}</td>
               <td>${escapeHtml(student.name)}</td>
               <td>${escapeHtml(student.class)}</td>
               <td>
                  <a href="${escapeHtml(viewHref)}" class="view-report">
                     <span class="status process">View</span>
                  </a>
               </td>
            </tr>
         `;
      }).join('');

      if (studentsList) {
         studentsList.innerHTML = rows;
      }
   }

   /**
    * Setup action buttons (print/download)
    */
   function setupActionButtons() {
      const mode = modeMap[examType] || modeMap['End-Term'];

      if (mode.printMode === 'download') {
         if (downloadBtn) {
            downloadBtn.style.display = 'inline-block';
            downloadBtn.href = `${BASE_URL}/reports/download?grade=${encodeURIComponent(grade)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(gradeToken)}`;
         }
         if (printBtn) {
            printBtn.style.display = 'none';
         }
      } else {
         if (downloadBtn) {
            downloadBtn.style.display = 'none';
         }
         if (printBtn) {
            printBtn.style.display = 'inline-block';
         }
      }
   }

   /**
    * Attach print all handler
    */
   function attachPrintHandler() {
      if (printBtn) {
         printBtn.addEventListener('click', handlePrintAll);
      }
   }

   /**
    * Handle print all functionality
    */
   function handlePrintAll() {
      if (!studentData || studentData.length === 0) {
         alert('No students to print!');
         return;
      }

      const mode = modeMap[examType] || modeMap['End-Term'];

      try {
         const frameDoc = printFrame.contentDocument || printFrame.contentWindow.document;

         let content = "<html><head><title>All Reports</title>";
         content += '<link rel="stylesheet" href="../../css/report.css">';
         content += "</head><body>";

         studentData.forEach(student => {
            let formHref = `${mode.reportFormPage}?student_id=${encodeURIComponent(student.student_id)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(examToken)}`;
            if (mode.includeExamType && examType) {
               formHref += `&exam_type=${encodeURIComponent(examType)}`;
            }
            content += `<iframe src="${escapeHtml(formHref)}" style="width:100%; height:1550px; border:0;"></iframe>`;
            content += '<div style="page-break-before: always;"></div>';
         });

         content += "</body></html>";

         frameDoc.open();
         frameDoc.write(content);
         frameDoc.close();

         setTimeout(() => {
            printFrame.contentWindow.print();
         }, 2000);
      } catch (error) {
         console.error('Print failed:', error);
         alert('Failed to prepare print preview');
      }
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
   ReportTable.init();
});
