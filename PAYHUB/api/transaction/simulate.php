<?php
// api/transaction/simulate.php  — Sandbox-only: instantly marks a test
// transaction as successful without touching the real Paystack API.
// Security: only works if is_test = 1. Live transactions are rejected.
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$headers = getallheaders();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$auth || strpos($auth, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$sk = str_replace('Bearer ', '', $auth);
$db = Database::connect();
$stmt = $db->prepare("SELECT id FROM users WHERE test_secret_key = ?");
$stmt->execute([$sk]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Invalid or non-test Secret Key. Only sk_test_ keys may use simulate.']);
    exit;
}

$ref = $_GET['reference'] ?? '';
if (!$ref) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Missing reference']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE reference = ? AND user_id = ?");
$stmt->execute([$ref, $user['id']]);
$tx = $stmt->fetch();

if (!$tx) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => 'Transaction not found']);
    exit;
}

if (!(bool)$tx['is_test']) {
    http_response_code(403);
    echo json_encode(['status' => false, 'message' => 'This endpoint cannot be used for live transactions.']);
    exit;
}

if ($tx['status'] === 'success') {
    echo json_encode(['status' => true, 'message' => 'Already successful', 'data' => ['reference' => $ref, 'status' => 'success']]);
    exit;
}

$db->beginTransaction();
try {
    $amount = (float)$tx['amount'];
    $fee = calculate_fees($amount, false, $user['id']);
    $settled = $amount - $fee;

    $stmt = $db->prepare("UPDATE transactions SET status = 'success', fee_amount = ?, settled_amount = ?, gateway_reference = ? WHERE id = ?");
    $stmt->execute([$fee, $settled, 'SIMULATED_' . strtoupper(bin2hex(random_bytes(4))), $tx['id']]);

    log_ledger_entry($user['id'], $settled, 'credit', 'payment', "Sandbox simulated payment: $ref", true);
    log_transaction_event($tx['id'], 'simulated', 'Payment marked successful via sandbox simulate endpoint');

    $db->commit();

    // Fire merchant webhook so their verify endpoint gets notified
    trigger_merchant_webhook($tx['id']);

    echo json_encode([
        'status'  => true,
        'message' => 'Transaction simulated as successful',
        'data'    => [
            'reference' => $ref,
            'status'    => 'success',
            'amount'    => $tx['amount'] * 100,
        ]
    ]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Simulation failed: ' . $e->getMessage()]);
}
