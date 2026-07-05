<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= htmlspecialchars(($settings['payhub_base_url'] ?? 'https://merchant.payhub.com.ng')) ?>/inline.js"></script>
</head>
<body class="bg-black text-white antialiased font-sans flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h2 class="text-2xl font-bold mb-4">Initializing Secure Payment...</h2>
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600 mx-auto"></div>
        <p class="mt-4 text-gray-400">Please wait while we open the payment gateway.</p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let handler = PayhubPop.setup({
              key: '<?= htmlspecialchars($publicKey) ?>',
              email: '<?= htmlspecialchars($email) ?>',
              amount: <?= (float)$amount * 100 ?>, // Kobo
              ref: '<?= htmlspecialchars($reference) ?>',
              onClose: function(){
                alert('Payment cancelled.');
                window.location.href = "/coin-shop";
              },
              callback: function(response){
                window.location.href = "/payment/verify?reference=" + response.reference;
              }
            });

            handler.openIframe();
        });
    </script>
</body>
</html>
