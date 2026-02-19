(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const examId = params.get("exam_id");
    const token = params.get("token");

    // Cache DOM element
    const list = document.getElementById("stream-grades");

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
            window.location.replace("../login.html");
            return;
        }

        if (!examId || !token) {
            console.error("Exam ID not provided");
            return;
        }

        // Show loading while fetching grades
        const gradesRes = await fetch(`${baseUrl}/analysis/grades?exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`, { credentials: "include" });
        const grades = await gradesRes.json();
        if (grades.success) {
            // Batch build grade links
            const items = grades.data.map((grade) =>
                `<a href="streamlist.html?exam_id=${encodeURIComponent(examId)}&grade=${encodeURIComponent(grade.grade)}&token=${encodeURIComponent(grade.token)}">
                    <li>
                        <i class='bx bx-show-alt'></i>
                        <span class="info-2">
                            <p>Grade - ${grade.grade}</p>
                        </span>
                    </li>
                </a>`
            );
            list.innerHTML = items.join("");
        }
    } catch (error) {
        console.error("Stream details load failed", error);
    } finally {
        // Remove loading indicator
        if (loading && loading.parentNode) loading.parentNode.removeChild(loading);
    }
})();
