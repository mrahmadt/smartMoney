<div
    x-data="{
        show: false,
        vapidKey: @js($this->vapidPublicKey),
        csrf: @js(csrf_token()),
        subUrl: @js(route('webpush.subscribe')),
        init() {
            this.checkPush();
        },
        checkPush() {
            if (typeof SmartMoneyPush === 'undefined') {
                setTimeout(() => this.checkPush(), 200);
                return;
            }
            SmartMoneyPush.isStillSubscribed().then(subscribed => {
                if (!subscribed) this.show = true;
            });
        },
        subscribe() {
            SmartMoneyPush.subscribe(this.vapidKey, this.csrf, this.subUrl)
                .then(() => { this.show = false; })
                .catch(e => { console.error('Web push subscription failed:', e); });
        }
    }"
>
    <div x-show="show" x-cloak style="border: 1px solid #fbbf24; background: #fffbeb; border-radius: 12px; padding: 12px 16px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#f59e0b" style="width: 24px; height: 24px; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <p style="flex: 1; font-size: 14px; font-weight: 500; color: #92400e; margin: 0;">
                {{ __('widget.web_push_prompt') }}
            </p>
            <button
                @click="subscribe()"
                style="font-size: 13px; font-weight: 600; color: #fff; background: #f59e0b; border: none; border-radius: 8px; padding: 6px 16px; cursor: pointer; white-space: nowrap;"
            >
                {{ __('widget.web_push_enable') }}
            </button>
        </div>
    </div>
</div>
