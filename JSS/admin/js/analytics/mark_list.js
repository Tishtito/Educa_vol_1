(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const examId = params.get("exam_id");
    const grade = params.get("grade");
    const token = params.get("token");

    // Cache DOM elements
    const marklistExam = document.getElementById("marklist-exam");
    const marklistGrade = document.getElementById("marklist-grade");
    const marklistTutor = document.getElementById("marklist-tutor");
    const headTop = document.getElementById("marklist-head-top");
    const headSub = document.getElementById("marklist-head-sub");
    const body = document.getElementById("marklist-body");
    const foot = document.getElementById("marklist-foot");

    // Loading indicator
    const loading = document.createElement("div");
    loading.id = "loading-indicator";
    loading.style = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;font-size:2em;";
    loading.textContent = "Loading...";
    document.body.appendChild(loading);

    try {
        // Parallel fetch for auth and mark list
        const [authRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        if (!examId || !grade || !token) {
            console.error("Missing exam_id or grade");
            return;
        }

        // Show loading while fetching mark list
        const res = await fetch(`${baseUrl}/marks/list?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade)}&token=${encodeURIComponent(token)}`, {
            credentials: "include",
        });
        const payload = await res.json();
        if (!payload.success) {
            console.error(payload.message || "Failed to load mark list");
            return;
        }

        const data = payload.data;
        marklistExam.textContent = `Mark List - ${data.exam_name}`;
        marklistGrade.textContent = `Grade: ${data.grade_title}`;
        marklistTutor.textContent = `Tutor: ${data.tutor}`;

        // Batch build table headers
        let headTopHtml = "<th rowspan=\"2\">Rank</th><th rowspan=\"2\">Name</th>";
        let headSubHtml = "";
        data.subjects.forEach((subject) => {
            headTopHtml += `<th colspan=\"2\">${subject}</th>`;
            headSubHtml += "<th>Marks</th><th>PL</th>";
        });
        headTopHtml += "<th rowspan=\"2\">Total Marks</th>";
        headTop.innerHTML = headTopHtml;
        headSub.innerHTML = headSubHtml;

        // Batch build table body
        const bodyRows = data.students.map((student) => {
            let row = `<tr><td>${student.rank}</td><td>${student.Name}</td>`;
            data.subjects.forEach((subject) => {
                const mark = student[subject] ?? "-";
                const pl = student[`PL_${subject}`] ?? "-";
                row += `<td>${mark}</td><td>${pl}</td>`;
            });
            row += `<td>${student.total_marks ?? 0}</td></tr>`;
            return row;
        });
        body.innerHTML = bodyRows.join("");

        // Batch build table foot
        const meanRow = ["<tr><th colspan=\"2\">Mean Scores</th>"];
        data.subjects.forEach((subject) => {
            meanRow.push(`<td colspan=\"2\">${data.mean_scores[subject] ?? 0}</td>`);
        });
        meanRow.push(`<td colspan=\"2\">${data.total_mean}</td></tr>`);

        const prevRow = ["<tr><th colspan=\"2\">Previous Mean Scores</th>"];
        data.subjects.forEach((subject) => {
            prevRow.push(`<td colspan=\"2\">${data.prev_mean_scores[subject] ?? "-"}</td>`);
        });
        prevRow.push(`<td colspan=\"2\">${data.prev_total_mean}</td></tr>`);

        const devRow = ["<tr><th colspan=\"2\">Deviation</th>"];
        data.subjects.forEach((subject) => {
            const dev = data.deviation_scores[subject] ?? "-";
            let color = "black";
            if (typeof dev === "number" || (typeof dev === "string" && dev !== "-")) {
                const val = Number(dev);
                if (val > 0) color = "green";
                if (val < 0) color = "red";
            }
            const display = dev !== "-" ? (Number(dev) > 0 ? `+${dev}` : dev) : "-";
            devRow.push(`<td colspan=\"2\" style=\"color: ${color}\">${display}</td>`);
        });

        let totalColor = "black";
        if (typeof data.total_mean_deviation === "number" || (typeof data.total_mean_deviation === "string" && data.total_mean_deviation !== "-")) {
            const totalVal = Number(data.total_mean_deviation);
            if (totalVal > 0) totalColor = "green";
            if (totalVal < 0) totalColor = "red";
        }
        const totalDisplay = data.total_mean_deviation !== "-" ? (Number(data.total_mean_deviation) > 0 ? `+${data.total_mean_deviation}` : data.total_mean_deviation) : "-";
        devRow.push(`<td colspan=\"2\" style=\"color: ${totalColor}\">${totalDisplay}</td></tr>`);

        foot.innerHTML = meanRow.join("") + prevRow.join("") + devRow.join("");
    } catch (error) {
        console.error("Mark list load failed", error);
    } finally {
        // Remove loading indicator
        if (loading && loading.parentNode) loading.parentNode.removeChild(loading);
    }
})();
