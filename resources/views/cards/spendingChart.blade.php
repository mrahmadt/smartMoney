<div x-data="{ spendingExpanded: $persist(1) }" class="shadow-lg mx-2 my-2 h-100 rounded-md border border-gray-300 bg-white mt-2 px-4 py-2">
    <div @click="spendingExpanded = ! spendingExpanded" class="text-lg text-gray-800 font-bold">Spending</div>
    <div x-show="spendingExpanded" x-collapse><canvas  style="width: 100%;" id="spendingChart"></canvas></div>
  </div>
  <script>
    const spendingChart_data = {
      labels: ['{!!implode("','", $spendingLabels)!!}'],
      datasets: [{
        data: [{{implode(',', $spending)}}],
        backgroundColor: [
          'rgba(255, 99, 132, 0.2)',
        ],
        borderColor: [
          'rgb(255, 99, 132)',
        ],
        borderWidth: 1
      }]
    };
    const spendingChart_config = {
      type: 'line',
      data: spendingChart_data,
      options: {
        responsive: true,
        fill: true,
        plugins: {
          legend: {
            display: false,
          },
          title: {
            display: false,
          }
        }
      },
    };
    
    var spendingChart_ctx = document.getElementById("spendingChart").getContext("2d");
    var spendingChart_myChart = new Chart(spendingChart_ctx, spendingChart_config);
    
</script>
    