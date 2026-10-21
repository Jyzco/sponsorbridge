<?php
// contract.php - MoU & Contract Generator Berdasarkan Negosiasi dengan Simulasi Tanda Tangan Digital
$page_title = "Dokumen Kontrak MoU & Tanda Tangan Digital";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

$contractId = (int)($_GET['id'] ?? 1);

// Ambil data kontrak lengkap
$stmt = $conn->prepare("
    SELECT c.*, p.title as proposal_title, p.eo_id, p.sponsor_id,
           e.title as event_title, e.category as event_category, e.location, e.city, e.start_date, e.end_date,
           eo.name as eo_name, eo.phone as eo_phone, eo.email as eo_email,
           sp.name as sponsor_name, sp.phone as sponsor_phone, sp.email as sponsor_email,
           nr.current_tier
    FROM contracts c
    JOIN proposals p ON c.proposal_id = p.id
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON p.eo_id = eo.id
    JOIN users sp ON p.sponsor_id = sp.id
    LEFT JOIN negotiation_rooms nr ON c.room_id = nr.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contractId);
$stmt->execute();
$contract = $stmt->get_result()->fetch_assoc();

if (!$contract) {
    echo "<div class='p-12 text-center text-slate-500'>Kontrak tidak ditemukan. <a href='" . BASE_URL . "/proposals.php' class='text-brand-indigo underline'>Kembali</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handler Tanda Tangan Digital
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sign_contract'])) {
    $signatureData = clean_input($_POST['signature_data'] ?? '');
    if (empty($signatureData)) {
        $signatureData = 'DIGITAL_SHA256_SEAL_' . hash('sha256', $currentUser['name'] . time());
    }

    $now = date('Y-m-d H:i:s');
    if ($role === 'eo') {
        $newStatus = !empty($contract['sponsor_signed_at']) ? 'Aktif' : 'Menunggu Tanda Tangan Sponsor';
        $stmtSign = $conn->prepare("UPDATE contracts SET eo_signed_at = ?, eo_signature = ?, status = ? WHERE id = ?");
        $stmtSign->bind_param("sssi", $now, $signatureData, $newStatus, $contractId);
        $stmtSign->execute();

        create_notification($contract['sponsor_id'], 'EO Telah Menandatangani MoU', "Pihak EO {$currentUser['name']} telah menandatangani MoU {$contract['contract_number']}.", "contract.php?id={$contractId}");
    } else {
        $newStatus = !empty($contract['eo_signed_at']) ? 'Aktif' : 'Menunggu Tanda Tangan EO';
        $stmtSign = $conn->prepare("UPDATE contracts SET sponsor_signed_at = ?, sponsor_signature = ?, status = ? WHERE id = ?");
        $stmtSign->bind_param("sssi", $now, $signatureData, $newStatus, $contractId);
        $stmtSign->execute();

        create_notification($contract['eo_id'], 'Sponsor Telah Menandatangani MoU', "Pihak Sponsor {$currentUser['name']} telah menandatangani MoU {$contract['contract_number']}.", "contract.php?id={$contractId}");
    }

    set_flash('success', 'Tanda tangan digital berhasil dibubuhkan pada dokumen perjanjian resmi.');
    header("Location: " . BASE_URL . "/contract.php?id={$contractId}&celebrate=1");
    exit;
}

$isEoSigned = !empty($contract['eo_signed_at']);
$isSponsorSigned = !empty($contract['sponsor_signed_at']);
$isFullySigned = $isEoSigned && $isSponsorSigned;
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Header Top Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Legal Documentation</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Surat Perjanjian Kerjasama (MoU)</h1>
                    <p class="text-xs text-slate-500 mt-1">Dokumen hukum sah kemitraan sponsorship berbasis kesepakatan negosiasi.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="window.print()" class="px-4 py-2 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-2xs flex items-center gap-1.5">
                        <i class="fa-solid fa-print"></i> Cetak / Unduh PDF
                    </button>
                    <a href="<?= BASE_URL ?>/escrow.php?id=<?= $contract['id'] ?>" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-vault"></i> Buka Jadwal Escrow
                    </a>
                </div>
            </div>

            <!-- Status Pipeline Tracker -->
            <div class="sb-card p-5 bg-white no-print">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Kontrak:</span>
                        <?= render_contract_status_badge($contract['status']) ?>
                    </div>
                    <span class="text-xs font-mono text-slate-400">Nomor: <?= clean_input($contract['contract_number']) ?></span>
                </div>

                <!-- 4 Step Stepper -->
                <div class="grid grid-cols-4 gap-2 text-center text-xs">
                    <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold">
                        <i class="fa-solid fa-check text-xs mr-1"></i> 1. Draf Negosiasi
                    </div>
                    <div class="p-2.5 rounded-xl border font-bold <?= $isEoSigned ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?>">
                        <i class="fa-solid <?= $isEoSigned ? 'fa-check' : 'fa-clock' ?> text-xs mr-1"></i> 2. TTD Pihak EO
                    </div>
                    <div class="p-2.5 rounded-xl border font-bold <?= $isSponsorSigned ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?>">
                        <i class="fa-solid <?= $isSponsorSigned ? 'fa-check' : 'fa-clock' ?> text-xs mr-1"></i> 3. TTD Pihak Sponsor
                    </div>
                    <div class="p-2.5 rounded-xl border font-bold <?= $isFullySigned ? 'bg-teal-50 border-teal-200 text-teal-800' : 'bg-slate-50 border-slate-200 text-slate-400' ?>">
                        <i class="fa-solid <?= $isFullySigned ? 'fa-shield-halved' : 'fa-lock' ?> text-xs mr-1"></i> 4. Kontrak Aktif
                    </div>
                </div>
            </div>

            <!-- Formal MoU Paper Layout -->
            <div class="sb-card p-8 sm:p-12 bg-white border border-slate-300 shadow-lg relative print:border-0 print:p-0">
                
                <!-- Watermark Legal Seal -->
                <div class="absolute right-8 top-8 opacity-10 pointer-events-none select-none">
                    <i class="fa-solid fa-shield-halved text-8xl text-navy-900"></i>
                </div>

                <!-- Header Legal -->
                <div class="text-center pb-6 border-b-2 border-navy-900 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-navy-900 text-white flex items-center justify-center font-bold text-xl mx-auto mb-2">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <h2 class="text-lg font-black text-navy-900 uppercase tracking-wider">Surat Perjanjian Kerjasama Sponsorship</h2>
                    <p class="text-xs font-mono text-slate-600 mt-0.5">NOMOR REGISTRASI: <?= clean_input($contract['contract_number']) ?></p>
                    <p class="text-[11px] text-slate-400">Platform Kemitraan Digital Terverifikasi SponsorBridge Indonesia</p>
                </div>

                <!-- Pembukaan Kontrak -->
                <div class="space-y-4 text-xs leading-relaxed text-slate-800 text-justify">
                    <p>
                        Pada hari ini, telah disepakati dan ditandatangani perjanjian kerjasama kemitraan sponsorship antara pihak-pihak di bawah ini:
                    </p>

                    <!-- Pihak 1 & 2 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">PIHAK PERTAMA (Event Organizer):</span>
                            <div class="font-extrabold text-navy-900 text-sm"><?= clean_input($contract['eo_name']) ?></div>
                            <div>Kontak: <?= clean_input($contract['eo_phone']) ?> | <?= clean_input($contract['eo_email']) ?></div>
                            <div class="text-[11px] text-slate-500">Penyelenggara Resmi Event: <strong><?= clean_input($contract['event_title']) ?></strong></div>
                        </div>

                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">PIHAK KEDUA (Sponsor Resmi):</span>
                            <div class="font-extrabold text-navy-900 text-sm"><?= clean_input($contract['sponsor_name']) ?></div>
                            <div>Kontak: <?= clean_input($contract['sponsor_phone']) ?> | <?= clean_input($contract['sponsor_email']) ?></div>
                            <div class="text-[11px] text-slate-500">Tier Kemitraan: <strong><?= clean_input($contract['current_tier'] ?? 'Gold Partner') ?></strong></div>
                        </div>
                    </div>

                    <!-- Pasal-pasal Perjanjian -->
                    <div class="space-y-3 pt-2">
                        <h3 class="font-bold text-navy-900 text-xs uppercase tracking-wider">Pasal 1: Ruang Lingkup Event</h3>
                        <p class="text-slate-700">
                            PIHAK PERTAMA menyelenggarakan kegiatan <strong><?= clean_input($contract['event_title']) ?></strong> yang berlokasi di <?= clean_input($contract['location']) ?>, <?= clean_input($contract['event_city']) ?> pada rentang tanggal <?= format_date($contract['start_date']) ?> sampai dengan <?= format_date($contract['end_date']) ?> dengan estimasi peserta <?= number_format($contract['attendee_estimate']) ?> pengunjung.
                        </p>

                        <h3 class="font-bold text-navy-900 text-xs uppercase tracking-wider pt-2">Pasal 2: Nilai Kemitraan & Skema Escrow Bertahap</h3>
                        <p class="text-slate-700">
                            Total nilai sponsorship yang disepakati adalah sebesar <strong><?= format_rupiah($contract['agreement_amount']) ?></strong>. Pembayaran wajib disalurkan melalui rekening penampung resmi (escrow) SponsorBridge dan dicairkan dengan mekanisme 3 termin berjenjang:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-slate-700">
                            <li><strong>Termin 1 (30%):</strong> Sebesar <?= format_rupiah($contract['agreement_amount'] * 0.30) ?> dicairkan setelah kedua belah pihak menandatangani dokumen perjanjian ini secara digital.</li>
                            <li><strong>Termin 2 (40%):</strong> Sebesar <?= format_rupiah($contract['agreement_amount'] * 0.40) ?> dicairkan setelah PIHAK PERTAMA mengunggah bukti verifikasi kesiapan venue, izin perizinan resmi, dan materi kampanye promosi.</li>
                            <li><strong>Termin 3 (30%):</strong> Sebesar <?= format_rupiah($contract['agreement_amount'] * 0.30) ?> dicairkan setelah pelaksanaan kegiatan selesai serta laporan hasil (LPJ & Post-Event Impact Analytics) diserahkan.</li>
                        </ul>

                        <h3 class="font-bold text-navy-900 text-xs uppercase tracking-wider pt-2">Pasal 3: Hak & Kewajiban Publikasi</h3>
                        <p class="text-slate-700">
                            PIHAK PERTAMA berkewajiban menempatkan logo dan aktivasi promosi PIHAK KEDUA sesuai tier yang telah disepakati. Segala sengketa (dispute) akan diselesaikan melalui jalur mediasi administratif tim kepatuhan SponsorBridge.
                        </p>
                    </div>

                    <!-- Area Tanda Tangan Digital Dua Belah Pihak -->
                    <div class="pt-8 mt-6 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
                        
                        <!-- Box TTD EO -->
                        <div class="p-4 rounded-xl border <?= $isEoSigned ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-300 bg-slate-50' ?> flex flex-col justify-between min-h-[160px]">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">PIHAK PERTAMA (EO)</span>
                                <div class="font-bold text-slate-800 text-xs mt-0.5"><?= clean_input($contract['eo_name']) ?></div>
                            </div>
                            <div class="py-3 flex flex-col items-center justify-center">
                                <?php if ($isEoSigned): ?>
                                    <div class="text-emerald-700 font-serif italic text-lg font-bold select-none">
                                        <?= clean_input($contract['eo_name']) ?>
                                    </div>
                                    <span class="text-[9px] font-mono text-emerald-600 mt-1 block">
                                        <i class="fa-solid fa-certificate text-emerald-500"></i> Ditandatangani Digital: <?= format_datetime($contract['eo_signed_at']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600 font-semibold italic">Menunggu Tanda Tangan EO</span>
                                <?php endif; ?>
                            </div>
                            <div class="border-t border-slate-200 pt-1.5 text-[10px] text-slate-500 font-mono">
                                Penanggung Jawab EO
                            </div>
                        </div>

                        <!-- Box TTD Sponsor -->
                        <div class="p-4 rounded-xl border <?= $isSponsorSigned ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-300 bg-slate-50' ?> flex flex-col justify-between min-h-[160px]">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">PIHAK KEDUA (Sponsor)</span>
                                <div class="font-bold text-slate-800 text-xs mt-0.5"><?= clean_input($contract['sponsor_name']) ?></div>
                            </div>
                            <div class="py-3 flex flex-col items-center justify-center">
                                <?php if ($isSponsorSigned): ?>
                                    <div class="text-emerald-700 font-serif italic text-lg font-bold select-none">
                                        <?= clean_input($contract['sponsor_name']) ?>
                                    </div>
                                    <span class="text-[9px] font-mono text-emerald-600 mt-1 block">
                                        <i class="fa-solid fa-certificate text-emerald-500"></i> Ditandatangani Digital: <?= format_datetime($contract['sponsor_signed_at']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-amber-600 font-semibold italic">Menunggu Tanda Tangan Sponsor</span>
                                <?php endif; ?>
                            </div>
                            <div class="border-t border-slate-200 pt-1.5 text-[10px] text-slate-500 font-mono">
                                Perwakilan Resmi Korporat
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Interaktif Signature Pad Action (Jika role user belum menandatangani) -->
            <?php 
                $needMySign = ($role === 'eo' && !$isEoSigned) || ($role === 'sponsor' && !$isSponsorSigned);
            ?>
            <?php if ($needMySign): ?>
            <div class="sb-card p-6 bg-white border-2 border-brand-indigo no-print">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-navy-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-signature text-brand-indigo"></i> Bubuhkan Tanda Tangan Digital Anda
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Gunakan kursor mouse atau layar sentuh untuk menggambar tanda tangan Anda di area kanvas.</p>
                    </div>
                    <button type="button" id="clearSignatureBtn" class="text-xs font-bold text-rose-600 hover:underline">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Bersihkan Kanvas
                    </button>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="sign_contract" value="1">
                    <input type="hidden" name="signature_data" id="signatureDataInput" value="">

                    <!-- Kanvas HTML5 Tanda Tangan -->
                    <div class="w-full h-36 bg-slate-50 rounded-xl overflow-hidden relative">
                        <canvas id="signaturePadCanvas" class="w-full h-full"></canvas>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div class="flex items-center gap-2 text-[11px] text-slate-500">
                            <i class="fa-solid fa-shield-check text-emerald-600"></i>
                            <span>Tanda tangan tersertifikasi SHA-256 dan terikat secara hukum perdata.</span>
                        </div>
                        <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-white bg-gradient-to-r from-brand-indigo to-brand-purple hover:opacity-95 shadow-md shadow-brand-indigo/25 transition text-xs flex items-center gap-1.5">
                            <i class="fa-solid fa-check-double"></i> Konfirmasi & Tanda Tangani
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php if (isset($_GET['celebrate'])): ?>
<script>
    // Rayakan penyelesaian tanda tangan dengan efek confetti
    window.addEventListener('load', () => {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
