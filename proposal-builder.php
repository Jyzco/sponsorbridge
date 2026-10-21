<?php
// proposal-builder.php - Smart Proposal Builder dengan Struktur Lengkap & Kalkulator ROI
$page_title = "Smart Proposal Builder";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_role('eo');
$currentUser = current_user();

$selectedEventId = (int)($_GET['event_id'] ?? 0);
$selectedSponsorId = (int)($_GET['sponsor_id'] ?? 0);

// Ambil daftar event milik EO ini
$stmtEvents = $conn->prepare("SELECT id, title, category, attendee_estimate, sponsorship_target FROM events WHERE eo_id = ? ORDER BY created_at DESC");
$stmtEvents->bind_param("i", $currentUser['id']);
$stmtEvents->execute();
$myEvents = $stmtEvents->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil daftar sponsor dari database
$allSponsors = $conn->query("
    SELECT u.id, u.name, sp.company_name, sp.industry, sp.csr_focus, sp.min_budget, sp.max_budget 
    FROM users u 
    JOIN sponsor_profiles sp ON u.id = sp.user_id 
    ORDER BY sp.max_budget DESC
")->fetch_all(MYSQLI_ASSOC);

// Jika ada event terpilih, ambil paketnya
$packages = [];
if ($selectedEventId > 0) {
    $stmtPkg = $conn->prepare("SELECT * FROM sponsorship_packages WHERE event_id = ? ORDER BY price ASC");
    $stmtPkg->bind_param("i", $selectedEventId);
    $stmtPkg->execute();
    $packages = $stmtPkg->get_result()->fetch_all(MYSQLI_ASSOC);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $sponsorId = (int)($_POST['sponsor_id'] ?? 0);
    $packageId = (int)($_POST['package_id'] ?? 0);
    $title = clean_input($_POST['title'] ?? '');
    $background = clean_input($_POST['background'] ?? '');
    $targetAudience = clean_input($_POST['target_audience'] ?? '');
    $promotionPlan = clean_input($_POST['promotion_plan'] ?? '');
    $customBenefits = clean_input($_POST['custom_benefits'] ?? '');
    $proposedAmount = (float)($_POST['proposed_amount'] ?? 0);
    $estimatedRoiPct = (float)($_POST['estimated_roi_pct'] ?? 250);
    $status = clean_input($_POST['action'] ?? 'Dikirim'); // 'Draft' atau 'Dikirim'

    if ($eventId <= 0 || $sponsorId <= 0 || empty($title) || $proposedAmount <= 0) {
        $error = 'Harap pilih event, brand tujuan, isi judul proposal, dan nominal penawaran.';
    } else {
        $stmtInsert = $conn->prepare("
            INSERT INTO proposals (event_id, eo_id, sponsor_id, package_id, title, background, target_audience, promotion_plan, custom_benefits, proposed_amount, estimated_roi_pct, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->bind_param("iiiisssssdds", $eventId, $currentUser['id'], $sponsorId, $packageId, $title, $background, $targetAudience, $promotionPlan, $customBenefits, $proposedAmount, $estimatedRoiPct, $status);

        if ($stmtInsert->execute()) {
            $newPropId = $conn->insert_id;

            // Beri notifikasi kepada sponsor terpilih
            if ($status === 'Dikirim') {
                create_notification($sponsorId, 'Proposal Kemitraan Baru Masuk', "EO {$currentUser['name']} telah mengirimkan proposal kemitraan: '{$title}'.", "proposal-detail.php?id={$newPropId}");
            }

            set_flash('success', "Proposal <strong>{$title}</strong> berhasil disimpan dengan status: <strong>{$status}</strong>!");
            header('Location: ' . BASE_URL . '/proposal-detail.php?id=' . $newPropId);
            exit;
        } else {
            $error = 'Gagal menyimpan proposal: ' . $conn->error;
        }
    }
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto">
            
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Smart Proposal Builder</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Penyusun Proposal Sponsorship Terstandar B2B</h1>
                    <p class="text-xs text-slate-500 mt-1">Susun proposal profesional dengan 8 struktur lengkap, simulasi ROI terukur, dan ajukan langsung ke brand partner.</p>
                </div>
                <a href="<?= BASE_URL ?>/proposals.php" class="px-4 py-2 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                    <i class="fa-solid fa-list-check mr-1"></i> Pelacak Proposal
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
                    <span><?= clean_input($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Form (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="sb-card p-6 sm:p-8 bg-white">
                        <form method="POST" action="" id="proposalForm" class="space-y-6">
                            
                            <!-- 1. Informasi Event & Sponsor Tujuan -->
                            <div class="space-y-4">
                                <h3 class="text-sm font-black text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-brand-indigo text-white text-xs flex items-center justify-center font-black">1</span>
                                    Pilih Event & Brand Target
                                </h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="event_id">
                                            Event Yang Diajukan *
                                        </label>
                                        <select id="event_id" name="event_id" required onchange="window.location.href='?event_id=' + this.value + '&sponsor_id=<?= $selectedSponsorId ?>'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                                            <option value="">Pilih Event Anda</option>
                                            <?php foreach ($myEvents as $me): ?>
                                                <option value="<?= $me['id'] ?>" <?= $selectedEventId == $me['id'] ? 'selected' : '' ?>>
                                                    <?= clean_input($me['title']) ?> (<?= number_format($me['attendee_estimate']) ?> Peserta)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="sponsor_id">
                                            Brand / Perusahaan Target *
                                        </label>
                                        <select id="sponsor_id" name="sponsor_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                                            <option value="">Pilih Brand Sponsor</option>
                                            <?php foreach ($allSponsors as $as): ?>
                                                <option value="<?= $as['id'] ?>" <?= $selectedSponsorId == $as['id'] ? 'selected' : '' ?>>
                                                    <?= clean_input($as['company_name']) ?> (<?= clean_input($as['industry']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="title">
                                        Judul Proposal Kemitraan *
                                    </label>
                                    <input type="text" id="title" name="title" required placeholder="Contoh: Proposal Kemitraan Eksklusif Jakarta Tech Summit 2026 bersama PT TelkoNusa"
                                        value="<?= isset($_POST['title']) ? clean_input($_POST['title']) : '' ?>"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                                </div>
                            </div>

                            <!-- 2. Latar Belakang & Tujuan -->
                            <div class="space-y-4 pt-2">
                                <h3 class="text-sm font-black text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-brand-indigo text-white text-xs flex items-center justify-center font-black">2</span>
                                    Latar Belakang & Tujuan Event
                                </h3>
                                <textarea name="background" rows="3" required placeholder="Jelaskan signifikansi event, urgensi isu yang diangkat, dan mengapa kemitraan ini strategis bagi kedua pihak..."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"><?= isset($_POST['background']) ? clean_input($_POST['background']) : '' ?></textarea>
                            </div>

                            <!-- 3. Target Audiens -->
                            <div class="space-y-4 pt-2">
                                <h3 class="text-sm font-black text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-brand-indigo text-white text-xs flex items-center justify-center font-black">3</span>
                                    Target Audiens & Demografi Pengunjung
                                </h3>
                                <textarea name="target_audience" rows="2.5" required placeholder="Jelaskan demografi usia, profesi, domisili, minat, dan daya beli pengunjung yang menjadi sasaran..."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"><?= isset($_POST['target_audience']) ? clean_input($_POST['target_audience']) : '' ?></textarea>
                            </div>

                            <!-- 4. Rencana Promosi Multichannel -->
                            <div class="space-y-4 pt-2">
                                <h3 class="text-sm font-black text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-brand-indigo text-white text-xs flex items-center justify-center font-black">4</span>
                                    Rencana Promosi Multichannel
                                </h3>
                                <textarea name="promotion_plan" rows="3" required placeholder="Rincikan strategi promosi: Media sosial (IG, TikTok), Billboard/OOH, Media partner nasional, Press release, Email blast, dan aktivasi offline..."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"><?= isset($_POST['promotion_plan']) ? clean_input($_POST['promotion_plan']) : '' ?></textarea>
                            </div>

                            <!-- 5. Paket Sponsorship & Nominal Penawaran -->
                            <div class="space-y-4 pt-2">
                                <h3 class="text-sm font-black text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-brand-indigo text-white text-xs flex items-center justify-center font-black">5</span>
                                    Paket Sponsorship & Nominal Penawaran
                                </h3>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="package_id">
                                            Tier Acuan
                                        </label>
                                        <select id="package_id" name="package_id" onchange="updateProposedPrice(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                                            <option value="">Pilih Paket Standar (Opsional)</option>
                                            <?php foreach ($packages as $p): ?>
                                                <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-benefits="<?= htmlspecialchars($p['benefits']) ?>">
                                                    <?= clean_input($p['tier']) ?> - <?= format_rupiah($p['price']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="proposed_amount">
                                            Nominal Penawaran Yang Diajukan (Rp) *
                                        </label>
                                        <input type="number" id="proposed_amount" name="proposed_amount" required min="1000000" placeholder="75000000"
                                            value="<?= isset($_POST['proposed_amount']) ? clean_input($_POST['proposed_amount']) : '75000000' ?>"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-extrabold focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="custom_benefits">
                                        Rincian Benefit Khusus untuk Brand Target *
                                    </label>
                                    <textarea id="custom_benefits" name="custom_benefits" rows="4" placeholder="• Penempatan logo dominan pada seluruh materi digital & banner panggung&#10;• Booth 3x3 meter di area strategis&#10;• Sesi presentasi produk 15 menit&#10;• MC ad-libs 5x per hari"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"><?= isset($_POST['custom_benefits']) ? clean_input($_POST['custom_benefits']) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Hidden Field Estimasi ROI -->
                            <input type="hidden" name="estimated_roi_pct" id="hiddenRoiPct" value="320">

                            <!-- Tombol Aksi Simpan Draft atau Kirim -->
                            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                                <button type="submit" name="action" value="Draft" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                    <i class="fa-regular fa-file mr-1"></i> Simpan Sebagai Draft
                                </button>
                                <button type="submit" name="action" value="Dikirim" class="px-6 py-2.5 rounded-xl font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-md shadow-brand-indigo/25 transition text-xs flex items-center gap-1.5">
                                    <i class="fa-solid fa-paper-plane"></i> Kirim Proposal ke Brand
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Right Column: Interactive Sponsor ROI Calculator Widget (1 Col) -->
                <div class="space-y-6">
                    <div class="sb-card p-6 bg-gradient-to-br from-navy-900 to-indigo-950 text-white sticky top-20">
                        <div class="flex items-center gap-2 text-indigo-300 text-xs font-bold uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-calculator text-amber-400"></i> Smart ROI Calculator
                        </div>
                        <h3 class="text-base font-extrabold text-white mb-1">Simulasi Proyeksi ROI Sponsor</h3>
                        <p class="text-[11px] text-slate-300 mb-5 leading-relaxed">Sesuaikan parameter untuk menghitung estimasi nilai earned media sponsor.</p>

                        <div class="space-y-4 text-xs">
                            <div>
                                <label class="text-[10px] text-slate-300 block font-semibold mb-1">Estimasi Peserta Fisik:</label>
                                <input type="number" id="roiAttendees" value="12000" class="w-full px-3 py-2 rounded-lg bg-navy-800 border border-navy-700 text-white text-xs font-bold">
                            </div>

                            <div>
                                <label class="text-[10px] text-slate-300 block font-semibold mb-1">Jumlah Postingan Medsos Resmi:</label>
                                <input type="number" id="roiSocialPosts" value="8" class="w-full px-3 py-2 rounded-lg bg-navy-800 border border-navy-700 text-white text-xs font-bold">
                            </div>

                            <div>
                                <label class="text-[10px] text-slate-300 block font-semibold mb-1">Proyeksi Total Impresi Digital:</label>
                                <input type="number" id="roiImpressions" value="250000" class="w-full px-3 py-2 rounded-lg bg-navy-800 border border-navy-700 text-white text-xs font-bold">
                            </div>

                            <div>
                                <label class="text-[10px] text-slate-300 block font-semibold mb-1">Nominal Paket (Rp):</label>
                                <input type="number" id="roiPackageCost" value="75000000" class="w-full px-3 py-2 rounded-lg bg-navy-800 border border-navy-700 text-white text-xs font-bold">
                            </div>
                        </div>

                        <!-- Hasil Kalkulasi Live -->
                        <div class="mt-6 pt-5 border-t border-navy-700/80 space-y-2.5">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400">Nilai Media Online (CPM):</span>
                                <span class="font-bold text-slate-200" id="resOnlineValue">Rp 11.250.000</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400">Nilai Eksposur Fisik:</span>
                                <span class="font-bold text-slate-200" id="resOnsiteValue">Rp 300.000.000</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400">Nilai Konten Medsos:</span>
                                <span class="font-bold text-slate-200" id="resSocialValue">Rp 1.200.000</span>
                            </div>
                            <div class="flex justify-between text-xs font-bold pt-2 border-t border-navy-700">
                                <span class="text-indigo-300">Total Earned Media:</span>
                                <span class="text-white text-sm" id="resTotalMediaValue">Rp 312.450.000</span>
                            </div>

                            <!-- ROI Multiplier Badge -->
                            <div class="p-3 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-between mt-3">
                                <div>
                                    <span class="text-[10px] text-slate-300 block">Proyeksi Multiplier ROI:</span>
                                    <div class="text-2xl font-black text-emerald-400" id="resRoiMultiplier">4.17x</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-300 block">Pertumbuhan Nilai:</span>
                                    <div class="text-base font-extrabold text-emerald-400" id="resRoiPercentage">+316.6%</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<script>
function updateProposedPrice(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const price = opt.getAttribute('data-price');
    const benefits = opt.getAttribute('data-benefits');
    if (price) {
        document.getElementById('proposed_amount').value = price;
        document.getElementById('roiPackageCost').value = price;
        // Trigger ROI calculation update
        document.getElementById('roiPackageCost').dispatchEvent(new Event('input'));
    }
    if (benefits) {
        document.getElementById('custom_benefits').value = benefits;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
