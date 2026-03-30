<div>
    @foreach($this->alerts as $alert)
    <div wire:key="pinned-alert-{{ $alert['id'] }}" style="border: 1px solid #93c5fd; background: #eff6ff; border-radius: 12px; padding: 12px 16px; margin-bottom: 8px;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <a href="{{ $alert['url'] }}" style="display: flex; align-items: flex-start; gap: 12px; flex: 1; min-width: 0; text-decoration: none; color: inherit;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#3b82f6" style="width: 22px; height: 22px; flex-shrink: 0; margin-top: 1px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <div style="flex: 1; min-width: 0;">
                    <p style="font-size: 14px; font-weight: 600; color: #1e40af; margin: 0;">{{ $alert['title'] }}</p>
                    <p style="font-size: 13px; color: #1e3a8a; margin: 4px 0 0 0; white-space: pre-line;">{{ \Illuminate\Support\Str::limit($alert['message'], 150) }}</p>
                </div>
            </a>
            <button wire:click="dismiss({{ $alert['id'] }})" style="background: none; border: none; cursor: pointer; padding: 2px; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#6b7280" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    @endforeach
</div>
