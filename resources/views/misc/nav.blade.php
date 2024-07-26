<nav class="bg-white shadow rounded-lg mb-5 sticky top-[100vh]">
<div class="cursor-pointer bg-gray-50 pb-2 mb-2 hidden" id="webpush-button">
    <div class="bg-indigo-600 text-white text-sm rounded-xl text-center mx-2 px-2 py-2 shadow-2xl">Subscribe Push Notification</div>
</div>
@if (Request::is('alerts*'))
<div class="cursor-pointer bg-gray-50 pb-2 mb-2 hidden" id="disable-webpush">
    <div class="bg-indigo-600 text-white text-sm rounded-xl text-center mx-2 px-2 py-2 shadow-2xl">Unsubscribe Push Notification</div>
</div>
@endif
    <div class="order-3 w-full md:w-auto md:order-2">
        <div class="grid grid-cols-3 text-sm text-center divide-x">
            @if (Request::is('budgets*'))
                <div class="px-4 py-4 text-white bg-indigo-800 font-bold rounded"><a href="/budgets">Budget</a></div>
            @else
            <a href="/budgets"><div class="px-4 py-4 text-white bg-indigo-600">Budget</div></a>
            @endif
            @if (Request::is('transactions*'))
                <div class="px-4 py-4 text-white bg-indigo-800 font-bold rounded"><a href="/transactions">Transactions</a></div>
            @else
                <a href="/transactions"><div class="px-4 py-4 text-white bg-indigo-600">Transactions</div></a>
            @endif
            @if (Request::is('alerts*'))
                <div class="px-4 py-4 text-white bg-indigo-800 font-bold rounded"><a href="/alerts">Alerts</a></div>

            @else
            <a href="/alerts"><div class="px-4 py-4 text-white bg-indigo-600">Alerts</div></a>
            @endif
        </div>
    </div>
</nav>

