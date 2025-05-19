/**
 * Charts.js for Municipal PNP System
 * Contains chart initialization code for reporting dashboards
 * Uses Chart.js library
 */

document.addEventListener('DOMContentLoaded', function() {
    // Load Chart.js from CDN if not already loaded
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = initializeCharts;
        document.head.appendChild(script);
    } else {
        initializeCharts();
    }
});

/**
 * Initialize all charts on the page
 */
function initializeCharts() {
    // Set Chart.js defaults
    Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.color = '#666';
    
    // Initialize ticket status chart if container exists
    const ticketStatusChart = document.getElementById('ticket-status-chart');
    if (ticketStatusChart) {
        initTicketStatusChart(ticketStatusChart);
    }
    
    // Initialize monthly tickets chart if container exists
    const monthlyTicketsChart = document.getElementById('monthly-tickets-chart');
    if (monthlyTicketsChart) {
        initMonthlyTicketsChart(monthlyTicketsChart);
    }
    
    // Initialize payment chart if container exists
    const paymentChart = document.getElementById('payment-chart');
    if (paymentChart) {
        initPaymentChart(paymentChart);
    }
    
    // Initialize department workload chart if container exists
    const departmentWorkloadChart = document.getElementById('department-workload-chart');
    if (departmentWorkloadChart) {
        initDepartmentWorkloadChart(departmentWorkloadChart);
    }
}

/**
 * Initialize ticket status distribution chart
 * @param {HTMLCanvasElement} container - Chart container
 */
function initTicketStatusChart(container) {
    // Fetch data from server or use predefined data
    fetchChartData('ticket-status-data.php')
        .then(data => {
            if (!data) {
                // Use sample data if no server data is available
                data = {
                    labels: ['New', 'In Progress', 'Resolved', 'Closed'],
                    datasets: [{
                        data: [12, 19, 8, 15],
                        backgroundColor: [
                            '#36a2eb',
                            '#ffcd56',
                            '#4bc0c0',
                            '#ff6384'
                        ]
                    }]
                };
            }
            
            new Chart(container, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        title: {
                            display: true,
                            text: 'Ticket Status Distribution'
                        }
                    },
                    cutout: '70%'
                }
            });
        });
}

/**
 * Initialize monthly tickets chart
 * @param {HTMLCanvasElement} container - Chart container
 */
function initMonthlyTicketsChart(container) {
    // Fetch data from server or use predefined data
    fetchChartData('monthly-tickets-data.php')
        .then(data => {
            if (!data) {
                // Use sample data if no server data is available
                const months = ['January', 'February', 'March', 'April', 'May', 'June'];
                data = {
                    labels: months,
                    datasets: [
                        {
                            label: 'Tickets Created',
                            data: [65, 59, 80, 81, 56, 55],
                            fill: false,
                            borderColor: '#36a2eb',
                            tension: 0.1
                        },
                        {
                            label: 'Tickets Resolved',
                            data: [28, 48, 40, 19, 86, 27],
                            fill: false,
                            borderColor: '#4bc0c0',
                            tension: 0.1
                        }
                    ]
                };
            }
            
            new Chart(container, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Ticket Activity'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Tickets'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        });
}

/**
 * Initialize payment chart
 * @param {HTMLCanvasElement} container - Chart container
 */
function initPaymentChart(container) {
    // Fetch data from server or use predefined data
    fetchChartData('payment-data.php')
        .then(data => {
            if (!data) {
                // Use sample data if no server data is available
                const months = ['January', 'February', 'March', 'April', 'May', 'June'];
                data = {
                    labels: months,
                    datasets: [{
                        type: 'bar',
                        label: 'Total Payments',
                        data: [12500, 19000, 3000, 5000, 2000, 3000],
                        backgroundColor: '#36a2eb'
                    }, {
                        type: 'line',
                        label: 'Payment Trend',
                        data: [12500, 15750, 12000, 10000, 8000, 5500],
                        borderColor: '#ff6384',
                        fill: false
                    }]
                };
            }
            
            new Chart(container, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Payment History'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (PHP)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        });
}

/**
 * Initialize department workload chart
 * @param {HTMLCanvasElement} container - Chart container
 */
function initDepartmentWorkloadChart(container) {
    // Fetch data from server or use predefined data
    fetchChartData('department-workload-data.php')
        .then(data => {
            if (!data) {
                // Use sample data if no server data is available
                data = {
                    labels: ['Treasury', 'Engineering', 'Admin', 'Planning', 'Health'],
                    datasets: [{
                        label: 'Active Tickets',
                        data: [42, 29, 34, 17, 22],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 206, 86)',
                            'rgb(75, 192, 192)',
                            'rgb(153, 102, 255)'
                        ],
                        borderWidth: 1
                    }]
                };
            }
            
            new Chart(container, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        title: {
                            display: true,
                            text: 'Department Workload'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Active Tickets'
                            }
                        }
                    }
                }
            });
        });
}

/**
 * Fetch chart data from server
 * @param {string} endpoint - API endpoint to fetch data
 * @returns {Promise<object|null>} - Chart data or null if fetch failed
 */
function fetchChartData(endpoint) {
    return fetch(endpoint)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            return null;
        });
} 