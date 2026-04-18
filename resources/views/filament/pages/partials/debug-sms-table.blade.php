<div class="fi-ta-table overflow-hidden">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5 text-start">
        <thead class="divide-y divide-gray-200 dark:divide-white/5">
            <tr>
                <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white sm:first-of-type:ps-6 w-1/3">Field</th>
                <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Value</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
            @foreach ($data as $key => $value)
                @if (!is_int($key) && $key !== 'notes')
                    <tr class="fi-ta-row transition duration-75">
                        <td class="fi-ta-cell px-3 py-4 align-top text-sm text-gray-500 dark:text-gray-400 sm:first-of-type:ps-6">
                            <span class="font-mono">{{ $key }}</span>
                        </td>
                        <td class="fi-ta-cell px-3 py-4 text-sm text-gray-950 dark:text-white">
                            @if (is_array($value))
                                <dl class="space-y-1">
                                    @foreach ($value as $subKey => $subValue)
                                        <div class="flex gap-x-2">
                                            @if (!is_int($subKey))
                                                <dt class="font-mono text-xs text-gray-500 dark:text-gray-400 min-w-[120px] underline"><u>{{ $subKey }}</u></dt>
                                            @endif
                                            <dd>{{ is_array($subValue) ? json_encode($subValue) : $subValue }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
