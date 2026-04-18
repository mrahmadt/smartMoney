<x-filament-panels::page>
    {{ $this->form }}

    @if ($results !== null)
        <div class="mt-6 space-y-4">
            @forelse ($results as $entry)
                @if ($entry['type'] === 'error')
                    <x-filament::section>
                        <x-slot name="heading">
                            <span class="fi-color-custom text-custom-600 dark:text-custom-400" style="--c-400:var(--danger-400);--c-600:var(--danger-600);">✗ {{ $entry['message'] }}</span>
                        </x-slot>
                    </x-filament::section>
                @elseif ($entry['type'] === 'info')
                    <x-filament::section>
                        <x-slot name="heading">
                            <span class="fi-color-custom text-custom-600 dark:text-custom-400" style="--c-400:var(--success-400);--c-600:var(--success-600);">✓ {{ $entry['message'] }}</span>
                        </x-slot>
                        @if (!empty($entry['data']))
                            @include('filament.pages.partials.debug-sms-table', ['data' => $entry['data']])
                        @endif
                    </x-filament::section>
                @elseif ($entry['type'] === 'transaction')
                    <x-filament::section>
                        <x-slot name="heading">
                            <span class="fi-color-custom text-custom-600 dark:text-custom-400" style="--c-400:var(--primary-400);--c-600:var(--primary-600);">✓ {{ $entry['message'] }}</span>
                        </x-slot>
                        @include('filament.pages.partials.debug-sms-table', ['data' => $entry['data']])
                    </x-filament::section>
                @endif
            @empty
                <x-filament::section>
                    <x-slot name="heading">No output from parser.</x-slot>
                </x-filament::section>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
