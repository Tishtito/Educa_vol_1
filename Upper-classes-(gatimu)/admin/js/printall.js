
document.getElementById('printAll').addEventListener('click', function() {
    let studentLinks = document.querySelectorAll('.view-report'); // Select all report links

    studentLinks.forEach((link, index) => {
        setTimeout(() => {
            let printWindow = window.open(link.href, '_blank');
            printWindow.onload = function() {
                printWindow.print(); 
                setTimeout(() => printWindow.close(), 1000); // Auto-close after printing
            };
        }, index * 3000); // Open each report with a delay to prevent overloading
    });
});
