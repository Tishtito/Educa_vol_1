(async function () {
    const baseUrl = "../../backend/public/index.php";

    // Cache DOM elements
    const totalStudents = document.getElementById("total-students");
    const totalTeachers = document.getElementById("total-teachers");
    const topStudentsBody = document.getElementById("top-students-body");
    const examList = document.getElementById("exam-list");

    // Loading indicator
    const loading = document.createElement("div");
    loading.id = "loading-indicator";
    loading.style = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;font-size:2em;";
    loading.textContent = "Loading...";
    document.body.appendChild(loading);

    try {
        // Parallel fetch for auth and summary
        const [authRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        // Fetch dashboard summary, top exams, and exams in parallel
        const [summaryRes, topRes, examsRes] = await Promise.all([
            fetch(`${baseUrl}/dashboard/summary`, { credentials: "include" }),
            fetch(`${baseUrl}/dashboard/top-exams`, { credentials: "include" }),
            fetch(`${baseUrl}/dashboard/exams`, { credentials: "include" })
        ]);

        // Summary
        const summary = await summaryRes.json();
        if (summary.success) {
            totalStudents.textContent = summary.data.total_students;
            totalTeachers.textContent = summary.data.total_examiners;
        }

        // Top exams
        const top = await topRes.json();
        if (top.success) {
            const rows = top.data.map((exam) =>
                `<tr><td>${exam.name}</td><td>${exam.date}</td><td>${exam.total_students}</td></tr>`
            );
            topStudentsBody.innerHTML = rows.join("");
        }

        // Exams list
        const exams = await examsRes.json();
        if (exams.success) {
            const items = exams.data.map((exam) =>
                `<a href="../analytics/mss-detail.html?exam_id=${encodeURIComponent(exam.exam_id)}&token=${encodeURIComponent(exam.token)}">
                    <li class="completed">
                        <div class="task-title">
                            <i class='bx bx-book'></i>
                            <p>${exam.exam_name}<span></span></p>
                        </div>
                        <i class='bx bx-dots-vertical-rounded'></i>
                    </li>
                </a>`
            );
            examList.innerHTML = items.join("");
        }
    } catch (error) {
        console.error("Dashboard load failed", error);
    } finally {
        // Remove loading indicator
        if (loading && loading.parentNode) loading.parentNode.removeChild(loading);
    }
})();
