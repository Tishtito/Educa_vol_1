(async function () {
    const baseUrl = "../../backend/public/index.php";
    const params = new URLSearchParams(window.location.search);
    const studentId = params.get('student_id') || '';
    const examId = params.get('exam_id') || '';
    const token = params.get('token') || '';

    function getPerformanceLevel(score, levels) {
        if (score === null || score === undefined) {
            return { pl: '-', ab: '-' };
        }
        for (const level of levels) {
            if (score >= level.min_marks && score <= level.max_marks) {
                return { pl: level.pl, ab: level.ab };
            }
        }
        return { pl: 'UNKNOWN', ab: 'UNKNOWN' };
    }

    function commentForPercentage(percentage) {
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

    function formatPrintDate(date) {
        const day = date.getDate();
        const suffix = (d) => {
            if (d > 3 && d < 21) return 'th';
            switch (d % 10) {
                case 1: return 'st';
                case 2: return 'nd';
                case 3: return 'rd';
                default: return 'th';
            }
        };
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        return `${day}${suffix(day)} ${months[date.getMonth()]} ${date.getFullYear()}`;
    }

    if (!studentId || !examId || !token) {
        return;
    }

    // Loading indicator
    const loading = document.createElement("div");
    loading.id = "loading-indicator";
    loading.style = "position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(255,255,255,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;font-size:2em;";
    loading.textContent = "Loading...";
    document.body.appendChild(loading);

    try {
        const [authRes] = await Promise.all([
            fetch(`${baseUrl}/auth/check`, { credentials: "include" })
        ]);
        const auth = await authRes.json();
        if (!auth.authenticated) {
            window.location.replace("../login.html");
            return;
        }

        const dataRes = await fetch(`${baseUrl}/reports/report-combined?student_id=${encodeURIComponent(studentId)}&exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(token)}`, { credentials: "include" });
        const payload = await dataRes.json();
        if (!payload.success) {
            return;
        }

        const { student, tutor, term, exam_year, mid_results, end_results, levels, subjects } = payload.data;

        document.getElementById('term-label').textContent = term || '-';
        document.getElementById('student-name').textContent = student.name || '-';
        document.getElementById('student-class').textContent = student.class || '-';
        document.getElementById('exam-year').textContent = exam_year ? ` ${exam_year}` : '';
        document.getElementById('tutor-name').textContent = tutor || 'Not Assigned';
        document.getElementById('print-date').textContent = formatPrintDate(new Date());

        const resultsBody = document.getElementById('results-body');
        resultsBody.innerHTML = '';

        let totalMarksMid = 0;
        let totalMarksEnd = 0;
        let totalMarksAvg = 0;
        const subjectKeys = Object.keys(subjects || {});

        const bodyRows = subjectKeys.map((key) => {
            const subjectName = subjects[key];
            const midScore = mid_results ? mid_results[key] : null;
            const endScore = end_results ? end_results[key] : null;

            const midPerf = getPerformanceLevel(midScore, levels);
            const endPerf = getPerformanceLevel(endScore, levels);

            const midVal = midScore ?? 0;
            const endVal = endScore ?? 0;
            const avgScore = (midScore !== null || endScore !== null) ? (Number(midVal) + Number(endVal)) / 2 : null;
            const avgPerf = getPerformanceLevel(avgScore, levels);

            if (midScore !== null && midScore !== undefined) totalMarksMid += Number(midScore);
            if (endScore !== null && endScore !== undefined) totalMarksEnd += Number(endScore);
            if (avgScore !== null && avgScore !== undefined) totalMarksAvg += Number(avgScore);

            return `<tr>
                <td>${subjectName}</td>
                <td>${midScore ?? '-'}</td>
                <td>${midPerf.ab}</td>
                <td>${endScore ?? '-'}</td>
                <td>${endPerf.ab}</td>
                <td>${avgScore !== null ? avgScore.toFixed(1) + '%' : '-'}</td>
                <td>${avgPerf.ab}</td>
                <td>${avgPerf.pl}</td>
            </tr>`;
        });
        resultsBody.innerHTML = bodyRows.join("");

        const totalOutOf = subjectKeys.length * 100;
        const percentage = totalOutOf > 0 ? (totalMarksAvg / totalOutOf) * 100 : 0;
        const formattedPercentage = percentage.toFixed(2);
        const avgPerformance = getPerformanceLevel(percentage, levels);
        const comments = commentForPercentage(percentage);

        document.getElementById('total-mid').textContent = totalMarksMid;
        document.getElementById('total-end').textContent = totalMarksEnd;
        document.getElementById('total-out-of').textContent = totalOutOf;
        document.getElementById('total-out-of-2').textContent = totalOutOf;
        document.getElementById('average-marks').textContent = formattedPercentage;
        document.getElementById('average-pl').textContent = avgPerformance.pl;
        document.getElementById('teacher-comment').textContent = comments.teacher;
        document.getElementById('principal-comment').textContent = comments.principal;
    } catch (error) {
        console.error('Failed to load report', error);
    } finally {
        // Remove loading indicator
        if (loading && loading.parentNode) loading.parentNode.removeChild(loading);
    }
})();
