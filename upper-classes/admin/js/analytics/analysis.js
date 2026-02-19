(function () {
    const baseUrl = "../../backend/public/index.php";

    async function loadAnalysis() {
        const examList = document.getElementById("exam-marklists");
        const streamList = document.getElementById("stream-marklists");

        if (!examList || !streamList) {
            return;
        }

        // Show loading indicators
        const loadingHTML = '<li><div class="task-title"><i class="bx bx-loader-alt"></i><p>Loading...</p></div></li>';
        examList.innerHTML = loadingHTML;
        streamList.innerHTML = loadingHTML;

        try {
            // Auth check is a prerequisite, so it must run first
            const authRes = await fetch(baseUrl + "/auth/check", { credentials: "include" });
            const auth = await authRes.json();
            if (!auth.authenticated) {
                window.location.replace("../login.html");
                return;
            }

            // Fetch exams
            const examsRes = await fetch(baseUrl + "/analysis/exams", { credentials: "include" });
            const exams = await examsRes.json();

            if (!exams.success || !Array.isArray(exams.data) || exams.data.length === 0) {
                const emptyHTML = '<li><div class="task-title"><i class="bx bx-info-circle"></i><p>No exams found</p></div></li>';
                examList.innerHTML = emptyHTML;
                streamList.innerHTML = emptyHTML;
                return;
            }

            // Batch DOM updates using innerHTML + join instead of appendChild loop
            var examItems = [];
            var streamItems = [];

            exams.data.forEach(function (exam) {
                var examId = encodeURIComponent(exam.exam_id);
                var token = encodeURIComponent(exam.token);
                var examName = exam.exam_name || "Exam";

                var examHTML = '<a href="exam_details.html?exam_id=' + examId + '&token=' + token + '">' +
                    '<li class="completed">' +
                    '<div class="task-title">' +
                    '<i class="bx bx-book"></i>' +
                    '<p>' + examName + '<span></span></p>' +
                    '</div>' +
                    '<i class="bx bx-dots-vertical-rounded"></i>' +
                    '</li>' +
                    '</a>';

                var streamHTML = '<a href="stream_details.html?exam_id=' + examId + '&token=' + token + '">' +
                    '<li class="completed">' +
                    '<div class="task-title">' +
                    '<i class="bx bx-book"></i>' +
                    '<p>' + examName + '<span></span></p>' +
                    '</div>' +
                    '<i class="bx bx-dots-vertical-rounded"></i>' +
                    '</li>' +
                    '</a>';

                examItems.push(examHTML);
                streamItems.push(streamHTML);
            });

            // Batch update: single innerHTML assignment instead of multiple appendChild calls
            examList.innerHTML = examItems.join("");
            streamList.innerHTML = streamItems.join("");

        } catch (error) {
            console.error("Analysis load failed", error);
            const errorHTML = '<li><div class="task-title"><i class="bx bx-error"></i><p>Failed to load</p></div></li>';
            examList.innerHTML = errorHTML;
            streamList.innerHTML = errorHTML;
        }
    }

    // Load when DOM is ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", loadAnalysis);
    } else {
        loadAnalysis();
    }
})();
