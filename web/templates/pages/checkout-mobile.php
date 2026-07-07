<?php
// Self-contained mobile checkout: no external scripts, no inline.js, no
// document.currentScript detection. The app WebViews load this page; it embeds
// Payhub's checkout directly in a full-screen iframe and surfaces any failure
// on screen instead of leaving a blank page.
$iframeUrl = $payhubBaseUrl . '/checkout.php?ref=' . urlencode($reference)
           . '&amount=' . urlencode((string)(float)$amount)
           . '&email=' . urlencode($email)
           . '&embed=1';
$hostedUrl = $payhubBaseUrl . '/checkout.php?ref=' . urlencode($reference)
           . '&amount=' . urlencode((string)(float)$amount)
           . '&email=' . urlencode($email);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SOLOREEL</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { background: #000; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; display: flex; flex-direction: column; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #0A0A0A; border-bottom: 1px solid #1f1f1f; flex: 0 0 auto; }
        .topbar .brand { font-weight: 800; font-size: 14px; letter-spacing: 1px; }
        .topbar .brand span { color: #dc2626; }
        .topbar a { color: #9ca3af; font-size: 13px; text-decoration: none; padding: 6px 10px; }
        .stage { position: relative; flex: 1 1 auto; background: #fff; }
        .stage iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: none; background: #fff; }
        #loading { position: absolute; inset: 0; background: #000; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 24px; }
        .spinner { width: 44px; height: 44px; border: 3px solid #333; border-bottom-color: #dc2626; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #loading h2 { font-size: 18px; margin: 20px 0 8px; }
        #loading p { color: #9ca3af; font-size: 13px; margin: 0; }
        #errorbox { position: absolute; inset: 0; background: #000; display: none; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 24px; }
        #errorbox .icon { font-size: 42px; }
        #errorbox h2 { font-size: 18px; margin: 12px 0 8px; }
        #errorbox p { color: #9ca3af; font-size: 13px; margin: 0 0 20px; word-break: break-word; max-width: 480px; }
        #errorbox button, #errorbox a.alt { display: block; width: 100%; max-width: 300px; padding: 14px 16px; border-radius: 12px; font-size: 15px; font-weight: 700; margin-bottom: 12px; text-align: center; text-decoration: none; }
        #errorbox button { background: #dc2626; color: #fff; border: none; }
        #errorbox a.alt { background: transparent; color: #fff; border: 1px solid #444; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">SOLO<span>REEL</span> &middot; Secure Checkout</div>
        <a href="/pay/closed">Cancel</a>
    </div>
    <div class="stage">
        <iframe id="gateway" src="<?= htmlspecialchars($iframeUrl) ?>" allow="payment; clipboard-read; clipboard-write"></iframe>
        <div id="loading">
            <div class="spinner"></div>
            <h2>Loading Secure Payment...</h2>
            <p>Please wait while we connect to the payment gateway.</p>
        </div>
        <div id="errorbox">
            <div class="icon">&#9888;&#65039;</div>
            <h2>Couldn't load the payment page</h2>
            <p id="errormsg"></p>
            <button onclick="retryGateway()">Retry</button>
            <a class="alt" href="<?= htmlspecialchars($hostedUrl) ?>">Open payment page directly</a>
        </div>
    </div>

    <script>
        (function () {
            var frame = document.getElementById('gateway');
            var loading = document.getElementById('loading');
            var errorbox = document.getElementById('errorbox');
            var errormsg = document.getElementById('errormsg');
            var loaded = false;
            var timeoutId = null;

            function showError(message) {
                loading.style.display = 'none';
                errorbox.style.display = 'flex';
                errormsg.textContent = message || 'An unknown error occurred.';
            }

            // Surface script errors on screen — a silent blank page is undebuggable
            // from a phone; this way the failure reason is always visible.
            window.onerror = function (msg, src, line) {
                showError(msg + (line ? (' (line ' + line + ')') : ''));
                return false;
            };

            function armTimeout() {
                if (timeoutId) clearTimeout(timeoutId);
                timeoutId = setTimeout(function () {
                    if (!loaded) {
                        showError('The payment gateway is taking too long to respond. Check your internet connection and try again.');
                    }
                }, 25000);
            }

            frame.addEventListener('load', function () {
                loaded = true;
                loading.style.display = 'none';
                if (timeoutId) clearTimeout(timeoutId);
            });

            window.retryGateway = function () {
                loaded = false;
                errorbox.style.display = 'none';
                loading.style.display = 'flex';
                armTimeout();
                frame.src = frame.src;
            };

            // Payhub's embedded checkout posts {type:'payhub_success'} to its parent
            // when the payment completes (both sandbox-simulate and live Paystack).
            window.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'payhub_success') {
                    window.location.href = '/pay/verify?reference=<?= urlencode($reference) ?>';
                }
            }, false);

            armTimeout();
        })();
    </script>
</body>
</html>
