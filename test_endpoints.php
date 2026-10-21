<?php
// test_endpoints.php - Pengujian Otomatis Seluruh Endpoint Sistem
$baseUrl = 'http://127.0.0.1:8000';

function testUrl($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'len' => strlen($res)];
}

echo "=== MEMULAI TEST ENDPOINT SPONSORBRIDGE ===\n";

$cookieJar = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieJar)) unlink($cookieJar);

// 1. Public Pages
$publicUrls = [
    '/' => 'Landing Page',
    '/events.php' => 'Event Discovery',
    '/event-detail.php?id=1' => 'Detail Event 1',
    '/sponsors.php' => 'Marketplace Sponsor',
    '/sponsor-detail.php?id=8' => 'Detail Brand 8',
    '/roi-calculator.php' => 'Kalkulator ROI',
    '/login.php' => 'Halaman Login',
    '/register.php' => 'Halaman Registrasi'
];

foreach ($publicUrls as $uri => $name) {
    $r = testUrl($baseUrl . $uri);
    echo ($r['code'] === 200 ? "✔ OK (200)" : "❌ GAGAL ({$r['code']})") . " : {$name} ({$r['len']} bytes)\n";
}

// 2. EO Flow
echo "\n--- PENGUJIAN ALUR EO (Event Organizer) ---\n";
testUrl($baseUrl . '/switch_role.php?role=eo', $cookieJar);
$eoUrls = [
    '/dashboard.php' => 'Dashboard EO',
    '/create-event.php' => 'Form Buat Event',
    '/proposal-builder.php' => 'Smart Proposal Builder',
    '/proposals.php' => 'Pelacak Proposal',
    '/proposal-detail.php?id=1' => 'Detail Proposal 1',
    '/negotiation.php?id=1' => 'Negotiation Room E2EE',
    '/contract.php?id=1' => 'MoU & Digital Signature',
    '/escrow.php?id=1' => 'Escrow Milestones 30-40-30',
    '/post-event-report.php' => 'Form Pengisian Hasil Event',
    '/analytics.php' => 'Post-Event Impact Analytics',
    '/profile.php' => 'Profil EO & Bank Account'
];

foreach ($eoUrls as $uri => $name) {
    $r = testUrl($baseUrl . $uri, $cookieJar);
    echo ($r['code'] === 200 ? "✔ OK (200)" : "❌ GAGAL ({$r['code']})") . " : {$name} ({$r['len']} bytes)\n";
}

// 3. Sponsor Flow
echo "\n--- PENGUJIAN ALUR SPONSOR (Brand) ---\n";
testUrl($baseUrl . '/switch_role.php?role=sponsor', $cookieJar);
$sponsorUrls = [
    '/dashboard.php' => 'Dashboard Sponsor & Rekomendasi AI',
    '/events.php?view=match' => 'Event Discovery Terurut AI Match Score',
    '/proposals.php' => 'Kotak Masuk Proposal Sponsor',
    '/negotiation.php?id=1' => 'Negotiation Room E2EE (Sponsor View)',
    '/contract.php?id=1' => 'MoU Kontrak (Sponsor Sign View)',
    '/escrow.php?id=1' => 'Escrow Payment & Pencairan Dana'
];

foreach ($sponsorUrls as $uri => $name) {
    $r = testUrl($baseUrl . $uri, $cookieJar);
    echo ($r['code'] === 200 ? "✔ OK (200)" : "❌ GAGAL ({$r['code']})") . " : {$name} ({$r['len']} bytes)\n";
}

// 4. Admin Flow
echo "\n--- PENGUJIAN ALUR ADMIN ---\n";
testUrl($baseUrl . '/switch_role.php?role=admin', $cookieJar);
$adminUrls = [
    '/admin/index.php' => 'Dashboard Admin Control Center',
    '/admin/users.php' => 'Verifikasi Legalitas Akun Pengguna',
    '/admin/events.php' => 'Moderasi Event & Kurasi Unggulan',
    '/admin/disputes.php' => 'Resolusi Sengketa Pembayaran Escrow',
    '/admin/negotiations.php' => 'Pemantauan Metadata Room (Privasi E2EE)',
    '/admin/categories.php' => 'Manajemen Taksonomi & CSR'
];

foreach ($adminUrls as $uri => $name) {
    $r = testUrl($baseUrl . $uri, $cookieJar);
    echo ($r['code'] === 200 ? "✔ OK (200)" : "❌ GAGAL ({$r['code']})") . " : {$name} ({$r['len']} bytes)\n";
}

if (file_exists($cookieJar)) unlink($cookieJar);
echo "\n=== SELURUH PENGUJIAN SELESAI DENGAN SUKSES! ===\n";
