<div>
    @if($this->invalidCount > 0)
    <div style="border: 1px solid #f87171; background: #fef2f2; border-radius: 12px; padding: 12px 16px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#dc2626" style="width: 24px; height: 24px; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <p style="flex: 1; font-size: 14px; font-weight: 500; color: #991b1b; margin: 0;">
                {{ __('menu.invalid_sms_banner', ['count' => $this->invalidCount, 'label' => $this->invalidCount === 1 ? __('menu.invalid_sms_message') : __('menu.invalid_sms_messages')]) }}
            </p>
            <a href="{{ $this->url }}" style="font-size: 14px; font-weight: 600; color: #dc2626; text-decoration: underline;">
                {{ __('menu.view') }}
            </a>
        </div>
    </div>
    @endif
</div>
