<?php

/**
 * Script sederhana untuk mencari konfigurasi email
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Pencarian Konfigurasi Email ===\n\n";

// Cari konfigurasi SMTP
$smtpSettings = [
    'SMTPHost',
    'SMTPPort',
    'SMTPUsername',
    'SMTPPassword',
    'SMTPSSL',
    'SMTPFromAddress',
    'SMTPFromName',
    'SMTPReplyTo'
];

echo "📧 Konfigurasi SMTP:\n";
foreach ($smtpSettings as $setting) {
    $value = \App\Helpers\Cfg::getValue($setting);
    echo "{$setting}: " . ($value ?: 'Tidak dikonfigurasi') . "\n";
}

echo "\n🔍 Mencari konfigurasi yang mengandung 'gudangssl'...\n";
try {
    $configs = \App\Models\Configuration::where('value', 'like', '%gudangssl%')->get();
    if ($configs->count() > 0) {
        foreach ($configs as $config) {
            echo "Setting: {$config->setting} = {$config->value}\n";
        }
    } else {
        echo "Tidak ditemukan konfigurasi yang mengandung 'gudangssl'\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n🔍 Mencari konfigurasi yang mengandung 'info@'...\n";
try {
    $configs = \App\Models\Configuration::where('value', 'like', '%info@%')->get();
    if ($configs->count() > 0) {
        foreach ($configs as $config) {
            echo "Setting: {$config->setting} = {$config->value}\n";
        }
    } else {
        echo "Tidak ditemukan konfigurasi yang mengandung 'info@'\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
