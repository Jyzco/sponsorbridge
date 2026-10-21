<?php
// includes/functions.php - Fungsi Pembantu Umum, Format, Sanitasi, & Kalkulasi

require_once __DIR__ . '/db.php';

/**
 * Sanitasi string input dasar untuk mencegah XSS & injeksi
 */
function clean_input(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke format mata uang Rupiah
 */
function format_rupiah($amount): string {
    $num = (float)$amount;
    return 'Rp ' . number_format($num, 0, ',', '.');
}

/**
 * Format tanggal ke Bahasa Indonesia (contoh: 15 Okt 2026)
 */
function format_date(?string $dateStr): string {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agt', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $day = date('d', $time);
    $monthNum = (int)date('m', $time);
    $year = date('Y', $time);
    return "$day {$months[$monthNum]} $year";
}

/**
 * Format tanggal & waktu ke Bahasa Indonesia (contoh: 15 Okt 2026, 14:30 WIB)
 */
function format_datetime(?string $datetimeStr): string {
    if (!$datetimeStr) return '-';
    $time = strtotime($datetimeStr);
    return format_date($datetimeStr) . ', ' . date('H:i', $time) . ' WIB';
}

/**
 * Tampilkan label waktu relatif (contoh: "10 menit yang lalu")
 */
function time_ago(?string $datetime): string {
    if (!$datetime) return '';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 2592000) return floor($diff / 86400) . ' hari lalu';
    return format_date($datetime);
}

/**
 * Render Badge Status Proposal
 */
function render_proposal_status_badge(string $status): string {
    $classes = [
        'Draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'Dikirim' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Dibaca' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Review' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Negosiasi' => 'bg-purple-50 text-purple-700 border-purple-200 animate-pulse',
        'Diterima' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Ditolak' => 'bg-rose-50 text-rose-700 border-rose-200',
        'Kontrak Ditandatangani' => 'bg-teal-50 text-teal-700 border-teal-200 font-semibold',
        'Selesai' => 'bg-emerald-100 text-emerald-800 border-emerald-300'
    ];
    $badgeClass = $classes[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
    return "<span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border {$badgeClass}\">
        <span class=\"w-1.5 h-1.5 rounded-full bg-current\"></span>
        {$status}
    </span>";
}

/**
 * Render Badge Status Kontrak
 */
function render_contract_status_badge(string $status): string {
    $classes = [
        'Draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'Menunggu Tanda Tangan EO' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Menunggu Tanda Tangan Sponsor' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Aktif' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Selesai' => 'bg-slate-100 text-slate-800 border-slate-300'
    ];
    $badgeClass = $classes[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
    return "<span class=\"inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border {$badgeClass}\">
        <span class=\"w-2 h-2 rounded-full bg-current\"></span>
        {$status}
    </span>";
}

/**
 * Render Badge Status Milestone Escrow
 */
function render_escrow_milestone_badge(string $status): string {
    $classes = [
        'Pending' => 'bg-slate-100 text-slate-600 border-slate-200',
        'Dana Masuk' => 'bg-blue-50 text-blue-700 border-blue-200',
        'Diverifikasi' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Dicairkan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Disengketakan' => 'bg-rose-50 text-rose-700 border-rose-200'
    ];
    $badgeClass = $classes[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
    return "<span class=\"inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {$badgeClass}\">
        {$status}
    </span>";
}

/**
 * Algoritma Cerdas Match Score (Kecocokan Sponsor & Event)
 */
function calculate_match_score(array $event, ?array $sponsorProfile): array {
    if (!$sponsorProfile) {
        return ['score' => 85, 'reason' => 'Kategori umum berpotensi tinggi'];
    }

    $score = 70;
    $reasons = [];

    // 1. Cek Kesesuaian Kategori & Industri
    $eventCat = strtolower($event['category'] ?? '');
    $sponsorInd = strtolower($sponsorProfile['industry'] ?? '');
    
    if (strpos($sponsorInd, 'telko') !== false && (strpos($eventCat, 'tekno') !== false || strpos($eventCat, 'gaming') !== false || strpos($eventCat, 'musik') !== false)) {
        $score += 15;
        $reasons[] = 'Sinergi tinggi audiens digital & pemuda';
    } elseif (strpos($sponsorInd, 'bank') !== false && (strpos($eventCat, 'musik') !== false || strpos($eventCat, 'bisnis') !== false || strpos($eventCat, 'kuliner') !== false)) {
        $score += 15;
        $reasons[] = 'Cocok untuk aktivasi transaksi cashless & akuisisi nasabah';
    } elseif (strpos($sponsorInd, 'minuman') !== false && (strpos($eventCat, 'olahraga') !== false || strpos($eventCat, 'musik') !== false)) {
        $score += 18;
        $reasons[] = 'Kesesuaian ideal hidrasi di event fisik berskala besar';
    } elseif (strpos($sponsorInd, 'cloud') !== false && strpos($eventCat, 'tekno') !== false) {
        $score += 20;
        $reasons[] = 'Target audiens developer & startup 100% presisi';
    } else {
        $score += 8;
    }

    // 2. Cek Budget Alignment
    $targetBudget = (float)($event['sponsorship_target'] ?? 0);
    $sponsorMaxBudget = (float)($sponsorProfile['max_budget'] ?? 1000000000);
    $sponsorMinBudget = (float)($sponsorProfile['min_budget'] ?? 10000000);

    if ($targetBudget >= $sponsorMinBudget && $targetBudget <= $sponsorMaxBudget) {
        $score += 10;
        $reasons[] = 'Nilai sponsorship dalam range alokasi anggaran';
    }

    // 3. Kesesuaian CSR Focus
    $csrFocus = strtolower($sponsorProfile['csr_focus'] ?? '');
    if (!empty($csrFocus)) {
        if ((strpos($csrFocus, 'lingkungan') !== false || strpos($csrFocus, 'emisi') !== false) && strpos($eventCat, 'lingkungan') !== false) {
            $score += 12;
            $reasons[] = 'Sangat selaras dengan pilar program CSR keberlanjutan';
        } elseif ((strpos($csrFocus, 'digital') !== false || strpos($csrFocus, 'pemuda') !== false) && (strpos($eventCat, 'tekno') !== false || strpos($eventCat, 'kampus') !== false)) {
            $score += 10;
            $reasons[] = 'Mendukung agenda CSR edukasi & pemuda';
        }
    }

    if ($score > 98) $score = 98;
    if ($score < 60) $score = 65;

    $primaryReason = !empty($reasons) ? implode('. ', $reasons) : 'Event populer dengan demografi audiens luas';

    return [
        'score' => $score,
        'reason' => $primaryReason
    ];
}

/**
 * Kalkulator Estimasi ROI Sponsor
 */
function calculate_sponsor_roi(int $attendees, int $socialPosts, int $estimatedImpressions, float $packageCost): array {
    // Patokan standar B2B Media Value di Indonesia:
    // Nilai impresi online: CPM Rp 45.000 per 1.000 impresi
    // Nilai eksposur fisik tatap muka: Rp 25.000 per attendee
    // Nilai engagement konten: Rp 150.000 per post media sosial resmi
    
    $onlineMediaValue = ($estimatedImpressions / 1000) * 45000;
    $onsiteExposureValue = $attendees * 25000;
    $socialContentValue = $socialPosts * 150000;
    $totalEarnedMediaValue = $onlineMediaValue + $onsiteExposureValue + $socialContentValue;

    $packageCost = max(1, $packageCost);
    $roiMultiplier = round($totalEarnedMediaValue / $packageCost, 2);
    $roiPercentage = round((($totalEarnedMediaValue - $packageCost) / $packageCost) * 100, 1);

    return [
        'attendees' => $attendees,
        'estimated_impressions' => $estimatedImpressions,
        'online_media_value' => $onlineMediaValue,
        'onsite_exposure_value' => $onsiteExposureValue,
        'social_content_value' => $socialContentValue,
        'total_earned_media_value' => $totalEarnedMediaValue,
        'roi_multiplier' => $roiMultiplier,
        'roi_percentage' => $roiPercentage
    ];
}

/**
 * Ambil notifikasi unread untuk pengguna
 */
function get_unread_notifications_count(int $userId): int {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return (int)($res['total'] ?? 0);
}

/**
 * Ambil list notifikasi pengguna
 */
function get_user_notifications(int $userId, int $limit = 5): array {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Tambah notifikasi baru
 */
function create_notification(int $userId, string $title, string $message, string $link = ''): bool {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, is_read) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("isss", $userId, $title, $message, $link);
    return $stmt->execute();
}
