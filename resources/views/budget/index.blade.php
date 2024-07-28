@extends('layouts.app')
@section('title', 'Budgets')
@section('content')
@php

foreach($budgets as $index => $budget){
if(isset($budget->attributes->spent[0])) {
    $remaining = number_format($budget->attributes->auto_budget_amount+$budget->attributes->spent[0]->sum,0);
    $budget_percentage_used = number_format(abs(($budget->attributes->spent[0]->sum/$budget->attributes->auto_budget_amount)*100),0);
    $spentSum = number_format($budget->attributes->spent[0]->sum,0);

}else{
    $remaining = number_format($budget->attributes->auto_budget_amount,0);
    $budget_percentage_used = 0;
    $spentSum = 0;
}
$remainingPercentage = 100 - $budget_percentage_used;

if($remaining < 0){
    $remainingColor = 'text-red-500';
}elseif($budget_percentage_used >= 80){
    $remainingColor = 'text-red-600';
}elseif($budget_percentage_used >= 60){
    $remainingColor = 'text-orange-400';
}else{
    $remainingColor = 'text-black';
}
@endphp
<a href="/budgets/{{$budget->id}}">
  <div class="pt-2">
<div class="shadow-lg mx-2 rounded-md border border-gray-300 bg-white px-4 py-2">
    <div class="grid grid-flow-row-dense grid-cols-2 grid-rows-1">
      <div>
        <div class="text-sm font-light text-gray-400 whitespace-nowrap">{{$budget->attributes->name}}</div>
        <div class="text-4xl font-bold {{$remainingColor}} tabular-nums">{{$remaining}}</div>
        <div class="text-sm text-gray-500">{{number_format($budget->attributes->auto_budget_amount,0)}} - {{__('cards.Spent')}} <span class="text-red-500">{{$spentSum}}</span></div>
      </div>
      <div>
        <div style="width: 100px; height: 80px; float: right; position: relative;">
            <div class="proportional-nums" style="width: 85%; height: 40px; position: absolute; top: 69%; left: 0; margin-top: -20px; line-height:19px; text-align: center; z-index: 999999999999999">{{$budget_percentage_used}}%</div><canvas id="budgetSpendingChart{{$index}}" width="100" height="100"></canvas>
        </div>
    </div>
    </div>
</div></div>
</a>

<script>
    var budgetSpendingChart_config{{$index}} = {
      type: 'doughnut',
      data: {
        datasets: [{
            data: [{{$budget_percentage_used}}, {{$remainingPercentage}}],
          backgroundColor: [
          'rgba(255, 99, 132 , 1)',
          'rgba(255, 99, 132, 0.2)',
          ],
          hoverBackgroundColor: [
            "#6f61ef",
            "#bab8cf",
          ]
        }]
      },
      options: {
        cutout: '70%',
        responsive: true,
      }
    };
    
    var budgetSpendingChart_ctx{{$index}} = document.getElementById("budgetSpendingChart{{$index}}").getContext("2d");
    var budgetSpendingChart{{$index}} = new Chart(budgetSpendingChart_ctx{{$index}}, budgetSpendingChart_config{{$index}});
</script>
@php
}
@endphp
@endsection
