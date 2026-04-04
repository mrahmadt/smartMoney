<x-filament-panels::page>
    <div style="max-width: 600px;">
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#f59e0b" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <h3 style="font-size: 18px; font-weight: 600; margin: 0; color: #111827;">
                    {{ __('menu.web_push') }}
                </h3>
            </div>

            <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0 0 20px 0;">
                {{ __('widget.web_push_description') }}
            </p>

            <div style="display: flex; gap: 12px; margin-bottom: 20px;">
                <x-filament::button type="button" id="btn-subscribe" color="success">
                    {{ __('widget.enable_notifications') }}
                </x-filament::button>

                <x-filament::button type="button" color="gray" id="btn-unsubscribe">
                    {{ __('widget.disable_notifications') }}
                </x-filament::button>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <span style="font-size: 13px; color: #6b7280;">{{ __('widget.web_push_status') }}:</span>
                <span id="push-status" style="font-size: 13px; font-weight: 600; color: #374151;">{{ __('widget.web_push_status_unknown') }}</span>
            </div>
        </div>
    </div>

    <script src="/js/webpush.js"></script>
    <script>
        const VAPID_PUBLIC_KEY = @js($this->getVapidPublicKey());
        const CSRF_TOKEN = @js(csrf_token());
        const SUBSCRIBE_URL = @js(route('webpush.subscribe'));
        const UNSUBSCRIBE_URL = @js(route('webpush.unsubscribe'));

        (async () => {
            document.getElementById('push-status').textContent = await SmartMoneyPush.getStatus();
        })();

        document.getElementById('btn-subscribe').addEventListener('click', async () => {
            try {
                await SmartMoneyPush.subscribe(VAPID_PUBLIC_KEY, CSRF_TOKEN, SUBSCRIBE_URL);
                document.getElementById('push-status').textContent = 'enabled';
            } catch (e) { alert(e.message || e); }
        });

        document.getElementById('btn-unsubscribe').addEventListener('click', async () => {
            try {
                await SmartMoneyPush.unsubscribe(CSRF_TOKEN, UNSUBSCRIBE_URL);
                document.getElementById('push-status').textContent = 'disabled';
            } catch (e) { alert(e.message || e); }
        });

        // Listen for SW update losing subscription
        SmartMoneyPush.listenForSwMessages(function() {
            document.getElementById('push-status').textContent = 'disabled (re-subscribe needed)';
        });

        // Check if permission was revoked externally
        (async () => {
            const stillOk = await SmartMoneyPush.isStillSubscribed();
            const status = await SmartMoneyPush.getStatus();
            if (status === 'enabled' && !stillOk) {
                document.getElementById('push-status').textContent = 'disabled (permission revoked)';
            }
        })();
    </script>
</x-filament-panels::page>