<?php
// escrow.php - Halaman Escrow Payment dengan Pembayaran Bertahap 3 Milestone (30% - 40% - 30%)
$page_title = "Pusat Pembayaran Escrow Bertahap";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

$contractId = (int)($_GET['id'] ?? 1);

// Ambil data kontrak & milestones
$stmt = $conn->prepare("
    SELECT c.*, p.title as proposal_title, p.eo_id, p.sponsor_id,
           e.title as event_title, e.category, e.start_date, e.end_date,
           eo.name as eo_name, ep.bank_name, ep.bank_account, ep.bank_holder,
           sp.name as sponsor_name
    FROM contracts c
    JOIN proposals p ON c.proposal_id = p.id
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON p.eo_id = eo.id
    JOIN users sp ON p.sponsor_id = sp.id
    LEFT JOIN eo_profiles ep ON ep.user_id = eo.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contractId);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();

if (!$contract) {
    echo "<div class='p-12 text-center text-slate-500'>Kontrak Escrow tidak ditemukan. <a href='" . BASE_URL . "/dashboard.php' class='text-brand-indigo underline'>Kembali ke dashboard</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handler Aksi Escrow (Deposit, Unggah Bukti, Verifikasi & Pencairan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $milestoneId = (int)($_POST['milestone_id'] ?? 0);
    $action = clean_input($_POST['escrow_action'] ?? '');

    if ($action === 'deposit' && $milestoneId > 0) {
        // Sponsor menyetor dana ke escrow -> status 'Dana Masuk'
        $conn->query("UPDATE escrow_milestones SET status = 'Dana Masuk', updated_at = NOW() WHERE id = {$milestoneId}");
        create_notification($contract['eo_id'], 'Dana Escrow Telah Disetor!', "Sponsor telah menyetorkan dana termin ke rekening bersama SponsorBridge.", "escrow.php?id={$contractId}");
        set_flash('success', 'Simulasi pembayaran berhasil! Dana kini tersimpan aman di rekening penampung escrow resmi.');
        header("Location: " . BASE_URL . "/escrow.php?id={$contractId}");
        exit;

    } elseif ($action === 'upload_proof' && $milestoneId > 0) {
        // EO mengunggah bukti kesiapan / LPJ -> status 'Diverifikasi'
        $notes = clean_input($_POST['verification_notes'] ?? 'Bukti dokumen kesiapan telah diverifikasi lengkap.');
        $proofFile = 'bukti_verifikasi_' . time() . '.pdf';
        
        $stmtUp = $conn->prepare("UPDATE escrow_milestones SET status = 'Diverifikasi', verification_notes = ?, proof_file = ?, proof_uploaded_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmtUp->bind_param("ssi", $notes, $proofFile, $milestoneId);
        $stmtUp->execute();

        create_notification($contract['sponsor_id'], 'Bukti Persiapan Diunggah EO', "EO telah mengunggah bukti verifikasi untuk pencairan termin.", "escrow.php?id={$contractId}");
        set_flash('success', 'Bukti persiapan milestone berhasil diunggah dan siap ditinjau sponsor.');
        header("Location: " . BASE_URL . "/escrow.php?id={$contractId}");
        exit;

    } elseif ($action === 'release' && $milestoneId > 0) {
        // Sponsor / Admin mencairkan dana ke EO -> status 'Dicairkan'
        $conn->query("UPDATE escrow_milestones SET status = 'Dicairkan', released_at = NOW(), updated_at = NOW() WHERE id = {$milestoneId}");
        
        // Update juga dana terkumpul event
        $stmtGetAmt = $conn->query("SELECT amount FROM escrow_milestones WHERE id = {$milestoneId}")->fetch_assoc();
        $amt = (float)($stmtGetAmt['amount'] ?? 0);
        $conn->query("UPDATE events e JOIN proposals p ON e.id = p.event_id JOIN contracts c ON p.id = c.proposal_id SET e.funds_raised = e.funds_raised + {$amt} WHERE c.id = {$contractId}");

        create_notification($contract['eo_id'], 'Dana Escrow Dicairkan!', "Dana termin sebesar " . format_rupiah($amt) . " telah berhasil dicairkan ke rekening bank Anda.", "escrow.php?id={$contractId}");
        set_flash('success', 'Dana termin berhasil dicairkan ke rekening bank resmi Event Organizer!');
        header("Location: " . BASE_URL . "/escrow.php?id={$contractId}");
        exit;

    } elseif ($action === 'dispute' && $milestoneId > 0) {
        // Ajukan sengketa
        $reason = clean_input($_POST['dispute_reason'] ?? 'Ketidaksesuaian realisasi benefit sponsorship.');
        $conn->query("UPDATE escrow_milestones SET status = 'Disengketakan', updated_at = NOW() WHERE id = {$milestoneId}");
        
        $stmtDisp = $conn->prepare("INSERT INTO disputes (contract_id, milestone_id, complainant_id, reason, status) VALUES (?, ?, ?, ?, 'open')");
        $stmtDisp->bind_param("iiis", $contractId, $milestoneId, $userId, $reason);
        $stmtDisp->execute();

        create_notification(1, 'Laporan Sengketa Escrow Baru', "Sengketa diajukan pada kontrak #{$contract['contract_number']}. Memerlukan mediasi admin.", "admin/disputes.php");
        set_flash('warning', 'Laporan sengketa telah diajukan ke tim Trust & Safety SponsorBridge untuk proses mediasi.');
        header("Location: " . BASE_URL . "/escrow.php?id={$contractId}");
        exit;
    }
}

// Ambil list 3 milestone escrow
$stmtMilestones = $conn->prepare("SELECT * FROM escrow_milestones WHERE contract_id = ? ORDER BY milestone_number ASC");
$stmtMilestones->bind_param("i", $contractId);
$stmtMilestones->execute();
$milestones = $stmtMilestones->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung total dana dicairkan vs tertahan
$totalReleased = 0;
$totalInEscrow = 0;
foreach ($milestones as $m) {
    if ($m['status'] === 'Dicairkan') {
        $totalReleased += $m['amount'];
    } else {
        $totalInEscrow += $m['amount'];
    }
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto space-y-6">
            
            <!-- Header Page -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Rekening Bersama Resmi</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Escrow Payment Bertahap</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Sistem pembayaran bersyarat (milestone-based) menjamin keamanan dana sponsor dan kelancaran arus kas EO.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?= BASE_URL ?>/contract.php?id=<?= $contract['id'] ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                        <i class="fa-solid fa-file-contract mr-1"></i> Buka Dokumen MoU
                    </a>
                </div>
            </div>

            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sb-card p-5 bg-gradient-to-br from-navy-900 to-indigo-950 text-white">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-300">Total Nilai Kontrak</span>
                    <div class="text-2xl font-black text-white mt-1"><?= format_rupiah($contract['agreement_amount']) ?></div>
                    <span class="text-[11px] text-slate-300 mt-1 block">MoU: <?= clean_input($contract['contract_number']) ?></span>
                </div>

                <div class="sb-card p-5 bg-white">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Telah Dicairkan ke EO</span>
                    <div class="text-2xl font-black text-emerald-600 mt-1"><?= format_rupiah($totalReleased) ?></div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Telah diverifikasi & diterima</span>
                </div>

                <div class="sb-card p-5 bg-white">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tertahan di Escrow</span>
                    <div class="text-2xl font-black text-blue-600 mt-1"><?= format_rupiah($totalInEscrow) ?></div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Aman di rekening penampung OJK</span>
                </div>
            </div>

            <!-- Official Escrow Explanation Banner -->
            <div class="sb-card p-5 bg-emerald-50/80 border border-emerald-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl shrink-0 shadow-sm">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-emerald-950 uppercase tracking-wider">Mekanisme Pembayaran Escrow Resmi 30% - 40% - 30%</h4>
                        <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">
                            Dana sponsor tidak langsung ditransfer penuh ke EO, melainkan disimpan di rekening bersama resmi SponsorBridge dan dicairkan berjenjang setelah pemenuhan syarat persiapan terverifikasi.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-[10px] font-mono text-emerald-900 bg-white/70 px-3 py-1.5 rounded-lg border border-emerald-200 shrink-0">
                    <i class="fa-solid fa-lock text-emerald-600"></i> Escrow Partner Terlisensi OJK & BI
                </div>
            </div>

            <!-- List 3 Milestone Cards -->
            <div class="space-y-4">
                <h3 class="text-sm font-black text-navy-900 uppercase tracking-wider">Tahapan Termin Pembayaran (Milestones)</h3>

                <div class="space-y-4">
                    <?php foreach ($milestones as $idx => $ms): 
                        $statusClass = [
                            'Pending'       => 'border-slate-200 bg-white',
                            'Dana Masuk'    => 'border-blue-300 bg-blue-50/30',
                            'Diverifikasi'  => 'border-amber-300 bg-amber-50/30',
                            'Dicairkan'     => 'border-emerald-300 bg-emerald-50/40',
                            'Disengketakan' => 'border-rose-300 bg-rose-50/40'
                        ][$ms['status']] ?? 'border-slate-200 bg-white';
                    ?>
                    <div class="sb-card p-6 border-2 <?= $statusClass ?> transition">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-brand-indigo text-white font-black text-xs flex items-center justify-center shadow-xs">
                                    <?= $ms['milestone_number'] ?>
                                </span>
                                <div>
                                    <span class="text-[10px] font-bold text-brand-indigo uppercase">Termin <?= $ms['milestone_number'] ?> &bull; Bobot: <?= $ms['percentage'] ?>%</span>
                                    <h4 class="text-sm font-extrabold text-navy-900"><?= clean_input($ms['milestone_title']) ?></h4>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-400 block font-semibold">Nominal Termin:</span>
                                    <div class="text-lg font-black text-navy-900"><?= format_rupiah($ms['amount']) ?></div>
                                </div>
                                <div>
                                    <?= render_escrow_milestone_badge($ms['status']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone Detail & Proof Section -->
                        <div class="py-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-600">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-semibold mb-1">Syarat Pencairan:</span>
                                <?php if ($ms['milestone_number'] == 1): ?>
                                    <p>Penandatanganan dokumen MoU legal oleh kedua pihak secara digital (DP Persiapan).</p>
                                <?php elseif ($ms['milestone_number'] == 2): ?>
                                    <p>Pengunggahan izin keramaian kepolisian, bukti sewa venue/gedung, dan penayangan kampanye promosi awal.</p>
                                <?php else: ?>
                                    <p>Kegiatan selesai terlaksana dan dokumen Laporan Pertanggungjawaban (LPJ & Post-Event Analytics) telah diunggah.</p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <span class="text-[10px] text-slate-400 block font-semibold mb-1">Catatan Verifikasi Dokumen:</span>
                                <p class="italic text-slate-700">
                                    <?= clean_input($ms['verification_notes'] ?? 'Menunggu proses pemenuhan milestone.') ?>
                                </p>
                                <?php if (!empty($ms['proof_file'])): ?>
                                    <div class="mt-2 flex items-center gap-1.5 text-brand-indigo font-bold cursor-pointer">
                                        <i class="fa-solid fa-file-pdf text-rose-500"></i>
                                        <span><?= clean_input($ms['proof_file']) ?></span>
                                        <span class="text-[10px] text-slate-400 font-normal">(Terlampir)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action Buttons per Role -->
                        <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                            <span class="text-[11px] text-slate-400">
                                <?php if ($ms['status'] === 'Dicairkan'): ?>
                                    <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Dana telah diterima di rekening EO pada <?= format_datetime($ms['released_at']) ?>
                                <?php else: ?>
                                    <i class="fa-solid fa-clock text-amber-500 mr-1"></i> Status proses: <?= clean_input($ms['status']) ?>
                                <?php endif; ?>
                            </span>

                            <div class="flex items-center gap-2">
                                <?php if ($role === 'sponsor' && $ms['status'] === 'Pending'): ?>
                                    <!-- Sponsor Menyetor Dana -->
                                    <form method="POST" action="">
                                        <input type="hidden" name="milestone_id" value="<?= $ms['id'] ?>">
                                        <input type="hidden" name="escrow_action" value="deposit">
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition">
                                            <i class="fa-solid fa-credit-card mr-1"></i> Setor Dana ke Escrow
                                        </button>
                                    </form>

                                <?php elseif ($role === 'eo' && in_array($ms['status'], ['Pending', 'Dana Masuk'])): ?>
                                    <!-- EO Mengunggah Bukti Persiapan -->
                                    <button type="button" onclick="openProofModal(<?= $ms['id'] ?>, <?= $ms['milestone_number'] ?>)" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm transition">
                                        <i class="fa-solid fa-upload mr-1"></i> Unggah Bukti Persiapan / LPJ
                                    </button>

                                <?php elseif (($role === 'sponsor' || $role === 'admin') && in_array($ms['status'], ['Dana Masuk', 'Diverifikasi'])): ?>
                                    <!-- Sponsor / Admin Mencairkan Dana -->
                                    <button type="button" onclick="openDisputeModal(<?= $ms['id'] ?>)" class="px-3 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Sengketa
                                    </button>
                                    <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin mencairkan dana termin ini kepada EO?')">
                                        <input type="hidden" name="milestone_id" value="<?= $ms['id'] ?>">
                                        <input type="hidden" name="escrow_action" value="release">
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition">
                                            <i class="fa-solid fa-money-bill-transfer mr-1"></i> Setujui & Cairkan Dana ke EO
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Detail Rekening Pencairan EO -->
            <div class="sb-card p-6 bg-white">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Informasi Rekening Bank Pencairan (EO)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold">Bank Penerima:</span>
                        <strong class="text-navy-900 text-sm"><?= clean_input($contract['bank_name'] ?? 'Bank Central Asia (BCA)') ?></strong>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold">Nomor Rekening Resmi:</span>
                        <strong class="font-mono text-navy-900 text-sm"><?= clean_input($contract['bank_account'] ?? '123-456-7890') ?></strong>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold">Atas Nama Pemilik:</span>
                        <strong class="text-navy-900 text-sm"><?= clean_input($contract['bank_holder'] ?? $contract['eo_name']) ?></strong>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Unggah Bukti Persiapan -->
<div id="proofModal" class="sb-modal hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 items-center justify-center p-4">
    <div class="sb-card max-w-md w-full p-6 bg-white shadow-2xl">
        <h3 class="text-base font-black text-navy-900 mb-1">Unggah Bukti Verifikasi Milestone</h3>
        <p class="text-xs text-slate-500 mb-4">Lampirkan dokumen bukti fisik, izin, foto kesiapan venue, atau LPJ akhir acara.</p>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="milestone_id" id="modalMilestoneId" value="">
            <input type="hidden" name="escrow_action" value="upload_proof">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Dokumen Bukti (PDF / ZIP / JPG)</label>
                <select name="simulated_file" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30">
                    <option value="Izin_Mabes_Polri_dan_Sewa_Venue.pdf">Izin_Mabes_Polri_dan_Sewa_Venue.pdf</option>
                    <option value="Dokumentasi_Billboard_Promosi.pdf">Dokumentasi_Billboard_Promosi.pdf</option>
                    <option value="Laporan_Pertanggungjawaban_Final.pdf">Laporan_Pertanggungjawaban_Final.pdf</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Keterangan untuk Sponsor</label>
                <textarea name="verification_notes" rows="3" required placeholder="Jelaskan progres kesiapan acara yang telah diselesaikan..."
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('proofModal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm">Kirim untuk Verifikasi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajukan Sengketa -->
<div id="disputeModal" class="sb-modal hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 items-center justify-center p-4">
    <div class="sb-card max-w-md w-full p-6 bg-white shadow-2xl">
        <h3 class="text-base font-black text-rose-700 mb-1">Ajukan Sengketa Escrow (Dispute)</h3>
        <p class="text-xs text-slate-500 mb-4">Pencairan dana akan ditahan sementara untuk ditinjau oleh tim penyelesaian sengketa SponsorBridge.</p>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="milestone_id" id="modalDisputeMilestoneId" value="">
            <input type="hidden" name="escrow_action" value="dispute">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Alasan Sengketa *</label>
                <textarea name="dispute_reason" rows="3" required placeholder="Contoh: Bukti sewa venue tidak terlampir atau penempatan logo tidak sesuai perjanjian MoU..."
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-rose-500/30"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('disputeModal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm">Kirim Laporan Sengketa</button>
            </div>
        </form>
    </div>
</div>

<script>
function openProofModal(mId, mNum) {
    document.getElementById('modalMilestoneId').value = mId;
    openModal('proofModal');
}
function openDisputeModal(mId) {
    document.getElementById('modalDisputeMilestoneId').value = mId;
    openModal('disputeModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
