<div x-data="{ CategoriesExpanded: $persist(1) }" class="shadow-lg mx-2 my-2 h-100 rounded-md border border-gray-300 bg-white mt-2 px-4 pt-2" :class="CategoriesExpanded ? 'pb-4' : 'pb-1'">
    <div @click="CategoriesExpanded = ! CategoriesExpanded"  class="text-lg text-gray-800 font-bold" :class="CategoriesExpanded ? 'mb-4' : 'mb-1'">Categories</div>
    <div x-show="CategoriesExpanded" x-collapse class="w-ful grid grid-cols-3 gap-0">
        @php
        foreach( $stats['categories'] as $category => $value) {
            $value['amount'] = abs($value['amount']);
            if($value['amount'] > 0){
                $amountColor = 'text-green-500';
            }elseif($value['amount'] < 0){
                $amountColor = 'text-red-500';
            }else{
                $amountColor = 'text-gray-500';
            }

        @endphp

        <div class="col-span-2 mt-2 text-sm">{{$category}}</div>
        <div class="text-red-500 pt-2 text-end text-red-500">{{ Str::replace('.00','',number_format($value['amount'],2)) }}</div>
        <div class="col-span-3 mt-2"><hr></div>
        @php
            }
        @endphp

    </div>
</div>
