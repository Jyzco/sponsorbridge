<?php
// events.php - Event Discovery & Katalog Terfilter
$page_title = "Eksplorasi Event & Peluang Sponsorship";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$currentUser = current_user();
$sponsorProfile = ($currentUser && $currentUser['role'] === 'sponsor') ? ($currentUser['profile'] ?? []) : null;

// Filter Parameters (dengan sanitasi)
$search = clean_input($_GET['search'] ?? '');
$category = clean_input($_GET['category'] ?? '');
$city = clean_input($_GET['city'] ?? '');
$minAttendees = (int)($_GET['min_attendees'] ?? 0);
$maxBudget = (float)($_GET['max_budget'] ?? 0);
$viewMatch = clean_input($_GET['view'] ?? '') === 'match';

// Bangun query dinamis menggunakan MySQLi
$sql = "SELECT e.*, u.name as eo_name, ep.logo as eo_logo, ep.avg_rating as eo_rating
        FROM events e
        JOIN users u ON e.eo_id = u.id
        LEFT JOIN eo_profiles ep ON ep.user_id = u.id
        WHERE e.status IN ('open', 'completed')";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.target_audience LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = &$likeSearch;
    $params[] = &$likeSearch;
    $params[] = &$likeSearch;
    $types .= "sss";
}

if (!empty($category)) {
    $sql .= " AND e.category = ?";
    $params[] = &$category;
    $types .= "s";
}

if (!empty($city)) {
    $sql .= " AND e.city = ?";
    $params[] = &$city;
    $types .= "s";
}

if ($minAttendees > 0) {
    $sql .= " AND e.attendee_estimate >= ?";
    $params[] = &$minAttendees;
    $types .= "i";
}

if ($maxBudget > 0) {
    $sql .= " AND e.sponsorship_target <= ?";
    $params[] = &$maxBudget;
    $types .= "d";
}

$sql .= " ORDER BY e.is_featured DESC, e.start_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung Match Score jika user adalah Sponsor
foreach ($events as &$ev) {
    $match = calculate_match_score($ev, $sponsorProfile);
    $ev['match_score'] = $match['score'];
    $ev['match_reason'] = $match['reason'];
}
unset($ev);

// Jika viewMatch aktif, urutkan berdasarkan match score tertinggi
if ($viewMatch) {
    usort($events, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
}

// Ambil daftar kategori & kota unik untuk filter
$categoriesList = $conn->query("SELECT DISTINCT category FROM events WHERE category != '' ORDER BY category ASC")->fetch_all(MYSQLI_ASSOC);
$citiesList = $conn->query("SELECT DISTINCT city FROM events WHERE city != '' ORDER BY city ASC")->fetch_all(MYSQLI_ASSOC);
?>

<main class="flex-grow py-8 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Page -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Katalog Kemitraan</span>
                <h1 class="text-2xl sm:text-3xl font-black text-navy-900 tracking-tight mt-1">Eksplorasi Event Terverifikasi</h1>
                <p class="text-xs text-slate-500 mt-1">Temukan event yang tepat untuk eksposur brand, aktivasi konsumen, atau program CSR perusahaan Anda.</p>
            </div>
            
            <?php if ($currentUser && $currentUser['role'] === 'sponsor'): ?>
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>/events.php?view=match" class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 <?= $viewMatch ? 'bg-brand-purple text-white shadow-md' : 'bg-white border border-slate-200 text-slate-700 hover:border-brand-purple' ?>">
                    <i class="fa-solid fa-sparkles text-amber-400"></i> Rekomendasi AI Match <?= $viewMatch ? '(Aktif)' : '' ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Bar Interaktif -->
        <div class="sb-card p-5 mb-8 bg-white">
            <form method="GET" action="" class="space-y-4">
                <?php if ($viewMatch): ?><input type="hidden" name="view" value="match"><?php endif; ?>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Kata Kunci Pencarian -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Cari Nama / Deskripsi</label>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                            <input type="text" name="search" value="<?= clean_input($search) ?>" placeholder="Contoh: AI, Musik, Marathon..." 
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                        </div>
                    </div>

                    <!-- Filter Kategori -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Kategori Event</label>
                        <select name="category" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categoriesList as $c): ?>
                                <option value="<?= clean_input($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>>
                                    <?= clean_input($c['category']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Kota / Lokasi -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Kota / Lokasi</label>
                        <select name="city" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                            <option value="">Semua Kota</option>
                            <?php foreach ($citiesList as $ct): ?>
                                <option value="<?= clean_input($ct['city']) ?>" <?= $city === $ct['city'] ? 'selected' : '' ?>>
                                    <?= clean_input($ct['city']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filter Target Pengunjung -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Min. Estimasi Peserta</label>
                        <select name="min_attendees" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                            <option value="0">Berapa saja</option>
                            <option value="5000" <?= $minAttendees == 5000 ? 'selected' : '' ?>>5.000+ Peserta</option>
                            <option value="10000" <?= $minAttendees == 10000 ? 'selected' : '' ?>>10.000+ Peserta</option>
                            <option value="20000" <?= $minAttendees == 20000 ? 'selected' : '' ?>>20.000+ Peserta</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <span class="text-xs text-slate-500 font-medium">
                        Ditemukan <strong><?= count($events) ?></strong> event yang sesuai kriteria.
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/events.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Reset Filter
                        </a>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm transition">
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Event Cards Grid -->
        <?php if (empty($events)): ?>
            <div class="sb-card p-12 text-center">
                <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300 mb-3"></i>
                <h3 class="text-base font-bold text-navy-900">Tidak ada event yang cocok dengan filter</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Coba ubah kata kunci atau reset filter untuk melihat event lainnya.</p>
                <a href="<?= BASE_URL ?>/events.php" class="mt-4 inline-block px-4 py-2 bg-brand-indigo text-white text-xs font-bold rounded-xl">Lihat Semua Event</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($events as $ev): 
                    $progressPct = min(100, round(($ev['funds_raised'] / max(1, $ev['sponsorship_target'])) * 100));
                ?>
                <div class="sb-card flex flex-col justify-between overflow-hidden group hover:border-brand-indigo/50">
                    <div>
                        <!-- Header Banner -->
                        <div class="relative h-44 bg-gradient-to-br from-navy-950 via-navy-900 to-indigo-950 p-5 flex flex-col justify-between text-white">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/20 backdrop-blur-md border border-white/30 text-white uppercase tracking-wider">
                                    <?= clean_input($ev['category']) ?>
                                </span>
                                <?php if ($currentUser && $currentUser['role'] === 'sponsor'): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-500 text-white shadow-sm flex items-center gap-1">
                                        <i class="fa-solid fa-sparkles text-[9px]"></i> <?= $ev['match_score'] ?>% Cocok
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/90 text-white">
                                        <?= clean_input($ev['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div>
                                <span class="text-[11px] text-slate-300 block mb-1">
                                    <i class="fa-regular fa-calendar mr-1"></i><?= format_date($ev['start_date']) ?> - <?= format_date($ev['end_date']) ?>
                                </span>
                                <h3 class="text-base font-extrabold text-white group-hover:text-indigo-200 transition line-clamp-2">
                                    <?= clean_input($ev['title']) ?>
                                </h3>
                            </div>
                        </div>

                        <!-- Content Body -->
                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] text-brand-indigo border border-slate-200">
                                        <?= strtoupper(substr($ev['eo_name'], 0, 1)) ?>
                                    </div>
                                    <span class="font-medium text-slate-700 truncate max-w-[150px]"><?= clean_input($ev['eo_name']) ?></span>
                                </div>
                                <span class="text-slate-500 font-semibold"><i class="fa-solid fa-location-dot text-slate-400 mr-1"></i><?= clean_input($ev['city']) ?></span>
                            </div>

                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                <?= clean_input($ev['description']) ?>
                            </p>

                            <?php if ($currentUser && $currentUser['role'] === 'sponsor'): ?>
                            <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-100 text-[11px] text-purple-900">
                                <strong class="font-bold">Analisis AI:</strong> <?= clean_input($ev['match_reason']) ?>
                            </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-2 gap-2 text-xs py-2.5 border-y border-slate-100">
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-semibold">Estimasi Peserta:</span>
                                    <strong class="text-navy-900"><i class="fa-solid fa-users text-slate-400 mr-1"></i><?= number_format($ev['attendee_estimate']) ?></strong>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-semibold">Target Sponsorship:</span>
                                    <strong class="text-emerald-600"><?= format_rupiah($ev['sponsorship_target']) ?></strong>
                                </div>
                            </div>

                            <!-- Progress Funding -->
                            <div>
                                <div class="flex justify-between text-[11px] mb-1">
                                    <span class="text-slate-400">Terkumpul: <?= format_rupiah($ev['funds_raised']) ?></span>
                                    <span class="font-bold text-brand-indigo"><?= $progressPct ?>%</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-brand-indigo to-brand-purple rounded-full" style="width: <?= $progressPct ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="p-5 pt-0">
                        <a href="<?= BASE_URL ?>/event-detail.php?id=<?= $ev['id'] ?>" class="w-full py-2.5 rounded-xl text-xs font-bold text-center block text-white bg-brand-indigo hover:bg-brand-indigo/90 transition shadow-sm">
                            Lihat Paket & Detail Kemitraan
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
