<?php
// proposal-detail.php - Detail Proposal & Aksi Review / Keputusan
$proposalId = (int)($_GET['id'] ?? 0);
if ($proposalId <= 0) {
    header('Location: ' . BASE_URL . '/proposals.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

// Ambil data proposal lengkap
$stmt = $conn->prepare("
    SELECT p.*, e.title as event_title, e.category as event_category, e.location as event_location,
           e.city as event_city, e.start_date, e.end_date, e.attendee_estimate, e.sponsorship_target,
           eo.name as eo_name, eo.email as eo_email, eo.phone as eo_phone,
           sp.name as sponsor_name, sp.email as sponsor_email,
           pkg.name as package_name, pkg.tier as package_tier
    FROM proposals p
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON p.eo_id = eo.id
    JOIN users sp ON p.sponsor_id = sp.id
    LEFT JOIN sponsorship_packages pkg ON p.package_id = pkg.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $proposalId);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    echo "<div class='p-12 text-center text-slate-500'>Proposal tidak ditemukan. <a href='" . BASE_URL . "/proposals.php' class='text-brand-indigo underline'>Kembali</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handler aksi Sponsor (Terima, Tolak, Buka Negosiasi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    
    if ($action === 'accept') {
        // Terima proposal -> Ubah status jadi 'Diterima' dan buat draf kontrak
        $conn->query("UPDATE proposals SET status = 'Diterima', updated_at = NOW() WHERE id = {$proposalId}");
        
        // Buat atau update kontrak
        $contractNum = 'SB-MOU-' . date('Ym') . '-' . sprintf('%03d', $proposalId);
        $terms = "PERJANJIAN KERJASAMA SPONSORSHIP RESMI\nNomor: {$contractNum}\n\nAntara:\nPIHAK PERTAMA: {$proposal['eo_name']} (Event Organizer)\nPIHAK KEDUA: {$proposal['sponsor_name']} (Sponsor Resmi)\n\nUntuk Event: {$proposal['event_title']}\nTotal Nilai Kesepakatan: Rp " . number_format($proposal['proposed_amount'], 0, ',', '.') . "\n\nPembayaran dilakukan melalui rekening escrow SponsorBridge bertahap 30% - 40% - 30%.";
        
        $stmtC = $conn->prepare("INSERT INTO contracts (proposal_id, contract_number, agreement_amount, terms_text, status) VALUES (?, ?, ?, ?, 'Menunggu Tanda Tangan EO')");
        $stmtC->bind_param("isds", $proposalId, $contractNum, $proposal['proposed_amount'], $terms);
        $stmtC->execute();
        $newContractId = $conn->insert_id;

        // Buat 3 Milestone Escrow
        $amt1 = $proposal['proposed_amount'] * 0.30;
        $amt2 = $proposal['proposed_amount'] * 0.40;
        $amt3 = $proposal['proposed_amount'] * 0.30;
        $conn->query("INSERT INTO escrow_milestones (contract_id, milestone_number, milestone_title, percentage, amount, status) VALUES 
            ({$newContractId}, 1, 'Pembayaran Uang Muka (DP 30%) Penandatanganan Kontrak', 30, {$amt1}, 'Pending'),
            ({$newContractId}, 2, 'Pencairan Tahap Kedua (40%) Kesiapan Venue & Izin', 40, {$amt2}, 'Pending'),
            ({$newContractId}, 3, 'Pelunasan Akhir (30%) Pasca Penyerahan Laporan LPJ', 30, {$amt3}, 'Pending')");

        create_notification($proposal['eo_id'], 'Proposal Sponsorship Diterima!', "Brand {$currentUser['name']} telah menerima proposal '{$proposal['title']}'. Silakan tanda tangani draf MoU.", "contract.php?id={$newContractId}");
        set_flash('success', 'Proposal berhasil disetujui! Draf Kontrak & Jadwal Escrow telah dibuat.');
        header("Location: " . BASE_URL . "/contract.php?id={$newContractId}");
        exit;

    } elseif ($action === 'open_negotiation') {
        // Buka negosiasi -> status 'Negosiasi' dan buat / redirect ke negotiation room
        $conn->query("UPDATE proposals SET status = 'Negosiasi', updated_at = NOW() WHERE id = {$proposalId}");
        
        // Cek apakah room sudah ada
        $stmtRoom = $conn->prepare("SELECT id FROM negotiation_rooms WHERE proposal_id = ?");
        $stmtRoom->bind_param("i", $proposalId);
        $stmtRoom->execute();
        $room = $stmtRoom->get_result()->fetch_assoc();
        
        $roomId = 0;
        if ($room) {
            $roomId = $room['id'];
        } else {
            $stmtNewRoom = $conn->prepare("INSERT INTO negotiation_rooms (proposal_id, eo_id, sponsor_id, status, current_offer_amount, current_tier) VALUES (?, ?, ?, 'Aktif', ?, ?)");
            $tierName = $proposal['package_tier'] ?? 'Custom';
            $stmtNewRoom->bind_param("iiids", $proposalId, $proposal['eo_id'], $proposal['sponsor_id'], $proposal['proposed_amount'], $tierName);
            $stmtNewRoom->execute();
            $roomId = $conn->insert_id;

            // Pesan sistem pembuka
            $conn->query("INSERT INTO negotiation_messages (room_id, sender_id, message, is_system) VALUES ({$roomId}, {$userId}, 'Ruang negosiasi dibuka oleh {$currentUser['name']}. Silakan diskusikan penyesuaian nominal penawaran dan paket benefit.', 1)");
        }

        create_notification($proposal['eo_id'], 'Negosiasi Baru Dibuka', "Sponsor {$currentUser['name']} telah membuka ruang negosiasi untuk proposal '{$proposal['title']}'.", "negotiation.php?id={$roomId}");
        header("Location: " . BASE_URL . "/negotiation.php?id={$roomId}");
        exit;

    } elseif ($action === 'reject') {
        $reason = clean_input($_POST['rejection_reason'] ?? 'Kriteria kemitraan belum sesuai dengan prioritas kampanye saat ini.');
        $stmtR = $conn->prepare("UPDATE proposals SET status = 'Ditolak', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmtR->bind_param("si", $reason, $proposalId);
        $stmtR->execute();

        create_notification($proposal['eo_id'], 'Proposal Sponsorship Ditolak', "Sponsor telah menolak proposal '{$proposal['title']}' dengan catatan: {$reason}", "proposal-detail.php?id={$proposalId}");
        set_flash('info', 'Proposal telah ditolak.');
        header("Location: " . BASE_URL . "/proposal-detail.php?id={$proposalId}");
        exit;
    }
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Breadcrumb & Back -->
            <div class="flex items-center justify-between">
                <a href="<?= BASE_URL ?>/proposals.php" class="text-xs font-bold text-slate-500 hover:text-navy-900 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Pelacak Proposal
                </a>
                <div>
                    <?= render_proposal_status_badge($proposal['status']) ?>
                </div>
            </div>

            <!-- Proposal Header Card -->
            <div class="sb-card p-6 sm:p-8 bg-white">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Dokumen Proposal B2B</span>
                        <h1 class="text-xl sm:text-2xl font-black text-navy-900 tracking-tight mt-1">
                            <?= clean_input($proposal['title']) ?>
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Diajukan oleh: <strong class="text-navy-900"><?= clean_input($proposal['eo_name']) ?></strong> kepada <strong class="text-navy-900"><?= clean_input($proposal['sponsor_name']) ?></strong>
                        </p>
                    </div>

                    <div class="text-right shrink-0">
                        <span class="text-[10px] text-slate-400 block font-semibold uppercase">Nominal Pengajuan:</span>
                        <div class="text-2xl font-black text-brand-indigo"><?= format_rupiah($proposal['proposed_amount']) ?></div>
                    </div>
                </div>

                <!-- Structured Proposal Content Sections -->
                <div class="space-y-6 pt-6 text-xs leading-relaxed text-slate-700">
                    
                    <!-- 1. Info Event -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Event:</span>
                            <strong class="text-navy-900"><?= clean_input($proposal['event_title']) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Kategori:</span>
                            <strong class="text-navy-900"><?= clean_input($proposal['event_category']) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Waktu:</span>
                            <strong class="text-navy-900"><?= format_date($proposal['start_date']) ?></strong>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Estimasi Audiens:</span>
                            <strong class="text-emerald-700 font-extrabold"><?= number_format($proposal['attendee_estimate']) ?> Pengunjung</strong>
                        </div>
                    </div>

                    <!-- 2. Latar Belakang & Sasaran Audiens -->
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-navy-900 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-align-left text-brand-indigo"></i> Latar Belakang & Urgensi Acara
                        </h3>
                        <p class="text-slate-600 bg-white p-4 rounded-xl border border-slate-100">
                            <?= clean_input($proposal['background']) ?>
                        </p>
                    </div>

                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-navy-900 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-bullseye text-rose-500"></i> Target & Karakteristik Audiens
                        </h3>
                        <p class="text-slate-600 bg-white p-4 rounded-xl border border-slate-100">
                            <?= clean_input($proposal['target_audience']) ?>
                        </p>
                    </div>

                    <!-- 3. Rencana Promosi Multichannel -->
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-navy-900 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-share-nodes text-sky-500"></i> Rencana Promosi Multichannel
                        </h3>
                        <p class="text-slate-600 bg-white p-4 rounded-xl border border-slate-100">
                            <?= clean_input($proposal['promotion_plan']) ?>
                        </p>
                    </div>

                    <!-- 4. Rincian Benefit Kemitraan -->
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-navy-900 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-gift text-amber-500"></i> Paket Benefit yang Ditawarkan
                        </h3>
                        <div class="p-4 rounded-xl bg-indigo-50/50 border border-indigo-100 text-slate-800 whitespace-pre-line leading-relaxed font-medium">
                            <?= clean_input($proposal['custom_benefits'] ?? 'Mengacu pada paket standar event.') ?>
                        </div>
                    </div>

                    <!-- 5. Estimasi ROI Nilai Kemitraan -->
                    <div class="p-4 rounded-xl bg-gradient-to-r from-navy-900 to-indigo-950 text-white flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-indigo-300 block">Proyeksi Earned Media Value (EMV)</span>
                            <span class="text-sm font-bold">Kalkulasi ROI: Estimasi multiplier return kemitraan</span>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-emerald-400"><?= $proposal['estimated_roi_pct'] ?>%</span>
                            <span class="text-[10px] text-slate-300 block">Proyeksi Nilai Balik</span>
                        </div>
                    </div>

                    <?php if (!empty($proposal['rejection_reason'])): ?>
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800">
                        <strong class="font-bold block mb-1">Catatan Penolakan:</strong>
                        <p><?= clean_input($proposal['rejection_reason']) ?></p>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Action Button Toolbar for Sponsor or EO -->
                <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <span class="text-xs text-slate-400">
                        ID Proposal: <strong>#PB-<?= sprintf('%04d', $proposal['id']) ?></strong>
                    </span>

                    <div class="flex items-center gap-2.5">
                        <?php if ($role === 'sponsor' && in_array($proposal['status'], ['Dikirim', 'Dibaca', 'Review'])): ?>
                            <!-- Sponsor Decision Buttons -->
                            <button type="button" onclick="openModal('rejectModal')" class="px-4 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition">
                                <i class="fa-solid fa-xmark mr-1"></i> Tolak
                            </button>
                            
                            <form method="POST" action="" class="inline">
                                <button type="submit" name="action" value="open_negotiation" class="px-4 py-2 rounded-xl text-xs font-bold text-brand-purple bg-purple-50 hover:bg-brand-purple hover:text-white transition shadow-2xs">
                                    <i class="fa-solid fa-comments-dollar mr-1"></i> Buka Negosiasi E2EE
                                </button>
                            </form>

                            <form method="POST" action="" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui proposal ini? Kesepakatan MoU akan otomatis dibuat.')">
                                <button type="submit" name="action" value="accept" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition">
                                    <i class="fa-solid fa-check mr-1"></i> Terima Penawaran
                                </button>
                            </form>

                        <?php elseif ($proposal['status'] === 'Negosiasi'): ?>
                            <a href="<?= BASE_URL ?>/negotiation.php?id=1" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-purple hover:bg-brand-purple/90 transition shadow-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-comments-dollar"></i> Masuk Negotiation Room
                            </a>
                        <?php elseif (in_array($proposal['status'], ['Diterima', 'Kontrak Ditandatangani'])): ?>
                            <a href="<?= BASE_URL ?>/contract.php?id=1" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition shadow-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-file-signature"></i> Lihat & Tanda Tangani Kontrak
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<!-- Modal Tolak Proposal -->
<div id="rejectModal" class="sb-modal hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 items-center justify-center p-4">
    <div class="sb-card max-w-md w-full p-6 bg-white shadow-2xl">
        <h3 class="text-base font-black text-navy-900 mb-2">Tolak Proposal Kemitraan</h3>
        <p class="text-xs text-slate-500 mb-4">Berikan alasan penolakan yang profesional agar pihak EO memahami kriteria perusahaan Anda.</p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="reject">
            <textarea name="rejection_reason" rows="3" required placeholder="Contoh: Alokasi anggaran sponsorship Q4 sudah penuh dialokasikan..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs mb-4 focus:ring-2 focus:ring-rose-500/30 focus:border-rose-500"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700">Konfirmasi Penolakan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
