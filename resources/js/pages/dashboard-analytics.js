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

// Your original chart options
var chartOptions = {
    chart: {
        height: 300,
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
    colors: ["#47ad94", "#604ae3", "#f0643b"],
    dataLabels: { enabled: false },
    stroke: {
        show: true,
        curve: "smooth",
        width: 2,
        lineCap: "square",
    },
    series: [
        { name: "New", data: [1, 2, 1, 0, 2] },
        { name: "Re-Opened", data: [0, 1, 0, 2, 1] },
        { name: "Closed", data: [2, 0, 1, 1, 2] },
    ],
    labels: ["Mon", "Tue", "Wed", "Thu", "Fri"],
    xaxis: {
        axisBorder: { show: false },
        axisTicks: { show: false },
        crosshairs: { show: true },
        labels: {
            offsetX: 0,
            offsetY: 5,
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
        padding: { top: -50, right: 0, bottom: 0, left: 5 },
    },
    legend: { show: true },
    fill: {
        type: "gradient",
        gradient: {
            type: "vertical",
            shadeIntensity: 1,
            inverseColors: false,
            opacityFrom: 0.12,
            opacityTo: 0.1,
            stops: [100, 100],
        },
    },
    responsive: [
        {
            breakpoint: 575,
            options: {
                legend: { offsetY: -50 },
            },
        },
    ],
};

// ✅ Dynamically calculate the max Y value from series
const allValues = chartOptions.series.flatMap((s) => s.data);
const maxValue = Math.max(...allValues);
const yTicks = maxValue < 1 ? 1 : maxValue; // prevent tickAmount = 0

// ✅ Update y-axis config dynamically
chartOptions.yaxis = {
    min: 0,
    max: yTicks,
    tickAmount: yTicks,
    labels: {
        formatter: (value) => value,
        offsetX: -15,
        style: {
            fontSize: "12px",
            cssClass: "apexcharts-yaxis-title",
        },
    },
};

// ✅ Initialize and render chart
var chart = new ApexCharts(
    document.querySelector("#sales_analytic"),
    chartOptions
);
chart.render();

function fetchSalesAnalytic(range = "year") {
    fetch(`/get-sales-analytic?range=${range}`)
        .then((res) => res.json())
        .then((data) => {
            // Update Apex chart
            chart.updateOptions({
                labels: data.labels,
            });

            chart.updateSeries([
                { name: "New", data: data.new_added },
                { name: "Re-Opened", data: data.reopened },
                { name: "Closed", data: data.closed },
            ]);

            // Update active dropdown item
            document.querySelectorAll(".chart-filter").forEach((el) => {
                el.classList.remove("active");
                if (el.getAttribute("data-range") === range) {
                    el.classList.add("active");
                }
            });

            // Optionally update dropdown button text
            const btn = document.querySelector(".dropdown-toggle");
            if (btn) {
                btn.textContent = range === "year" ? "This Year" : "This Month";
            }
        })
        .catch((err) => {
            console.error("Failed to fetch sales analytic data:", err);
        });
}

// Event binding
document.addEventListener("DOMContentLoaded", () => {
    fetchSalesAnalytic("year");

    document.querySelectorAll(".chart-filter").forEach((el) => {
        el.addEventListener("click", function () {
            const range = this.getAttribute("data-range");
            fetchSalesAnalytic(range);
        });
    });
});

// sales_funnel for weekly sales
// Global variable to access later
window.salesChart = null;

const salesChartOptions = {
    chart: {
        height: 120,
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
    const chartEl = document.querySelector("#sales_funnel");
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
