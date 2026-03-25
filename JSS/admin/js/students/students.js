// students.js - optimized per frontend guidelines
(async function () {
    const baseUrl = "../../backend/public/index.php";
    const activeCount = document.getElementById('active-count');
    const finishedCount = document.getElementById('finished-count');

    try {
        const [authRes, summaryRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" }),
            fetch(`${baseUrl}/students/summary`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }
        const summary = await summaryRes.json();
        if (summary.success) {
            if (activeCount) activeCount.textContent = summary.data.active ?? 0;
            if (finishedCount) finishedCount.textContent = summary.data.finished ?? 0;
        }
    } catch (error) {
        // Optionally log error
    }
})();
