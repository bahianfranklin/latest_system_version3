// Set new default font family and font color to mimic Bootstrap's default styling
if (typeof Chart !== 'undefined' && Chart.defaults) {
    if (Chart.defaults.font) {
        Chart.defaults.font.family = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    } else if (Chart.defaults.global) {
        Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    }

    if (typeof Chart.defaults.color !== 'undefined') {
        Chart.defaults.color = '#292b2c';
    } else if (Chart.defaults.global) {
        Chart.defaults.global.defaultFontColor = '#292b2c';
    }
}

// Pie Chart Example
var ctx = document.getElementById("myPieChart");
if (ctx && typeof Chart !== 'undefined') {
var myPieChart = new Chart(ctx, {
  type: 'pie',
  data: {
    labels: ["Blue", "Red", "Yellow", "Green"],
    datasets: [{
      data: [12.21, 15.58, 11.25, 8.32],
      backgroundColor: ['#007bff', '#dc3545', '#ffc107', '#28a745'],
    }],
  },
});
}
