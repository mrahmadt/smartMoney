<div x-show="modelOpen" x-data="showTransaction()" x-on:modal.window="transaction_id = $event.detail.transaction_id; modelOpen = $event.detail.isOpen; fetchTransaction();" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 text-center md:items-center sm:block sm:p-0">
        <div x-cloak @click="modelOpen = false" x-show="modelOpen" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0" 
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100" 
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-40" aria-hidden="true"
        ></div>
        <div x-cloak x-show="modelOpen" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block w-full max-w-xl p-8 my-20 overflow-hidden text-left transition-all transform bg-white rounded-lg shadow-xl 2xl:max-w-2xl">
            <div class="text-end">
            </div>
            <p class="mt-2">
                <div x-html="transaction.notes" class="mb-2 border border-gray-400 p-2 shadow rounded-lg text-sm"></div>
                <div x-text="transaction.date" class="mb-2 text-sm text-gray-500 float-end">May 7th, 2024, @ 08:39:00</div>

                  <div class="font-bold text-sm text-gray-800">From: <span class="font-normal text-gray-800" x-text="transaction.source_name"></span></div>
                  <div class="font-bold text-sm text-gray-800">To: <span  class="font-normal text-gray-800" x-text="transaction.destination_name"></span></div>
                <div x-text="transaction.budget_name" x-show="transaction.budget_name" class="mt-2 text-xs font-semibold inline-block py-1 px-2 rounded-full text-indigo-600 bg-indigo-200 uppercase last:mr-0 mr-1"></div>
                <div x-text="transaction.category_name" x-show="transaction.category_name" class="mt-2 text-xs font-semibold inline-block py-1 px-2 rounded-full text-purple-600 bg-purple-200 uppercase last:mr-0 mr-1"></div>

            </p>
            <div class="flex justify-end mt-6">
                <button @click="modelOpen = false" type="button" class="px-3 py-2 text-sm tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    function showTransaction() {
  return {
    isLoading: false,
    isOpen: false,
    transaction_id: 0,
    transaction: {
        notes: '<div class="texe-center"><svg class="bg-violet-800 animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"></svg></div>',
        source_name: '',
        destination_name: '',
        date: ''
    },
    fetchTransaction() {
      this.isLoading = true;
      this.transaction = {
        notes: '<div class="texe-center"><svg class="bg-violet-800 animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24"></svg></div>',
        source_name: '',
        destination_name: '',
        date: ''
      };
      const token = '{{ $apiToken }}';
      const options = {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      };
      fetch(`/api/transaction/${this.transaction_id}`, options)
      //personalToken
        .then((response) => response.json())
        .then((data) => {
          this.transaction = data;
          this.isLoading = false;
          this.isOpen= true;
        })
        .catch((err) => {
          console.log("ERROR", err);
          // redirect page to yahoo.com
          window.location.href = "/profile/autoRefreshAPIToken";
        });
    }
  };
}


</script>