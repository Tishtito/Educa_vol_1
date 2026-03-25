(function () {
    const baseUrl = "../../backend/public/index.php";

    async function loadExamDetails() {
        const gradesList = document.getElementById("grades-list");
        
        if (!gradesList) {
            return;
        }

        // Parse query parameters
        var params = new URLSearchParams(window.location.search);
        var examId = params.get("exam_id");
        var token = params.get("token");

        if (!examId || !token) {
            gradesList.innerHTML = '<li><span class="info-2"><p>Invalid exam parameters</p></span></li>';
            return;
        }

        // Show loading indicator
        gradesList.innerHTML = '<li><i class="bx bx-loader-alt"></i><span class="info-2"><p>Loading grades...</p></span></li>';

        try {
            // Auth check is prerequisite
            var authRes = await fetch(baseUrl + "/auth/check", { credentials: "include" });
            var auth = await authRes.json();
            if (!auth.authenticated) {
                window.location.replace("../login.html");
                return;
            }

            // Fetch grades
            var gradesUrl = baseUrl + "/analysis/grades?exam_id=" + encodeURIComponent(examId) + "&token=" + encodeURIComponent(token);
            var gradesRes = await fetch(gradesUrl, { credentials: "include" });
            var grades = await gradesRes.json();

            if (!grades.success || !Array.isArray(grades.data) || grades.data.length === 0) {
                gradesList.innerHTML = '<li><i class="bx bx-info-circle"></i><span class="info-2"><p>No grades found</p></span></li>';
                return;
            }

            // Batch DOM updates: build HTML array, then join and assign once
            var gradeItems = [];

            grades.data.forEach(function (grade) {
                var gradeVal = grade.grade || "N/A";
                var gradeToken = grade.token || token;
                
                var gradeHTML = '<a href="mark_list.html?exam_id=' + encodeURIComponent(examId) + 
                    '&grade=' + encodeURIComponent(gradeVal) + 
                    '&token=' + encodeURIComponent(gradeToken) + '">' +
                    '<li>' +
                    '<i class="bx bx-show-alt"></i>' +
                    '<span class="info-2">' +
                    '<p>' + gradeVal + '</p>' +
                    '</span>' +
                    '</li>' +
                    '</a>';

                gradeItems.push(gradeHTML);
            });

            // Single DOM update: batched innerHTML instead of appendChild loop
            gradesList.innerHTML = gradeItems.join("");

        } catch (error) {
            console.error("Exam details load failed", error);
            gradesList.innerHTML = '<li><i class="bx bx-error"></i><span class="info-2"><p>Failed to load grades</p></span></li>';
        }
    }

    // Load when DOM is ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", loadExamDetails);
    } else {
        loadExamDetails();
    }
})();
