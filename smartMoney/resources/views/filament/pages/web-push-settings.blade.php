<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            Enable browser notifications for this account.
        </div>

        <div class="flex gap-2">
            <x-filament::button type="button" id="btn-subscribe">
                Enable notifications
            </x-filament::button>

            <x-filament::button type="button" color="gray" id="btn-unsubscribe">
                Disable notifications
            </x-filament::button>
        </div>

        <div class="text-xs text-gray-500">
            Status: <span id="push-status">unknown</span>
        </div>
    </div>

    <script>
        const VAPID_PUBLIC_KEY = @js($this->getVapidPublicKey());

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
            return outputArray;
        }

        async function ensureSw() {
            if (!('serviceWorker' in navigator)) throw new Error('Service Worker not supported');
            return await navigator.serviceWorker.register('/sw.js');
        }

        async function getSub(reg) {
            return await reg.pushManager.getSubscription();
        }

        async function setStatus(text) {
            document.getElementById('push-status').textContent = text;
        }

        async function subscribe() {
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') throw new Error('Permission denied');

            const reg = await ensureSw();

            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });

            await fetch(@js(route('webpush.subscribe')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @js(csrf_token()),
                },
                body: JSON.stringify(sub),
            });

            await setStatus('enabled');
        }

        async function unsubscribe() {
            const reg = await ensureSw();
            const sub = await getSub(reg);

            if (sub) {
                await fetch(@js(route('webpush.unsubscribe')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify({ endpoint: sub.endpoint }),
                });

                await sub.unsubscribe();
            }

            await setStatus('disabled');
        }

        (async () => {
            try {
                const reg = await ensureSw();
                const sub = await getSub(reg);
                await setStatus(sub ? 'enabled' : 'disabled');
            } catch (e) {
                await setStatus('unsupported');
            }
        })();

        document.getElementById('btn-subscribe').addEventListener('click', async () => {
            try { await subscribe(); } catch (e) { alert(e.message || e); }
        });

        document.getElementById('btn-unsubscribe').addEventListener('click', async () => {
            try { await unsubscribe(); } catch (e) { alert(e.message || e); }
        });
    </script>
</x-filament-panels::page>