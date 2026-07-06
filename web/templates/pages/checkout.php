<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-black text-white antialiased font-sans flex items-center justify-center min-h-screen">
    <div class="text-center px-4">
        <h2 class="text-xl sm:text-2xl font-bold mb-4">Initializing Secure Payment...</h2>
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600 mx-auto"></div>
        <p class="mt-4 text-sm text-gray-400">Please wait while we open the payment gateway.</p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let handler = PayhubPop.setup({
              key: '<?= htmlspecialchars($publicKey) ?>',
              email: '<?= htmlspecialchars($email) ?>',
              amount: <?= (float)$amount * 100 ?>, // Kobo
              reference: '<?= htmlspecialchars($reference) ?>',
              ref: '<?= htmlspecialchars($reference) ?>',
              onClose: function(){
                alert('Payment cancelled.');
                window.location.href = "/coin-shop";
              },
              callback: function(response){
                window.location.href = "/payment/verify?reference=<?= htmlspecialchars($reference) ?>";
              }
            });

            handler.openIframe();
        });
    </script>
</body>
</html>
