<?php
// =====================================================
//  לב ים — Grow Payments Webhook Handler
//  ─────────────────────────────────────────────────
//  Grow Payments calls this URL after a successful payment.
//  Set this as your webhook URL in the Grow dashboard:
//    https://yourdomain.com/levyam-webhook.php
// =====================================================

define('DATA_FILE', __DIR__ . '/levyam-data.json');
define('LOG_FILE',  __DIR__ . '/levyam-webhook.log');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Read raw body (Grow may send JSON or form data)
$raw  = file_get_contents('php://input');
$post = $_POST;

// Try to parse JSON body if POST is empty
if (empty($post) && !empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $post = $decoded;
    }
}

// ─── LOG FOR DEBUGGING ───────────────────────────────
$logEntry = date('Y-m-d H:i:s') . ' | POST: ' . json_encode($post) . ' | RAW: ' . substr($raw, 0, 500) . "\n";
file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);

// ─── VALIDATE PAYMENT STATUS ─────────────────────────
// Grow sends a status field indicating success
// Common field names: status, StatusCode, transactionStatus
$status = $post['status']            ??
          $post['StatusCode']        ??
          $post['transactionStatus'] ??
          $post['paymentStatus']     ?? '';

$isSuccess = in_array(strtolower((string)$status), ['success', '1', 'approved', 'ok', '200'], true)
          || $status === 1;

// Also check for explicit failure indicators
$isFailed = in_array(strtolower((string)$status), ['failed', 'failure', 'error', 'cancelled', '0'], true);

if ($isFailed) {
    // Log but don't update counter
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " | FAILED payment, not counting.\n", FILE_APPEND | LOCK_EX);
    http_response_code(200);
    echo 'ok';
    exit;
}

// ─── EXTRACT AMOUNT ──────────────────────────────────
$amount = (int)(
    $post['sum']            ??
    $post['amount']         ??
    $post['Sum']            ??
    $post['transactionSum'] ?? 0
);

if ($amount <= 0) {
    // Amount missing — log warning, respond OK so Grow doesn't retry
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " | WARNING: amount is 0 or missing.\n", FILE_APPEND | LOCK_EX);
    http_response_code(200);
    echo 'ok';
    exit;
}

// ─── UPDATE PROGRESS (with file locking) ─────────────
$fp = fopen(DATA_FILE, 'c+');
if (!$fp) {
    http_response_code(500);
    exit('Cannot open data file');
}

if (flock($fp, LOCK_EX)) {
    $contents = stream_get_contents($fp);
    $data     = json_decode($contents, true) ?? ['total' => 0, 'backers' => 0];

    $data['total']   = (int)($data['total']   ?? 0) + $amount;
    $data['backers'] = (int)($data['backers'] ?? 0) + 1;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data));
    fflush($fp);
    flock($fp, LOCK_UN);

    file_put_contents(LOG_FILE,
        date('Y-m-d H:i:s') . " | SUCCESS: +₪{$amount} → total ₪{$data['total']} | backers: {$data['backers']}\n",
        FILE_APPEND | LOCK_EX
    );
}
fclose($fp);

http_response_code(200);
echo 'ok';
