/**
 * SmartMoney Web Push utilities
 */
window.SmartMoneyPush = {
    urlBase64ToUint8Array: function(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    },

    ensureSw: async function() {
        if (!('serviceWorker' in navigator)) throw new Error('Service Worker not supported');
        return await navigator.serviceWorker.register('/sw.js');
    },

    getSub: async function(reg) {
        return await reg.pushManager.getSubscription();
    },

    subscribe: async function(vapidKey, csrfToken, subscribeUrl) {
        const perm = await Notification.requestPermission();
        if (perm !== 'granted') throw new Error('Permission denied');

        const reg = await this.ensureSw();
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this.urlBase64ToUint8Array(vapidKey),
        });

        await fetch(subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(sub),
        });

        return sub;
    },

    unsubscribe: async function(csrfToken, unsubscribeUrl) {
        const reg = await this.ensureSw();
        const sub = await this.getSub(reg);

        if (sub) {
            await fetch(unsubscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ endpoint: sub.endpoint }),
            });
            await sub.unsubscribe();
        }
    },

    getStatus: async function() {
        try {
            const reg = await this.ensureSw();
            const sub = await this.getSub(reg);
            return sub ? 'enabled' : 'disabled';
        } catch (e) {
            return 'unsupported';
        }
    },

    /**
     * Check if push permission was revoked via browser settings.
     * Returns true if permission is still granted and subscription exists.
     */
    isStillSubscribed: async function() {
        try {
            if (Notification.permission !== 'granted') return false;
            const reg = await this.ensureSw();
            const sub = await this.getSub(reg);
            return !!sub;
        } catch (e) {
            return false;
        }
    },

    /**
     * Listen for SW messages (e.g. push-subscription-lost on SW update).
     * Call this once on page load.
     */
    listenForSwMessages: function(onSubscriptionLost) {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'push-subscription-lost') {
                    if (typeof onSubscriptionLost === 'function') {
                        onSubscriptionLost();
                    }
                }
            });
        }
    },
};
