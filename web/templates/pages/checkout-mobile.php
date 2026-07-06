<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SOLOREEL</title>
    <script>
        // Polyfill document.currentScript to support Cloudflare Rocket Loader and async environments
        Object.defineProperty(document, 'currentScript', {
            get: function() {
                var scripts = document.getElementsByTagName('script');
                for (var i = 0; i < scripts.length; i++) {
                    if (scripts[i].src.indexOf('inline.js') !== -1) {
                        return scripts[i];
                    }
                }
                return { src: 'https://merchant.payhub.com.ng/inline.js' };
            },
            configurable: true
        });
    </script>
    <script src="https://merchant.payhub.com.ng/inline.js"></script>
    <style>
        body { background: #000; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; text-align: center; }
        .spinner { width: 48px; height: 48px; border: 3px solid #333; border-bottom-color: #dc2626; border-radius: 50%; margin: 20px auto 0; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { font-size: 20px; margin: 0 16px; }
        p { color: #9ca3af; font-size: 14px; margin: 16px; }
        /* Force responsiveness on the Payhub Checkout Iframe and Container */
        #payhub-checkout-overlay > div {
            width: 95% !important;
            max-width: 420px !important;
            height: 90vh !important;
            max-height: 580px !important;
            border-radius: 16px !important;
        }
        #payhub-checkout-overlay iframe {
            border-radius: 16px !important;
        }
    </style>
</head>
<body>
    <div>
        <h2>Initializing Secure Payment...</h2>
        <div class="spinner"></div>
        <p>Please wait while we open the payment gateway.</p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let handler = PayhubPop.setup({
              key: '<?= htmlspecialchars($publicKey) ?>',
              email: '<?= htmlspecialchars($email) ?>',
              amount: <?= (float)$amount * 100 ?>, // Kobo
              ref: '<?= htmlspecialchars($reference) ?>',
              onClose: function(){
                // Neutral URL — must NOT contain "verify"/"callback"/"success" so the
                // app WebViews don't mistake a cancelled checkout for a payment.
                window.location.href = "/pay/closed";
              },
              callback: function(response){
                window.location.href = "/pay/verify?reference=" + encodeURIComponent(response.reference);
              }
            });

            handler.openIframe();
        });
    </script>
</body>
</html>
