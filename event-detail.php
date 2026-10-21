<?php
// event-detail.php - Detail Event & Paket Sponsorship
$eventId = (int)($_GET['id'] ?? 0);
if ($eventId <= 0) {
    header('Location: ' . BASE_URL . '/events.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Ambil detail event beserta profil EO
$stmt = $conn->prepare("
    SELECT e.*, u.name as eo_name, u.email as eo_email, u.phone as eo_phone,
           ep.organization_name, ep.description as eo_description, ep.city as eo_city,
           ep.website as eo_website, ep.instagram as eo_instagram, ep.total_events_held,
           ep.avg_rating as eo_rating, ep.track_record
    FROM events e
    JOIN users u ON e.eo_id = u.id
    LEFT JOIN eo_profiles ep ON ep.user_id = u.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    echo "<div class='p-12 text-center text-slate-500'>Event tidak ditemukan. <a href='" . BASE_URL . "/events.php' class='text-brand-indigo underline'>Kembali ke katalog</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Ambil 4 tier paket sponsorship (Bronze, Silver, Gold, Platinum)
$stmtPackages = $conn->prepare("SELECT * FROM sponsorship_packages WHERE event_id = ? ORDER BY price ASC");
$stmtPackages->bind_param("i", $eventId);
$stmtPackages->execute();
$packages = $stmtPackages->get_result()->fetch_all(MYSQLI_ASSOC);

// AI Match Score jika yang melihat adalah Sponsor
$currentUser = current_user();
$sponsorProfile = ($currentUser && $currentUser['role'] === 'sponsor') ? ($currentUser['profile'] ?? []) : null;
$match = calculate_match_score($event, $sponsorProfile);

$progressPct = min(100, round(($event['funds_raised'] / max(1, $event['sponsorship_target'])) * 100));

// Hitung estimasi ROI standar untuk tier Gold (atau paket ke-3)
$sampleGoldPrice = isset($packages[2]) ? (float)$packages[2]['price'] : 75000000;
$roiCalc = calculate_sponsor_roi($event['attendee_estimate'], 8, $event['attendee_estimate'] * 15, $sampleGoldPrice);
?>

<main class="flex-grow py-8 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6 font-medium">
            <a href="<?= BASE_URL ?>/index.php" class="hover:text-slate-600">Beranda</a>
            <span>/</span>
            <a href="<?= BASE_URL ?>/events.php" class="hover:text-slate-600">Eksplorasi Event</a>
            <span>/</span>
            <span class="text-slate-800 font-bold truncate max-w-xs"><?= clean_input($event['title']) ?></span>
        </nav>

        <!-- Top Hero Banner of Event -->
        <div class="sb-card overflow-hidden mb-8 border-0 shadow-lg">
            <div class="bg-gradient-to-r from-navy-950 via-navy-900 to-indigo-950 p-6 sm:p-10 text-white relative">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-white/20 backdrop-blur-md">
                            <?= clean_input($event['category']) ?>
                        </span>
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-500 text-white">
                            <?= strtoupper($event['status']) ?>
                        </span>
                    </div>

                    <?php if ($currentUser && $currentUser['role'] === 'sponsor'): ?>
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold">
                        <i class="fa-solid fa-sparkles text-amber-400"></i>
                        <span>AI Match Score: <?= $match['score'] ?>% Cocok</span>
                    </div>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight mb-3">
                    <?= clean_input($event['title']) ?>
                </h1>

                <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-slate-300">
                    <div><i class="fa-regular fa-calendar text-brand-indigo mr-1.5"></i><?= format_date($event['start_date']) ?> s/d <?= format_date($event['end_date']) ?></div>
                    <div><i class="fa-solid fa-location-dot text-rose-400 mr-1.5"></i><?= clean_input($event['location']) ?>, <?= clean_input($event['city']) ?></div>
                    <div><i class="fa-solid fa-users text-sky-400 mr-1.5"></i><?= number_format($event['attendee_estimate']) ?> Target Pengunjung</div>
                    <div><i class="fa-solid fa-user-check text-emerald-400 mr-1.5"></i>Oleh: <strong><?= clean_input($event['eo_name']) ?></strong></div>
                </div>
            </div>

            <!-- Funding Progress Bar Strip -->
            <div class="p-6 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex-1 w-full">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="font-bold text-slate-500">Progress Pendanaan Sponsorship:</span>
                        <span class="font-black text-brand-indigo text-sm"><?= format_rupiah($event['funds_raised']) ?> <span class="text-xs text-slate-400 font-normal">dari target <?= format_rupiah($event['sponsorship_target']) ?></span> (<?= $progressPct ?>%)</span>
                    </div>
                    <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-brand-indigo via-brand-purple to-pink-500 rounded-full" style="width: <?= $progressPct ?>%"></div>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
                    <?php if ($currentUser && $currentUser['role'] === 'sponsor'): ?>
                        <a href="<?= BASE_URL ?>/negotiation.php?event_id=<?= $event['id'] ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-white bg-brand-purple hover:bg-brand-purple/90 transition shadow-md shadow-brand-purple/20 text-xs flex items-center justify-center gap-2">
                            <i class="fa-solid fa-comments-dollar"></i> Ajukan Negosiasi
                        </a>
                    <?php elseif ($currentUser && $currentUser['role'] === 'eo' && $currentUser['id'] == $event['eo_id']): ?>
                        <a href="<?= BASE_URL ?>/proposal-builder.php?event_id=<?= $event['id'] ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 transition text-xs flex items-center justify-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Susun Proposal Smart
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php" class="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 transition text-xs flex items-center justify-center gap-2">
                            <i class="fa-solid fa-right-to-bracket"></i> Masuk untuk Bermitra
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Layout Grid: Detail Kiri & Profil EO Kanan -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Kolom Kiri: Deskripsi, Paket, Rundown (2 Cols) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Deskripsi & Target Audiens -->
                <div class="sb-card p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-base font-extrabold text-navy-900 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-brand-indigo"></i> Deskripsi Lengkap Acara
                        </h2>
                        <div class="text-xs text-slate-600 leading-relaxed space-y-2 whitespace-pre-line">
                            <?= clean_input($event['description']) ?>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Profil & Target Demografi Audiens</h3>
                        <div class="p-4 rounded-xl bg-indigo-50/60 border border-indigo-100 text-xs text-navy-900 leading-relaxed font-medium">
                            <i class="fa-solid fa-bullseye text-brand-indigo mr-1.5"></i>
                            <?= clean_input($event['target_audience']) ?>
                        </div>
                    </div>

                    <?php if (!empty($event['rundown'])): ?>
                    <div class="pt-6 border-t border-slate-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Agenda & Rundown Acara</h3>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 font-mono text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                            <?= clean_input($event['rundown']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Paket Sponsorship (Bronze, Silver, Gold, Platinum) -->
                <div>
                    <div class="mb-4">
                        <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Pilihan Kemitraan</span>
                        <h2 class="text-xl font-black text-navy-900 tracking-tight">Paket Sponsorship Tersedia</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih tier sponsorship yang sesuai dengan anggaran dan objektif aktivasi brand Anda.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($packages as $pkg): 
                            $isAvailable = $pkg['slots_taken'] < $pkg['quota'];
                            $tierColors = [
                                'Bronze'   => 'border-amber-700/30 bg-amber-50/40 text-amber-800',
                                'Silver'   => 'border-slate-300 bg-slate-100 text-slate-800',
                                'Gold'     => 'border-amber-400 bg-amber-50 text-amber-900',
                                'Platinum' => 'border-purple-300 bg-purple-50 text-purple-900'
                            ][$pkg['tier']] ?? 'border-slate-200 bg-white text-slate-800';
                        ?>
                        <div class="sb-card p-6 flex flex-col justify-between relative <?= $pkg['tier'] === 'Gold' ? 'ring-2 ring-brand-indigo shadow-md' : '' ?>">
                            <?php if ($pkg['tier'] === 'Gold'): ?>
                                <span class="absolute -top-3 right-4 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-brand-indigo text-white uppercase tracking-wider shadow-sm">
                                    Paling Diminati
                                </span>
                            <?php endif; ?>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider <?= $tierColors ?>">
                                        <?= clean_input($pkg['tier']) ?>
                                    </span>
                                    <span class="text-[11px] font-semibold <?= $isAvailable ? 'text-emerald-600' : 'text-rose-500' ?>">
                                        <?= $isAvailable ? "Tersisa " . ($pkg['quota'] - $pkg['slots_taken']) . " dari " . $pkg['quota'] . " Slot" : "Slot Penuh" ?>
                                    </span>
                                </div>

                                <h3 class="text-base font-black text-navy-900 mt-1"><?= clean_input($pkg['name']) ?></h3>
                                <div class="text-xl font-black text-brand-indigo mt-1 mb-4"><?= format_rupiah($pkg['price']) ?></div>

                                <div class="text-xs text-slate-600 space-y-2 border-t border-slate-100 pt-3 whitespace-pre-line leading-relaxed">
                                    <?= clean_input($pkg['benefits']) ?>
                                </div>
                            </div>

                            <div class="pt-6 mt-4 border-t border-slate-100">
                                <?php if ($currentUser && $currentUser['role'] === 'sponsor'): ?>
                                    <a href="<?= BASE_URL ?>/negotiation.php?event_id=<?= $event['id'] ?>&tier=<?= urlencode($pkg['tier']) ?>&price=<?= $pkg['price'] ?>" class="w-full py-2.5 rounded-xl font-bold text-xs text-center block text-white bg-brand-indigo hover:bg-brand-indigo/90 transition shadow-sm">
                                        Pilih & Ajukan Negosiasi
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/login.php" class="w-full py-2.5 rounded-xl font-bold text-xs text-center block bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        Masuk untuk Memilih
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Embedded Estimasi ROI Sponsor Box -->
                <div class="sb-card p-6 bg-gradient-to-br from-indigo-900 to-navy-900 text-white">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-300">Proyeksi B2B Media Value</span>
                            <h3 class="text-base font-black text-white">Kalkulasi ROI Sponsor (Tier Gold)</h3>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-emerald-400"><?= $roiCalc['roi_multiplier'] ?>x</span>
                            <span class="text-[10px] text-slate-300 block">Proyeksi ROI Multiplier</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 p-3.5 rounded-xl bg-white/10 backdrop-blur-md text-xs">
                        <div>
                            <span class="text-[10px] text-slate-300 block">Nilai Earned Media:</span>
                            <strong class="text-white text-sm"><?= format_rupiah($roiCalc['total_earned_media_value']) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-300 block">Impresi Digital:</span>
                            <strong class="text-white text-sm"><?= number_format($roiCalc['estimated_impressions']) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-300 block">Target Exposure Fisik:</span>
                            <strong class="text-white text-sm"><?= number_format($event['attendee_estimate']) ?> audiens</strong>
                        </div>
                    </div>
                    
                    <p class="text-[11px] text-slate-300 mt-3 italic">
                        *Perhitungan mengacu pada standar CPM industri media Indonesia (Rp 45.000 / 1k impresi online & Rp 25.000 per kehadiran tatap muka).
                    </p>
                </div>

            </div>

            <!-- Kolom Kanan: Profil EO, Kontak, & Keamanan (1 Col) -->
            <div class="space-y-6">
                
                <!-- Profil Organisasi EO -->
                <div class="sb-card p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Penyelenggara Event</h3>
                    
                    <div class="flex items-center gap-3.5 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-brand-indigo to-brand-purple text-white font-black text-lg flex items-center justify-center shadow-md">
                            <?= strtoupper(substr($event['eo_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-navy-900"><?= clean_input($event['eo_name']) ?></h4>
                            <div class="flex items-center gap-1.5 text-xs text-amber-500 font-bold mt-0.5">
                                <i class="fa-solid fa-star"></i>
                                <span><?= number_format($event['eo_rating'] ?? 4.9, 1) ?></span>
                                <span class="text-slate-400 font-normal">(<?= (int)($event['total_events_held'] ?? 10) ?> Event Sukses)</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        <?= clean_input($event['eo_description'] ?? 'Event organizer terverifikasi di SponsorBridge dengan rekam jejak pelaksanaan berstandar profesional.') ?>
                    </p>

                    <div class="space-y-2 text-xs text-slate-600 pt-3 border-t border-slate-100">
                        <?php if (!empty($event['eo_city'])): ?>
                            <div><i class="fa-solid fa-location-dot text-slate-400 mr-2 w-4"></i><?= clean_input($event['eo_city']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($event['eo_instagram'])): ?>
                            <div><i class="fa-brands fa-instagram text-slate-400 mr-2 w-4"></i><?= clean_input($event['eo_instagram']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($event['eo_website'])): ?>
                            <div><i class="fa-solid fa-globe text-slate-400 mr-2 w-4"></i><a href="<?= clean_input($event['eo_website']) ?>" target="_blank" class="text-brand-indigo hover:underline truncate"><?= clean_input($event['eo_website']) ?></a></div>
                        <?php endif; ?>
                    </div>

                    <a href="<?= BASE_URL ?>/profile.php?id=<?= $event['eo_id'] ?>" class="w-full mt-4 py-2 rounded-xl text-xs font-bold text-center block text-brand-indigo bg-indigo-50 hover:bg-brand-indigo hover:text-white transition">
                        Lihat Portofolio & Track Record EO
                    </a>
                </div>

                <!-- Jaminan Keamanan Escrow -->
                <div class="sb-card p-6 bg-slate-900 text-white space-y-4">
                    <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold">
                        <i class="fa-solid fa-shield-check text-base"></i>
                        <span>Sponsorship Dilindungi Escrow</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Dana sponsorship disimpan di rekening penampung resmi dan dicairkan bertahap (30% DP, 40% Kesiapan Venue & Izin, 30% Laporan Akhir).
                    </p>
                    <div class="space-y-2 text-[11px] text-slate-400 font-mono pt-2 border-t border-slate-800">
                        <div><i class="fa-solid fa-lock text-emerald-400 mr-1.5"></i> End-to-End Encrypted Chat</div>
                        <div><i class="fa-solid fa-file-signature text-blue-400 mr-1.5"></i> Legal MoU Generator</div>
                        <div><i class="fa-solid fa-scale-balanced text-amber-400 mr-1.5"></i> Mediasi Dispute Resmi</div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
