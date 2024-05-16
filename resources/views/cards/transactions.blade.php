<div class="pt-2"><div x-data="{ TransactionsExpanded: $persist(1) }" class="shadow-lg mx-2 h-100 rounded-md border border-gray-300 bg-white px-4 pt-2 pb-4" :class="TransactionsExpanded ? 'pb-4' : 'pb-1'">
    <div @click="TransactionsExpanded = ! TransactionsExpanded"  class="text-lg text-gray-800 font-bold" :class="TransactionsExpanded ? 'mb-4' : 'mb-1'">Last 50 transactions</div>
    <div x-show="TransactionsExpanded" x-collapse>
        @php
        $counter = 0;
        foreach( $transactions as $transaction) {
            if(isset($transaction->attributes->transactions[0])){
                $transaction = $transaction->attributes->transactions[0];
            }
            $counter++;
            if($counter > 50){
                break;
            }
            if($transaction->type == 'deposit'){
                $amountColor = 'text-green-500';
                $shop = $transaction->source_name;
            }elseif($transaction->type == 'withdrawal'){
                $amountColor = 'text-red-500';
                $shop = $transaction->destination_name;
            }else{
                // dd($transaction);
                $amountColor = 'text-gray-500';
                $shop = $transaction->source_name ?? 'Unknown';
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
</div></div>