<?php
$host = 'mx.kerjamail.co';
$port = 993;
$user = 'finance@m2b.co.id';
$pass = 'Finance_2025';
$mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}INBOX';

echo "Testing connection to: $mailbox\n";
echo "User: $user\n\n";

$connection = @imap_open($mailbox, $user, $pass);

if ($connection) {
    echo "✅ SUCCESS! Finance email connected!\n";
    $check = imap_check($connection);
    echo "Total messages: " . $check->Nmsgs . "\n";
    imap_close($connection);
} else {
    echo "❌ FAILED!\n";
    echo "Error: " . imap_last_error() . "\n";
}
