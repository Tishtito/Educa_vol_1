(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const examId = params.get("exam_id");
    const grade = params.get("grade");
    const token = params.get("token");

    // Cache DOM elements
    const streamlistExam = document.getElementById("streamlist-exam");
    const streamlistGrade = document.getElementById("streamlist-grade");
    const headTop = document.getElementById("streamlist-head-top");
    const headSub = document.getElementById("streamlist-head-sub");
    const body = document.getElementById("streamlist-body");
    const foot = document.getElementById("streamlist-foot");

    // Loading indicator
    const loading = document.createElement("div");
    loading.id = "loading-indicator";
    loading.style = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;font-size:2em;";
    loading.textContent = "Loading...";
    document.body.appendChild(loading);

    try {
        // Parallel fetch for auth
        const [authRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../../Pages/login.html");
            return;
        }

        if (!examId || !grade || !token) {
            console.error("Missing exam_id or grade");
            return;
        }

        // Show loading while fetching stream list
        const res = await fetch(`${baseUrl}/streams/list?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade)}&token=${encodeURIComponent(token)}`, {
            credentials: "include",
        });
        const payload = await res.json();
        if (!payload.success) {
            console.error(payload.message || "Failed to load stream list");
            return;
        }

        const data = payload.data;
        streamlistExam.textContent = `Mark List - ${data.exam_name}`;
        streamlistGrade.textContent = `Grade: ${data.grade}`;

        // Batch build table headers
        let headTopHtml = "<th rowspan=\"2\">Rank</th><th rowspan=\"2\">Name</th><th rowspan=\"2\">Class</th>";
        let headSubHtml = "";
        data.subjects.forEach((subject) => {
            headTopHtml += `<th colspan=\"2\">${subject}</th>`;
            headSubHtml += "<th>Marks</th><th>PL</th>";
        });
        headTopHtml += "<th rowspan=\"2\">Total Marks</th>";
        headTop.innerHTML = headTopHtml;
        headSub.innerHTML = headSubHtml;

        // PL calculation
        const getPL = (score) => {
            const numeric = Number(score);
            if (Number.isNaN(numeric)) return "-";
            const match = data.performance_levels.find(pl => numeric >= pl.min_marks && numeric <= pl.max_marks);
            return match ? match.ab : "-";
        };

        // Batch build table body
        const bodyRows = data.students.map((student) => {
            let row = `<tr><td>${student.rank}</td><td>${student.Name}</td><td>${student.Class}</td>`;
            data.subjects.forEach((subject) => {
                const mark = student[subject] ?? "-";
                row += `<td>${mark}</td><td>${getPL(mark)}</td>`;
            });
            row += `<td>${student.Total_marks ?? 0}</td></tr>`;
            return row;
        });
        body.innerHTML = bodyRows.join("");

        // Batch build table foot
        const meanRow = ["<tr><th colspan=\"3\">Mean Scores</th>"];
        data.subjects.forEach((subject) => {
            meanRow.push(`<td colspan=\"2\">${data.mean_scores[subject] ?? 0}</td>`);
        });
        meanRow.push(`<td>${data.total_mean ?? 0}</td></tr>`);
        foot.innerHTML = meanRow.join("");
    } catch (error) {
        console.error("Stream list load failed", error);
    } finally {
        // Remove loading indicator
        if (loading && loading.parentNode) loading.parentNode.removeChild(loading);
    }
})();
