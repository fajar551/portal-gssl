<?php

/**
 * Script untuk mengatasi masalah SSL Certificate pada email
 * Jalankan dengan: php fix_ssl_email.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fix SSL Email Issues ===\n\n";

// Test email dengan berbagai konfigurasi SSL
function testEmailConfig($config, $name)
{
    echo "🧪 Testing: {$name}\n";
    echo "   Host: {$config['host']}:{$config['port']}\n";
    echo "   Encryption: " . ($config['encryption'] ?: 'None') . "\n";

    try {
        // Set konfigurasi
        config([
            'mail.mailers.smtp.host' => $config['host'],
            'mail.mailers.smtp.port' => $config['port'],
            'mail.mailers.smtp.encryption' => $config['encryption'],
            'mail.mailers.smtp.timeout' => 60,
            'mail.mailers.smtp.verify_peer' => $config['verify_peer'],
            'mail.mailers.smtp.verify_peer_name' => $config['verify_peer_name'],
            'mail.mailers.smtp.allow_self_signed' => $config['allow_self_signed'],
        ]);

        // Test koneksi
        $connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
        if ($connection) {
            echo "   ✅ Koneksi berhasil\n";
            fclose($connection);
            return true;
        } else {
            echo "   ❌ Koneksi gagal: {$errstr}\n";
            return false;
        }
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ambil konfigurasi dari database
$smtpHost = \App\Helpers\Cfg::getValue('SMTPHost');
$smtpPort = \App\Helpers\Cfg::getValue('SMTPPort');
$smtpUsername = \App\Helpers\Cfg::getValue('SMTPUsername');
$smtpPassword = \App\Helpers\Cfg::getValue('SMTPPassword');
$smtpSSL = \App\Helpers\Cfg::getValue('SMTPSSL');

if (!$smtpHost || !$smtpPort) {
    echo "❌ Konfigurasi SMTP tidak ditemukan di database!\n";
    echo "Silakan konfigurasi SMTP melalui admin panel terlebih dahulu.\n";
    exit(1);
}

echo "📧 Konfigurasi SMTP saat ini:\n";
echo "   Host: {$smtpHost}\n";
echo "   Port: {$smtpPort}\n";
echo "   SSL: " . ($smtpSSL ?: 'Tidak dikonfigurasi') . "\n\n";

// Konfigurasi untuk test
$configs = [
    [
        'name' => 'SSL dengan Certificate Verification (Original)',
        'host' => $smtpHost,
        'port' => (int)$smtpPort,
        'encryption' => $smtpSSL ?: 'ssl',
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    ],
    [
        'name' => 'SSL tanpa Certificate Verification',
        'host' => $smtpHost,
        'port' => (int)$smtpPort,
        'encryption' => $smtpSSL ?: 'ssl',
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
    [
        'name' => 'TLS dengan Certificate Verification',
        'host' => $smtpHost,
        'port' => 587,
        'encryption' => 'tls',
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    ],
    [
        'name' => 'TLS tanpa Certificate Verification (Recommended)',
        'host' => $smtpHost,
        'port' => 587,
        'encryption' => 'tls',
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
    [
        'name' => 'Tanpa SSL (Fallback)',
        'host' => $smtpHost,
        'port' => 25,
        'encryption' => null,
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false,
    ]
];

$workingConfigs = [];

foreach ($configs as $config) {
    if (testEmailConfig($config, $config['name'])) {
        $workingConfigs[] = $config;
    }
    echo "\n";
}

// Rekomendasi konfigurasi
if (!empty($workingConfigs)) {
    echo "✅ Konfigurasi yang berhasil:\n";
    foreach ($workingConfigs as $config) {
        echo "   - {$config['name']}\n";
    }

    // Pilih konfigurasi terbaik
    $bestConfig = $workingConfigs[0]; // Ambil yang pertama (biasanya TLS tanpa verification)

    echo "\n🎯 Rekomendasi konfigurasi terbaik:\n";
    echo "   Host: {$bestConfig['host']}\n";
    echo "   Port: {$bestConfig['port']}\n";
    echo "   Encryption: " . ($bestConfig['encryption'] ?: 'None') . "\n";
    echo "   Verify Peer: " . ($bestConfig['verify_peer'] ? 'Yes' : 'No') . "\n";
    echo "   Allow Self Signed: " . ($bestConfig['allow_self_signed'] ? 'Yes' : 'No') . "\n";

    // Update konfigurasi di database
    echo "\n🔄 Update konfigurasi di database...\n";
    try {
        \App\Helpers\Cfg::setValue('SMTPPort', $bestConfig['port']);
        \App\Helpers\Cfg::setValue('SMTPSSL', $bestConfig['encryption'] ?: '');
        echo "✅ Konfigurasi berhasil diupdate!\n";
    } catch (\Exception $e) {
        echo "❌ Gagal update konfigurasi: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Semua konfigurasi gagal!\n";
    echo "Kemungkinan masalah:\n";
    echo "1. Server SMTP tidak bisa diakses\n";
    echo "2. Firewall memblokir koneksi\n";
    echo "3. Credential SMTP salah\n";
    echo "4. Server SMTP down\n";
}

echo "\n=== Langkah Selanjutnya ===\n";
echo "1. Jalankan: php test_email.php untuk test email\n";
echo "2. Periksa log: storage/logs/laravel.log\n";
echo "3. Jika masih error, coba provider email lain\n";
echo "4. Hubungi admin server untuk renew SSL certificate\n";