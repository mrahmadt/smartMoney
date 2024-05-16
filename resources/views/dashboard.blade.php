<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class=" font-bold  text-xl">
                        <div class="px-4 pt-8 text-xl text-indigo-500 hover:text-indigo-200"><a href="/budgets">Budget</a></div>
                        <div class="px-4 pt-8 text-xl text-indigo-500 hover:text-indigo-200"><a href="/transactions">Transactions</a></div>
                        <div class="px-4 pt-8 text-xl text-indigo-500 hover:text-indigo-200"><a href="/alerts">Alerts</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
