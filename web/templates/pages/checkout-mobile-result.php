<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - SOLOREEL</title>
    <style>
        body { background: #000; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; text-align: center; }
        .icon { font-size: 56px; }
        h2 { font-size: 20px; margin: 12px 16px; }
        p { color: #9ca3af; font-size: 14px; margin: 8px 24px; }
        .spinner { width: 48px; height: 48px; border: 3px solid #333; border-bottom-color: #dc2626; border-radius: 50%; margin: 20px auto; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="content">
        <div class="spinner"></div>
        <h2>Confirming your payment...</h2>
        <p>Please wait a moment.</p>
    </div>

    <script>
        (function() {
            var reference = <?= json_encode($reference ?? '') ?>;
            var content = document.getElementById('content');

            function show(icon, title, message) {
                content.innerHTML = '<div class="icon">' + icon + '</div><h2>' + title + '</h2><p>' + message + '</p>';
            }

            if (!reference) {
                show('&#10060;', 'Missing reference', 'No payment reference was provided.');
                return;
            }

            fetch('/api/v1/payment/verify?reference=' + encodeURIComponent(reference))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res && res.status === true) {
                        show('&#127881;', 'Payment Successful!', 'Your coins have been added. You can close this window and return to the app.');
                    } else {
                        show('&#9203;', 'Payment Pending', (res && (res.error || res.message)) || 'We could not confirm the payment yet. If you were charged, your coins will be credited shortly.');
                    }
                })
                .catch(function() {
                    show('&#9888;&#65039;', 'Connection issue', 'We could not confirm the payment. Please reopen the coins page in the app.');
                });
        })();
    </script>
</body>
</html>
