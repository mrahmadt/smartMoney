@if($range)
<div class="shadow-lg mx-2 my-2 rounded-md border border-gray-300 bg-white px-4 py-2">
    <div class="grid grid-flow-row-dense grid-cols-3 grid-rows-1">
      <div>
        <div class="text-sm font-light text-gray-400 text-left"><</div>
      </div>
      <div>
        <div class="text-sm font-light text-gray-800 text-center whitespace-nowrap">{{$range}}</div>
      </div>
      <div>
        <div class="text-sm font-light text-gray-400 text-right">></div>
      </div>
    </div>
</div>
@endif