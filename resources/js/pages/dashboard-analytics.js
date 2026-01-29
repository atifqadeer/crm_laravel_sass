/**
 * Theme: Lahomes - Real Estate Admin Dashboard Template
 * Author: Techzaa
 * Module/App: Dashboard
 */

import ApexCharts from "apexcharts/dist/apexcharts";

window.ApexCharts = ApexCharts;

import jsVectorMap from "jsvectormap";
import "jsvectormap/dist/maps/world-merc.js";
import "jsvectormap/dist/maps/world.js";

// Chart options
var chartOptions = {
    chart: {
        height: "100%",
        type: "area",
        dropShadow: {
            enabled: true,
            opacity: 0.2,
            blur: 10,
            left: -7,
            top: 22,
        },
        toolbar: { show: false },
    },
    colors: ["#47ad94", "#604ae3", "#f0643b", "#ffc107", "#dc3545"], 
    // New, Reopened, Closed, Pending, Rejected (red)
    dataLabels: { enabled: false },
    stroke: {
        show: true,
        curve: "smooth",
        width: 2,
        lineCap: "square",
    },
    series: [
        { name: "New Sales", data: [] },
        { name: "Re-Opened Sales", data: [] },
        { name: "Closed Sales", data: [] },
        { name: "Pending Sales", data: [] },
        { name: "Rejected Sales", data: [] },
    ],
    labels: [],
    xaxis: {
        axisBorder: { show: false },
        axisTicks: { show: false },
        crosshairs: { show: true },
        labels: {
            rotate: -50,        // rotate labels vertically
            rotateAlways: true, // force rotation even if space is available
            offsetY: 0,
            style: {
                fontSize: "12px",
                cssClass: "apexcharts-xaxis-title",
            },
        },
    },
    grid: {
        borderColor: "#191e3a",
        strokeDashArray: 5,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } },
        padding: { top: 0, right: 10, bottom: 0, left: 5 },
    },
    legend: { show: true, position: "top", horizontalAlign: "right" },
    fill: {
        type: "gradient",
        gradient: {
            type: "vertical",
            shadeIntensity: 1,
            inverseColors: false,
            opacityFrom: 0.25,
            opacityTo: 0.05,
            stops: [0, 100],
        },
    },
    yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
            offsetX: -10,
            style: {
                fontSize: "12px",
                cssClass: "apexcharts-yaxis-title",
            },
        },
    },
    responsive: [
        {
            breakpoint: 768,
            options: {
                chart: { height: 300 },
                legend: { position: "bottom" },
            },
        },
    ],
};

// Render chart
var chart = new ApexCharts(document.querySelector("#sales_analytic"), chartOptions);
chart.render();

// Fetch and update chart data
function fetchSalesAnalytic(range = "month") {
    fetch(`/get-sales-analytic?range=${range}`)
        .then((res) => res.json())
        .then((data) => {
            // Update chart with new data
            chart.updateOptions({
                labels: data.labels,
                series: [
                    { name: "New Sales", data: data.new_added },
                    { name: "Re-Opened Sales", data: data.reopened },
                    { name: "Closed Sales", data: data.closed },
                    { name: "Pending Sales", data: data.pending },
                    { name: "Rejected Sales", data: data.rejected }, // 👈 added rejected
                ]
            });

            // Update active dropdown item
            document.querySelectorAll(".chart-sale-filter").forEach((el) => {
                el.classList.remove("active");
                if (el.getAttribute("data-sale-range") === range) {
                    el.classList.add("active");
                }
            });

            // Update dropdown button text
            const btn = document.querySelector(".dropdown-sale-toggle");
            if (btn) {
                btn.textContent = range === "year" ? "This Year" : "This Month";
            }
        })
        .catch((err) => {
            console.error("Failed to fetch sales analytic data:", err);
        });
}

// Dropdown filter click handler
document.querySelectorAll(".chart-sale-filter").forEach((el) => {
    el.addEventListener("click", function (e) {
        e.preventDefault();
        const range = this.getAttribute("data-sale-range");
        fetchSalesAnalytic(range);
    });
});

// Event binding
document.addEventListener("DOMContentLoaded", () => {
    fetchSalesAnalytic("month");

    document.querySelectorAll(".chart-sale-filter").forEach((el) => {
        el.addEventListener("click", function () {
            const range = this.getAttribute("data-sale-range");
            fetchSalesAnalytic(range);
        });
    });
});

// Global variable to access later
window.salesChart = null;

const salesChartOptions = {
    chart: {
        height: "100%",
        parentHeightOffset: 0,
        type: "bar",
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            barHeight: "100%",
            columnWidth: "40%",
            startingShape: "rounded",
            endingShape: "rounded",
            borderRadius: 4,
            distributed: true,
        },
    },
    grid: {
        show: true,
        padding: {
            top: -20,
            bottom: -10,
            left: 0,
            right: 0,
        },
    },
    colors: [
        "#604ae3",
        "#604ae3",
        "#604ae3",
        "#604ae3",
        "#604ae3",
        "#604ae3",
        "#604ae3",
    ],
    dataLabels: { enabled: false },
    series: [
        {
            name: "Sales",
            data: [0, 0, 0, 0, 0, 0, 0],
        },
    ],
    xaxis: {
        categories: ["S", "M", "T", "W", "T", "F", "S"],
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { show: true },
    },
    tooltip: { enabled: true },
    legend: { show: false },
    responsive: [
        {
            breakpoint: 1025,
            options: { chart: { height: 199 } },
        },
    ],
};

document.addEventListener("DOMContentLoaded", function () {
    const chartEl = document.querySelector("#sales_chart");
    if (chartEl) {
        window.salesChart = new ApexCharts(chartEl, salesChartOptions);
        window.salesChart.render();
    }
});

// Function to update chart from anywhere
window.updateSalesChart = function (chartData) {
    if (window.salesChart) {
        window.salesChart.updateSeries([
            {
                name: "Sales",
                data: chartData,
            },
        ]);
    }
};
