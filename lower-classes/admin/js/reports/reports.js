/**
 * Reports Module
 * Handles displaying categorized exam reports (Weekly, Opener, Mid-Term, End-Term)
 */

const Reports = (() => {
   const BASE_URL = "../../backend/public/index.php";

   // Cache DOM elements
   let weeklyList, openerList, midtermList, endtermList;

   // Exam type configuration
   const examTypeConfig = {
      weekly: {
         containerId: 'weekly-list',
         type: 'Weekly',
         emptyText: 'No Weekly Exams Found'
      },
      opener: {
         containerId: 'opener-list',
         type: 'Opener',
         emptyText: 'No Opener Exams Found'
      },
      midterm: {
         containerId: 'midterm-list',
         type: 'Mid-Term',
         emptyText: 'No Mid-Term Exams Found'
      },
      endterm: {
         containerId: 'endterm-list',
         type: 'End-Term',
         emptyText: 'No End of Term Exams Found'
      }
   };

   /**
    * Initialize the reports page
    */
   async function init() {
      cacheDOMElements();
      await loadAllExamTypes();
   }

   /**
    * Cache frequently accessed DOM elements
    */
   function cacheDOMElements() {
      weeklyList = document.getElementById('weekly-list');
      openerList = document.getElementById('opener-list');
      midtermList = document.getElementById('midterm-list');
      endtermList = document.getElementById('endterm-list');
   }

   /**
    * Load all exam types in parallel
    */
   async function loadAllExamTypes() {
      try {
         showLoadingStates();

         const authRes = await fetch(`${BASE_URL}/auth/check`, { credentials: "include" });
         const auth = await authRes.json();

         if (!auth.authenticated) {
            window.location.replace("Pages/login.html");
            return;
         }

         // Fetch all 4 exam types in parallel
         const [weeklyRes, openerRes, midtermRes, endtermRes] = await Promise.all([
            fetch(`${BASE_URL}/reports/exams?type=Weekly`, { credentials: "include" }),
            fetch(`${BASE_URL}/reports/exams?type=Opener`, { credentials: "include" }),
            fetch(`${BASE_URL}/reports/exams?type=Mid-Term`, { credentials: "include" }),
            fetch(`${BASE_URL}/reports/exams?type=End-Term`, { credentials: "include" })
         ]);

         // Parse all responses in parallel
         const [weekly, opener, midterm, endterm] = await Promise.all([
            weeklyRes.json(),
            openerRes.json(),
            midtermRes.json(),
            endtermRes.json()
         ]);

         // Render each list
         renderList(weeklyList, weekly.success ? weekly.data : [], examTypeConfig.weekly.emptyText);
         renderList(openerList, opener.success ? opener.data : [], examTypeConfig.opener.emptyText);
         renderList(midtermList, midterm.success ? midterm.data : [], examTypeConfig.midterm.emptyText);
         renderList(endtermList, endterm.success ? endterm.data : [], examTypeConfig.endterm.emptyText);
      } catch (error) {
         console.error('Failed to load reports:', error);
         showErrorState('Error loading reports data');
      }
   }

   /**
    * Show loading states
    */
   function showLoadingStates() {
      const containers = [weeklyList, openerList, midtermList, endtermList];
      containers.forEach(container => {
         if (container) {
            container.innerHTML = '<li class="no-task">Loading...</li>';
         }
      });
   }

   /**
    * Show error state
    */
   function showErrorState(message) {
      const containers = [weeklyList, openerList, midtermList, endtermList];
      containers.forEach(container => {
         if (container) {
            container.innerHTML = `<li class="no-task">${escapeHtml(message)}</li>`;
         }
      });
   }

   /**
    * Render exam list with batch rendering
    */
   function renderList(container, exams, emptyText) {
      if (!container) return;

      if (!exams || exams.length === 0) {
         container.innerHTML = `<li class="no-task">${escapeHtml(emptyText)}</li>`;
         return;
      }

      const links = exams.map(exam => {
         const href = `report_select.html?exam_id=${encodeURIComponent(exam.exam_id)}&exam_type=${encodeURIComponent(exam.exam_type)}&token=${encodeURIComponent(exam.token)}`;

         return `
            <a href="${escapeHtml(href)}">
               <li class="completed">
                  <div class="task-title">
                     <i class='bx bx-book'></i>
                     <p>${escapeHtml(exam.exam_name)}<span></span></p>
                  </div>
                  <i class='bx bx-dots-vertical-rounded'></i>
               </li>
            </a>
         `;
      }).join('');

      container.innerHTML = links;
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
   Reports.init();
});
