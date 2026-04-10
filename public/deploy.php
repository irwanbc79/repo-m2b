<?php
$secret = 'M2B_DEPLOY_2024_SECRET';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if (!hash_equals('sha256=' . hash_hmac('sha256', $payload, $secret), $signature)) {
    http_response_code(403);
    die('Forbidden');
}

$base = '/home/u301249154/domains/m2b.co.id/public_html/portal';
$php  = PHP_BINARY ?: 'php';

$commands = [
    "cd {$base} && git pull origin main",
    "{$php} {$base}/artisan route:clear",
    "{$php} {$base}/artisan config:clear",
    "{$php} {$base}/artisan view:clear",
    "{$php} {$base}/artisan cache:clear",
    "{$php} {$base}/artisan optimize",
];

$output = '';
foreach ($commands as $cmd) {
    $output .= "$ {$cmd}\n" . shell_exec("{$cmd} 2>&1") . "\n";
}

file_put_contents('deploy.log', date('Y-m-d H:i:s') . "\n" . $output . "\n\n", FILE_APPEND);
echo "OK: " . date('Y-m-d H:i:s');
