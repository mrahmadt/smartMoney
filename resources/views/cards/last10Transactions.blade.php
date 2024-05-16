<div x-data="{ LastTransactionsExpanded: $persist(1) }" class="shadow-lg mx-2 my-2 h-100 rounded-md border border-gray-300 bg-white mt-2 px-4 pt-2 pb-4" :class="LastTransactionsExpanded ? 'pb-4' : 'pb-1'">
    <div @click="LastTransactionsExpanded = ! LastTransactionsExpanded"  class="text-lg text-gray-800 font-bold" :class="LastTransactionsExpanded ? 'mb-4' : 'mb-1'">Last 10 transactions</div>
    <div x-show="LastTransactionsExpanded" x-collapse>
        @php
        $counter = 0;
        foreach( $transactions as $transaction) {
            $counter++;
            if($counter > 10){
                break;
            }
            if($transaction->type == 'deposit'){
                $amountColor = 'text-green-500';
                $shop = $transaction->source_name;
            }elseif($transaction->type == 'withdrawal'){
                $amountColor = 'text-red-500';
                $shop = $transaction->destination_name;
            }else{
                $amountColor = 'text-gray-500';
                $shop = $transaction->source_name;
            }
        @endphp
<div class="grid grid-cols-3 gap-0" @click="$dispatch('modal', {transaction_id: {{$transaction->transaction_journal_id}}, isOpen: true})">
<div class="col-span-2 pt-2">
    <div class="inline-block" ><div class="text-sm">{{$shop}}</div><div class="text-xs text-gray-400">{{$transaction->category_name}}</div>
</div> 
</div>
<div><div class="{{$amountColor}} pt-1 text-end">{{ Str::replace('.00','',number_format($transaction->amount,2)) }} {{$transaction->currency_symbol}}</div><div class="text-xs text-gray-400 text-end"> {{ \Carbon\Carbon::parse($transaction->date)->diffForhumans() }}</div></div>
<div class="col-span-3 mt-2"><hr></div>
</div>
        @php
            }
        @endphp
    </div>
</div>