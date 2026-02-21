<div x-data="{ CategoriesExpandedChart: $persist(1) }" class="shadow-lg mx-2 my-2 h-100 rounded-md border border-gray-300 bg-white mt-2 px-4 py-2">
    <div @click="CategoriesExpandedChart = ! CategoriesExpandedChart"  class="text-lg text-gray-800 font-bold">{{__('cards.Categories')}}</div>
    <div x-show="CategoriesExpandedChart" x-collapse><canvas id="categoriesChart"></canvas></div>
</div>
<script>
    var categoriesChart_config = {
      type: 'pie',
      data: {
        labels: ['{!!implode("','", array_keys($categoriesChart))!!}'],
        datasets: [{
          data: ['{!!implode("','", array_values($categoriesChart))!!}'],
        }]
      },
      options: {
        responsive: true,
        
        plugins: {
          legend: {
            position: 'bottom',
        }
    
      }
    }
  };
     
var categoriesChart_ctx = document.getElementById("categoriesChart").getContext("2d");
var categoriesChart = new Chart(categoriesChart_ctx, categoriesChart_config);
</script>