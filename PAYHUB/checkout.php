<?php
// php-version/checkout.php
require_once 'includes/functions.php';

$ref = sanitize($_GET['ref'] ?? '');
$db = Database::connect();
// Use the transaction's own is_test flag (set at initialize-time from which
// secret key — sk_test_/sk_live_ — the caller used), the same way verify.php
// already does. Previously this joined to the merchant's account-wide
// is_test_mode toggle instead, so a transaction correctly initialized in test
// mode would still render with the live Paystack key here.
$stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ?");
$stmt->execute([$ref]);
$tx = $stmt->fetch();

$isTest = $tx ? (bool)$tx['is_test'] : false;
$pk = $isTest ? getConfig('paystack_test_public_key') : getConfig('paystack_public_key');

$amount = $tx ? (float)$tx['amount'] : (float)($_GET['amount'] ?? 1000);
$email = $tx ? $tx['customer_email'] : ($_GET['email'] ?? '');
if (!$ref) $ref = 'PH_'.time();
$isEmbedded = isset($_GET['embed']) && $_GET['embed'] == '1';

if (!$isEmbedded) {
    include 'includes/header.php';
} else {
    // Basic styles for embedded version
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    </head>
    <body class="bg-white">
    <?php
}
?>
<div class="<?php echo $isEmbedded ? '' : 'pt-32 pb-20 bg-slate-50 min-h-screen flex items-center justify-center p-4'; ?>">
    <?php if ($isTest): ?>
        <div class="fixed top-0 left-0 right-0 bg-amber-500 text-white text-[10px] font-bold uppercase tracking-[0.2em] py-2 text-center z-[100] flex items-center justify-center gap-2">
            <i class="lucide-shield-alert w-3 h-3"></i>
            Test Mode - No real money will be processed
            <i class="lucide-shield-alert w-3 h-3"></i>
        </div>
    <?php endif; ?>
    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden relative">
        <div class="p-8 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                    <i class="lucide-credit-card w-4 h-4"></i>
                </div>
                <span class="font-bold text-slate-900 uppercase text-xs tracking-widest">Secure Checkout</span>
            </div>
            <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase">Amount</p>
                <p class="text-lg font-bold text-indigo-600"><?php echo formatCurrency($amount); ?></p>
            </div>
        </div>
        <div class="p-8">
            <div class="mb-8">
                <p class="text-sm text-slate-500 mb-1">Paying to</p>
                <p class="font-bold text-slate-900 text-lg"><?php echo getConfig('site_name', 'Payhub'); ?></p>
            </div>

            <div class="space-y-4 mb-8">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-400">
                        <i class="lucide-user w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Customer</p>
                        <p class="text-sm font-bold text-slate-700"><?php echo $email ?: 'Guest Customer'; ?></p>
                    </div>
                </div>
            </div>

            <?php if (!$isTest): ?>
            <script src="https://js.paystack.co/v2/inline.js"></script>
            <?php endif; ?>
            <button onclick="<?php echo $isTest ? 'simulateSuccess()' : 'payWithPaystack()'; ?>" id="pay-btn" class="w-full <?php echo $isTest ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-100' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200'; ?> text-white py-4 rounded-2xl font-bold shadow-lg transition-all flex items-center justify-center gap-2">
                <i class="lucide-<?php echo $isTest ? 'beaker' : 'shield-check'; ?> w-5 h-5"></i>
                <?php echo $isTest ? 'Simulate Success' : 'Pay Now'; ?>
            </button>

            <p class="mt-6 text-center text-[10px] text-slate-400 uppercase tracking-widest flex items-center justify-center gap-1">
                <i class="lucide-lock w-3 h-3"></i> Secured by Payhub
            </p>
        </div>
    </div>
</div>

<script>
<?php if ($isTest): ?>
// SANDBOX MODE: Simulate success without going through real Paystack API
function simulateSuccess() {
    const btn = document.getElementById('pay-btn');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    fetch('<?php echo BASE_URL; ?>api/transaction/simulate.php?reference=<?php echo urlencode($ref); ?>', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer <?php echo $pk; ?>'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status) {
            <?php if ($isEmbedded): ?>
                window.parent.postMessage({
                    type: 'payhub_success',
                    data: { reference: '<?php echo addslashes($ref); ?>', status: 'success' }
                }, '*');
            <?php else: ?>
                window.location.href = 'verify.php?reference=<?php echo urlencode($ref); ?>';
            <?php endif; ?>
        } else {
            btn.disabled = false;
            btn.textContent = 'Simulate Success';
            alert('Simulation failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(function(e) {
        btn.disabled = false;
        btn.textContent = 'Simulate Success';
        alert('Network error: ' + e.message);
    });
}
<?php else: ?>
// LIVE MODE: Use real Paystack
function payWithPaystack() {
    const paystack = new PaystackPop();
    paystack.newTransaction({
        key: '<?php echo $pk; ?>',
        email: '<?php echo $email; ?>',
        amount: <?php echo $amount * 100; ?>,
        reference: '<?php echo $ref; ?>',
        onCancel: function(){
            alert('Transaction cancelled.');
        },
        onSuccess: function(response){
            <?php if ($isEmbedded): ?>
                window.parent.postMessage({
                    type: 'payhub_success',
                    data: response
                }, '*');
            <?php else: ?>
                window.location.href = 'verify.php?reference=' + response.reference;
            <?php endif; ?>
        }
    });
}
<?php endif; ?>
</script>

<?php
if (!$isEmbedded) {
    include 'includes/footer.php';
} else {
    ?></body></html><?php
}
?>
