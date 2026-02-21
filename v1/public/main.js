/******/ (() => { // webpackBootstrap
    var __webpack_exports__ = {};
    /*!******************************!*\
      !*** ./resources/js/main.js ***!
      \******************************/
    var csrftoken = document.querySelector('meta[name="csrf-token"]').getAttribute('Content');
    var VAPID_PUBLIC_KEY = document.querySelector('meta[name="VAPID_PUBLIC_KEY"]').getAttribute('Content');
    var apiToken = document.querySelector('meta[name="apiToken"]').getAttribute('Content');
    if (!('Notification' in window)) {
      alert("This browser does not support notifications.");
    }

window.addEventListener('load', function () {
    if (document.getElementById('webpush-button')) {
        document.getElementById('webpush-button').addEventListener('click', function () {
            enablePushNotifications();
            var disable_webpush = document.querySelector("#disable-webpush");
            if(disable_webpush !==null){
                disable_webpush.classList.remove("hidden");
            }
            var webpush_button = document.querySelector("#webpush-button");
            if(webpush_button !==null){
                webpush_button.classList.add("hidden");
            }
        });
        document.getElementById('disable-webpush').addEventListener('click', function () {
            disablePushNotifications();
            var webpush_button = document.querySelector("#webpush-button");
            if(webpush_button !==null){
                webpush_button.classList.remove("hidden");
            }
            var disable_webpush = document.querySelector("#disable-webpush");
            if(disable_webpush !==null){
                disable_webpush.classList.add("hidden");
            }
        });
    }
    
    // We need the service worker registration to check for a subscription
    navigator.serviceWorker.ready.then((serviceWorkerRegistration) => {
        serviceWorkerRegistration.pushManager
        .getSubscription()
        .then((subscription) => {
            
            
            if (!subscription) {
                var webpush_button = document.querySelector("#webpush-button");
                //alert("You are not subscribed to push notifications.");
                if(webpush_button !==null){
                    webpush_button.classList.remove("hidden");
                }
            }else{
                // var disable_webpush = document.querySelector("#disable-webpush");
                // alert("You are subscribed to push notifications.");
                // if(disable_webpush !==null){
                //     disable_webpush.classList.remove("hidden");
                // }
            }
        })
        .catch((err) => {
            console.error(`Error during getSubscription(): ${err}`);
        });
    });
});
  
if ("serviceWorker" in navigator) {
    window.addEventListener("load", function() {
        navigator.serviceWorker.register("/sw.js");
    });
}

function urlBase64ToUint8Array(base64String) {
    var padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/\-/g, "+").replace(/_/g, "/");

    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
    	outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

function subscribe(sub) {
    const key = sub.getKey('p256dh')
    const token = sub.getKey('auth')
    const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0]

    const data = {
        endpoint: sub.endpoint,
        public_key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
        auth_token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
        encoding: contentEncoding,
    };

    fetch('/api/notifications/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${apiToken}`,
            'X-CSRF-TOKEN': csrftoken,
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Success:', data);
    })
    .catch((error) => {
        console.error('Error:', error);
    });
}


function enablePushNotifications() {
    navigator.serviceWorker.ready.then(registration => {
        registration.pushManager.getSubscription().then(subscription => {

            if (subscription) {
                return subscription;
            }
            
            const serverKey = urlBase64ToUint8Array(VAPID_PUBLIC_KEY);

            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: serverKey
            });
        }).then(subscription => {
            if (!subscription) {
                alert('Error occured while subscribing');
                return;
            }
            subscribe(subscription);
        });
    });
}

function disablePushNotifications() {
    navigator.serviceWorker.ready.then(registration => {
        registration.pushManager.getSubscription().then(subscription => {
            if (!subscription) {
                return;
            }

            subscription.unsubscribe().then(() => {
                fetch('/api/notifications/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${apiToken}`,
                        'X-CSRF-TOKEN': csrftoken
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Success:', data);
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
            })
        });
    });
}



  /******/ })()
;