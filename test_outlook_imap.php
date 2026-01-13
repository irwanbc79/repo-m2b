<?php
$host = 'outlook.office365.com';
$port = 993;
$user = 'mora.multiberkah@outlook.com';
$pass = 'outlookMora0920';
$mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}INBOX';

echo "Testing Outlook IMAP connection...\n";
echo "Host: $host\n";
echo "User: $user\n\n";

$connection = @imap_open($mailbox, $user, $pass);

if ($connection) {
    echo "✅ SUCCESS! Outlook connected!\n";
    $check = imap_check($connection);
    echo "Total messages: " . $check->Nmsgs . "\n";
    imap_close($connection);
} else {
    echo "❌ FAILED!\n";
    echo "Error: " . imap_last_error() . "\n";
    echo "\nKemungkinan penyebab:\n";
    echo "1. Password salah\n";
    echo "2. 2FA aktif - butuh App Password\n";
    echo "3. IMAP tidak diaktifkan di Outlook settings\n";
}
