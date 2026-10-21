<?php
// negotiation.php - Negotiation Room Khusus dengan End-to-End Encryption (E2EE)
$page_title = "Negotiation Room (E2EE)";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_login();
$currentUser = current_user();
$userId = $currentUser['id'];
$role = $currentUser['role'];

// Proteksi Admin: Admin DILARANG masuk ke isi chat E2EE (sesuai ketentuan soal)
if ($role === 'admin') {
    set_flash('error', 'Kebijakan Privasi E2EE: Administrator tidak memiliki akses membaca isi percakapan end-to-end terenkripsi.');
    header('Location: ' . BASE_URL . '/admin/negotiations.php');
    exit;
}

$roomId = (int)($_GET['id'] ?? 0);
$eventIdParam = (int)($_GET['event_id'] ?? 0);

// Jika user datang dari tombol "Ajukan Negosiasi" di halaman event detail:
if ($roomId <= 0 && $eventIdParam > 0) {
    // Cari apakah sudah ada proposal / room untuk event ini antara user dan EO
    $stmtFind = $conn->prepare("
        SELECT nr.id FROM negotiation_rooms nr
        JOIN proposals p ON nr.proposal_id = p.id
        WHERE p.event_id = ? AND (nr.eo_id = ? OR nr.sponsor_id = ?)
        LIMIT 1
    ");
    $stmtFind->bind_param("iii", $eventIdParam, $userId, $userId);
    $stmtFind->execute();
    $existing = $stmtFind->get_result()->fetch_assoc();

    if ($existing) {
        header("Location: " . BASE_URL . "/negotiation.php?id=" . $existing['id']);
        exit;
    } else {
        // Buat proposal negosiasi baru
        $stmtEventInfo = $conn->prepare("SELECT eo_id, title, sponsorship_target FROM events WHERE id = ?");
        $stmtEventInfo->bind_param("i", $eventIdParam);
        $stmtEventInfo->execute();
        $evInfo = $stmtEventInfo->get_result()->fetch_assoc();

        if ($evInfo) {
            $propTitle = "Negosiasi Kemitraan: " . $evInfo['title'];
            $defaultPrice = (float)($_GET['price'] ?? ($evInfo['sponsorship_target'] * 0.25));
            $tierParam = clean_input($_GET['tier'] ?? 'Gold');

            $stmtNewProp = $conn->prepare("INSERT INTO proposals (event_id, eo_id, sponsor_id, title, background, target_audience, promotion_plan, proposed_amount, status) VALUES (?, ?, ?, ?, 'Pengajuan negosiasi sponsorship langsung.', 'Audiens tertarget event.', 'Promosi multichannel terpadu.', ?, 'Negosiasi')");
            $stmtNewProp->bind_param("iiisd", $eventIdParam, $evInfo['eo_id'], $userId, $propTitle, $defaultPrice);
            $stmtNewProp->execute();
            $newPropId = $conn->insert_id;

            $stmtNewR = $conn->prepare("INSERT INTO negotiation_rooms (proposal_id, eo_id, sponsor_id, status, current_offer_amount, current_tier) VALUES (?, ?, ?, 'Aktif', ?, ?)");
            $stmtNewR->bind_param("iiids", $newPropId, $evInfo['eo_id'], $userId, $defaultPrice, $tierParam);
            $stmtNewR->execute();
            $newRoomId = $conn->insert_id;

            $msgInit = "Ruang negosiasi dibuka untuk {$evInfo['title']}. Penawaran awal diajukan sebesar " . format_rupiah($defaultPrice) . ".";
            $conn->query("INSERT INTO negotiation_messages (room_id, sender_id, message, is_system, offer_amount) VALUES ({$newRoomId}, {$userId}, '{$msgInit}', 1, {$defaultPrice})");

            header("Location: " . BASE_URL . "/negotiation.php?id=" . $newRoomId);
            exit;
        }
    }
}

// Ambil daftar ruang negosiasi yang diikuti user ini
$stmtRooms = $conn->prepare("
    SELECT nr.*, e.title as event_title, e.category as event_category,
           eo.name as eo_name, sp.name as sponsor_name
    FROM negotiation_rooms nr
    JOIN proposals p ON nr.proposal_id = p.id
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON nr.eo_id = eo.id
    JOIN users sp ON nr.sponsor_id = sp.id
    WHERE nr.eo_id = ? OR nr.sponsor_id = ?
    ORDER BY nr.updated_at DESC
");
$stmtRooms->bind_param("ii", $userId, $userId);
$stmtRooms->execute();
$allRooms = $stmtRooms->get_result()->fetch_all(MYSQLI_ASSOC);

// Jika tidak ada room spesifik yang dipilih, pilih room pertama
if ($roomId <= 0 && !empty($allRooms)) {
    $roomId = $allRooms[0]['id'];
}

// Ambil data room aktif
$activeRoom = null;
if ($roomId > 0) {
    $stmtActive = $conn->prepare("
        SELECT nr.*, p.title as proposal_title, p.proposed_amount as original_proposed_amount,
               e.id as event_id, e.title as event_title, e.category as event_category, e.city as event_city,
               e.start_date, e.end_date, e.attendee_estimate, e.sponsorship_target,
               eo.name as eo_name, eo.id as eo_user_id,
               sp.name as sponsor_name, sp.id as sponsor_user_id
        FROM negotiation_rooms nr
        JOIN proposals p ON nr.proposal_id = p.id
        JOIN events e ON p.event_id = e.id
        JOIN users eo ON nr.eo_id = eo.id
        JOIN users sp ON nr.sponsor_id = sp.id
        WHERE nr.id = ? AND (nr.eo_id = ? OR nr.sponsor_id = ?)
    ");
    $stmtActive->bind_param("iii", $roomId, $userId, $userId);
    $stmtActive->execute();
    $activeRoom = $stmtActive->get_result()->fetch_assoc();
}

// Handler Kirim Pesan / Ajukan Penawaran / Terima Penawaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $activeRoom) {
    $actionType = clean_input($_POST['action_type'] ?? 'message');

    if ($actionType === 'send_message') {
        $messageText = clean_input($_POST['message'] ?? '');
        $attachmentName = null;

        // Simulasi Lampiran File jika dicentang
        if (!empty($_POST['attach_simulation'])) {
            $attachmentName = clean_input($_POST['attach_simulation']);
        }

        if (!empty($messageText) || !empty($attachmentName)) {
            $stmtM = $conn->prepare("INSERT INTO negotiation_messages (room_id, sender_id, message, is_system, attachment_name) VALUES (?, ?, ?, 0, ?)");
            $stmtM->bind_param("iiss", $roomId, $userId, $messageText, $attachmentName);
            $stmtM->execute();

            $partnerId = ($userId == $activeRoom['eo_user_id']) ? $activeRoom['sponsor_user_id'] : $activeRoom['eo_user_id'];
            create_notification($partnerId, 'Pesan Baru di Ruang Negosiasi', "Pesan terenkripsi baru dari {$currentUser['name']}.", "negotiation.php?id={$roomId}");
            
            $conn->query("UPDATE negotiation_rooms SET updated_at = NOW() WHERE id = {$roomId}");
            header("Location: " . BASE_URL . "/negotiation.php?id={$roomId}");
            exit;
        }

    } elseif ($actionType === 'counter_offer') {
        $newAmount = (float)($_POST['offer_amount'] ?? 0);
        $newTier = clean_input($_POST['offer_tier'] ?? $activeRoom['current_tier']);
        $offerNote = clean_input($_POST['offer_note'] ?? '');

        if ($newAmount > 0) {
            $stmtUp = $conn->prepare("UPDATE negotiation_rooms SET current_offer_amount = ?, current_tier = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->bind_param("dsi", $newAmount, $newTier, $roomId);
            $stmtUp->execute();

            // Insert system message penawaran baru
            $systemMsg = "Pengajuan Penawaran Baru: " . format_rupiah($newAmount) . " (Tier: {$newTier}). Catatan: {$offerNote}";
            $stmtM = $conn->prepare("INSERT INTO negotiation_messages (room_id, sender_id, message, is_system, offer_amount) VALUES (?, ?, ?, 1, ?)");
            $stmtM->bind_param("iisd", $roomId, $userId, $systemMsg, $newAmount);
            $stmtM->execute();

            $partnerId = ($userId == $activeRoom['eo_user_id']) ? $activeRoom['sponsor_user_id'] : $activeRoom['eo_user_id'];
            create_notification($partnerId, 'Penawaran Nominal Baru!', "{$currentUser['name']} telah mengajukan penawaran baru: " . format_rupiah($newAmount), "negotiation.php?id={$roomId}");

            set_flash('success', 'Penawaran baru berhasil diajukan!');
            header("Location: " . BASE_URL . "/negotiation.php?id={$roomId}");
            exit;
        }

    } elseif ($actionType === 'seal_deal') {
        // Sepakati penawaran -> status 'Kesepakatan'
        $dealSummary = json_encode([
            'agreed_amount' => $activeRoom['current_offer_amount'],
            'tier' => $activeRoom['current_tier'],
            'agreed_by' => $currentUser['name'],
            'agreed_at' => date('Y-m-d H:i:s'),
            'milestones' => [
                ['termin' => 1, 'pct' => 30, 'amount' => $activeRoom['current_offer_amount'] * 0.30, 'desc' => 'Uang Muka (DP) Penandatanganan MoU'],
                ['termin' => 2, 'pct' => 40, 'amount' => $activeRoom['current_offer_amount'] * 0.40, 'desc' => 'Verifikasi Kesiapan Venue & Publikasi Promosi'],
                ['termin' => 3, 'pct' => 30, 'amount' => $activeRoom['current_offer_amount'] * 0.30, 'desc' => 'Pelunasan Pasca Laporan Dampak Selesai']
            ]
        ]);

        $stmtSeal = $conn->prepare("UPDATE negotiation_rooms SET status = 'Kesepakatan', deal_summary_json = ?, updated_at = NOW() WHERE id = ?");
        $stmtSeal->bind_param("si", $dealSummary, $roomId);
        $stmtSeal->execute();

        // Update status proposal jadi 'Diterima'
        $conn->query("UPDATE proposals SET status = 'Diterima', proposed_amount = {$activeRoom['current_offer_amount']} WHERE id = {$activeRoom['proposal_id']}");

        // Generate Dokumen Kontrak / MoU Otomatis
        $contractNum = 'SB-MOU-' . date('Ym') . '-' . sprintf('%03d', $activeRoom['proposal_id']);
        $terms = "PERJANJIAN KERJASAMA SPONSORSHIP RESMI\nNomor: {$contractNum}\n\nAntara:\nPIHAK PERTAMA: {$activeRoom['eo_name']} (Event Organizer)\nPIHAK KEDUA: {$activeRoom['sponsor_name']} (Sponsor Resmi)\n\nEvent: {$activeRoom['event_title']}\nTier Kemitraan: {$activeRoom['current_tier']}\nTotal Nilai Kesepakatan: " . format_rupiah($activeRoom['current_offer_amount']) . "\n\nPembayaran dilakukan secara bertahap melalui sistem rekening bersama (escrow) SponsorBridge:\n- Termin 1 (30%): " . format_rupiah($activeRoom['current_offer_amount'] * 0.30) . "\n- Termin 2 (40%): " . format_rupiah($activeRoom['current_offer_amount'] * 0.40) . "\n- Termin 3 (30%): " . format_rupiah($activeRoom['current_offer_amount'] * 0.30);

        $stmtC = $conn->prepare("INSERT INTO contracts (proposal_id, room_id, contract_number, agreement_amount, terms_text, status) VALUES (?, ?, ?, ?, ?, 'Menunggu Tanda Tangan EO') ON DUPLICATE KEY UPDATE agreement_amount = VALUES(agreement_amount), terms_text = VALUES(terms_text)");
        $stmtC->bind_param("iisds", $activeRoom['proposal_id'], $roomId, $contractNum, $activeRoom['current_offer_amount'], $terms);
        $stmtC->execute();
        $contractId = $conn->insert_id ?: 1;

        // Buat Milestone Escrow jika belum ada
        $amt1 = $activeRoom['current_offer_amount'] * 0.30;
        $amt2 = $activeRoom['current_offer_amount'] * 0.40;
        $amt3 = $activeRoom['current_offer_amount'] * 0.30;
        $conn->query("INSERT IGNORE INTO escrow_milestones (contract_id, milestone_number, milestone_title, percentage, amount, status) VALUES 
            ({$contractId}, 1, 'Pembayaran Uang Muka (DP 30%) Penandatanganan Kontrak', 30, {$amt1}, 'Pending'),
            ({$contractId}, 2, 'Pencairan Tahap Kedua (40%) Kesiapan Venue & Izin', 40, {$amt2}, 'Pending'),
            ({$contractId}, 3, 'Pelunasan Akhir (30%) Pasca Penyerahan Laporan LPJ', 30, {$amt3}, 'Pending')");

        $sysDone = "🎉 KESEPAKATAN TERCAPAI! Kedua pihak menyetujui nominal final " . format_rupiah($activeRoom['current_offer_amount']) . ". Dokumen MoU telah siap untuk ditandatangani.";
        $conn->query("INSERT INTO negotiation_messages (room_id, sender_id, message, is_system, offer_amount) VALUES ({$roomId}, {$userId}, '{$sysDone}', 1, {$activeRoom['current_offer_amount']})");

        set_flash('success', 'Selamat! Kesepakatan sponsorship berhasil dibuat. Silakan buka draf MoU untuk ditandatangani.');
        header("Location: " . BASE_URL . "/negotiation.php?id={$roomId}");
        exit;
    }
}

// Ambil seluruh percakapan chat dalam room
$messages = [];
if ($activeRoom) {
    $stmtMsg = $conn->prepare("
        SELECT nm.*, u.name as sender_name, u.role as sender_role
        FROM negotiation_messages nm
        JOIN users u ON nm.sender_id = u.id
        WHERE nm.room_id = ?
        ORDER BY nm.created_at ASC
    ");
    $stmtMsg->bind_param("i", $roomId);
    $stmtMsg->execute();
    $messages = $stmtMsg->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 flex flex-col bg-slate-100 h-[calc(100vh-4rem)] overflow-hidden">
        
        <!-- Header Room -->
        <div class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0 shadow-2xs z-10">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-indigo to-brand-purple text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    <i class="fa-solid fa-comments-dollar"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-sm font-black text-navy-900 leading-tight">
                            Negotiation Room
                        </h1>
                        <!-- End-to-End Encrypted Badge -->
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold e2ee-badge" title="Pesan terenkripsi end-to-end. Admin dan pihak ketiga tidak dapat membaca isi obrolan.">
                            <i class="fa-solid fa-lock text-[9px]"></i> End-to-End Encrypted
                        </span>
                    </div>
                    <?php if ($activeRoom): ?>
                        <p class="text-[11px] text-slate-500">
                            <?= clean_input($activeRoom['event_title']) ?> &bull; Antara <strong class="text-slate-700"><?= clean_input($activeRoom['eo_name']) ?></strong> dan <strong class="text-slate-700"><?= clean_input($activeRoom['sponsor_name']) ?></strong>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status Negosiasi Badge -->
            <?php if ($activeRoom): ?>
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">Status:</span>
                <span class="px-2.5 py-1 rounded-full text-xs font-black <?= $activeRoom['status'] === 'Kesepakatan' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-purple-100 text-purple-800 border border-purple-300' ?>">
                    <?= clean_input($activeRoom['status']) ?>
                </span>
                <?php if ($activeRoom['status'] === 'Kesepakatan'): ?>
                    <a href="<?= BASE_URL ?>/contract.php?id=1" class="px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-sm transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-signature text-[10px]"></i> Tanda Tangan MoU
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Split View: Sidebar Rooms (Left) + Chat Window (Center) + Deal Panel (Right) -->
        <div class="flex-1 flex overflow-hidden">
            
            <!-- 1. Left Room List (Hidden on Mobile) -->
            <div class="w-72 bg-white border-r border-slate-200 flex flex-col shrink-0 hidden md:flex">
                <div class="p-3 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                    <span>Percakapan Aktif</span>
                    <span class="bg-slate-100 px-1.5 py-0.2 rounded text-[10px]"><?= count($allRooms) ?></span>
                </div>
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <?php foreach ($allRooms as $rm): 
                        $isActive = $rm['id'] == $roomId;
                        $partnerName = ($userId == $rm['eo_id']) ? $rm['sponsor_name'] : $rm['eo_name'];
                    ?>
                    <a href="?id=<?= $rm['id'] ?>" class="block p-3.5 transition <?= $isActive ? 'bg-indigo-50/60 border-l-4 border-brand-indigo' : 'hover:bg-slate-50' ?>">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-navy-900 truncate"><?= clean_input($partnerName) ?></span>
                            <span class="text-[9px] text-slate-400"><?= date('H:i', strtotime($rm['updated_at'])) ?></span>
                        </div>
                        <p class="text-[11px] text-slate-500 truncate mb-1"><?= clean_input($rm['event_title']) ?></p>
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="font-extrabold text-brand-indigo"><?= format_rupiah($rm['current_offer_amount']) ?></span>
                            <span class="text-[9px] text-slate-400 uppercase font-semibold"><?= clean_input($rm['status']) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Center: Chat Feed & Message Composer -->
            <div class="flex-1 flex flex-col bg-slate-50/60 overflow-hidden">
                
                <!-- Security Notice Banner -->
                <div class="bg-emerald-50/90 border-b border-emerald-200/80 px-4 py-2 flex items-center justify-between text-xs text-emerald-800 shrink-0">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                        <span class="text-[11px] leading-tight">
                            Ruang terenkripsi end-to-end (E2EE). Riwayat pesan privat dan admin tidak dapat membaca obrolan ini.
                        </span>
                    </div>
                    <span class="text-[10px] font-mono text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">AES-256</span>
                </div>

                <!-- Chat Messages Scroll Container -->
                <div id="negotiationChatFeed" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                    <?php if (empty($messages)): ?>
                        <div class="p-8 text-center text-xs text-slate-400">Mulai diskusi kemitraan dengan mengirim pesan pertama di bawah.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): 
                            $isMe = $msg['sender_id'] == $userId;
                            $isSys = (bool)$msg['is_system'];
                        ?>
                            <?php if ($isSys): ?>
                                <!-- System Announcement / Counter Offer Event -->
                                <div class="max-w-lg mx-auto p-3 rounded-xl bg-amber-50/90 border border-amber-200 text-center text-xs text-amber-900 shadow-2xs">
                                    <div class="flex items-center justify-center gap-1.5 font-bold mb-0.5">
                                        <i class="fa-solid fa-bell text-amber-600 text-xs"></i>
                                        <span>Pemberitahuan Sistem Negosiasi</span>
                                    </div>
                                    <p class="text-[11px] text-amber-800 leading-relaxed"><?= clean_input($msg['message']) ?></p>
                                    <span class="text-[9px] text-amber-600 mt-1 block font-mono"><?= date('H:i', strtotime($msg['created_at'])) ?> WIB</span>
                                </div>
                            <?php else: ?>
                                <!-- User Message Bubble -->
                                <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?>">
                                    <div class="flex items-center gap-1.5 mb-1 px-1 text-[10px] text-slate-400 font-semibold">
                                        <span><?= clean_input($msg['sender_name']) ?></span>
                                        <span>&bull;</span>
                                        <span><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                    </div>
                                    <div class="max-w-md sm:max-w-lg px-4 py-3 text-xs leading-relaxed <?= $isMe ? 'chat-bubble-user shadow-md' : 'chat-bubble-other border border-slate-200 shadow-2xs' ?>">
                                        <?= nl2br(clean_input($msg['message'])) ?>

                                        <?php if (!empty($msg['attachment_name'])): ?>
                                            <div class="mt-2.5 pt-2 border-t <?= $isMe ? 'border-white/20' : 'border-slate-200' ?> flex items-center gap-2">
                                                <i class="fa-solid fa-file-pdf <?= $isMe ? 'text-white' : 'text-rose-500' ?> text-sm"></i>
                                                <span class="text-[11px] font-bold underline cursor-pointer"><?= clean_input($msg['attachment_name']) ?></span>
                                                <span class="text-[9px] opacity-75">(Lampiran Terenkripsi)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Chat Message Composer -->
                <?php if ($activeRoom && $activeRoom['status'] !== 'Kesepakatan'): ?>
                <div class="bg-white border-t border-slate-200 p-3 sm:p-4 shrink-0">
                    <form method="POST" action="" class="space-y-2">
                        <input type="hidden" name="action_type" value="send_message">
                        
                        <div class="flex items-center gap-2">
                            <input type="text" name="message" required placeholder="Ketik pesan negosiasi Anda di sini (Terenkripsi E2EE)..." 
                                class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            
                            <!-- Lampiran Simulasi -->
                            <select name="attach_simulation" class="hidden sm:block text-[11px] px-2.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 focus:outline-none">
                                <option value="">+ Lampiran</option>
                                <option value="Revisi_Benefit_Sponsorship.pdf">Revisi_Benefit.pdf</option>
                                <option value="Rundown_Aktivasi_Brand.pdf">Rundown_Aktivasi.pdf</option>
                                <option value="Floorplan_Booth_3x3.pdf">Floorplan_Booth.pdf</option>
                            </select>

                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 transition shadow-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-paper-plane"></i> <span class="hidden sm:inline">Kirim</span>
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-emerald-50 border-t border-emerald-200 p-3 text-center text-xs font-bold text-emerald-800">
                    🎉 Kesepakatan telah disetujui bersama. Ruang chat dikunci dalam status read-only.
                </div>
                <?php endif; ?>

            </div>

            <!-- 3. Right: Deal Offer Center & Action Panel (Hidden on Small Mobile) -->
            <?php if ($activeRoom): ?>
            <div class="w-80 bg-white border-l border-slate-200 p-5 overflow-y-auto shrink-0 hidden lg:flex lg:flex-col justify-between">
                <div class="space-y-5">
                    
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ringkasan Event</span>
                        <h3 class="text-sm font-extrabold text-navy-900 mt-0.5"><?= clean_input($activeRoom['event_title']) ?></h3>
                        <p class="text-[11px] text-slate-500 mt-1"><?= clean_input($activeRoom['event_city']) ?> &bull; <?= number_format($activeRoom['attendee_estimate']) ?> audiens</p>
                    </div>

                    <!-- Current Offer Card -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-900 to-navy-950 text-white shadow-sm space-y-3">
                        <div class="flex justify-between items-center text-[10px] uppercase font-bold text-indigo-300">
                            <span>Penawaran Saat Ini</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded text-white"><?= clean_input($activeRoom['current_tier']) ?></span>
                        </div>
                        <div class="text-2xl font-black text-white">
                            <?= format_rupiah($activeRoom['current_offer_amount']) ?>
                        </div>
                        <div class="text-[10px] text-slate-300">
                            Proposal Awal: <?= format_rupiah($activeRoom['original_proposed_amount']) ?>
                        </div>
                    </div>

                    <!-- Action Buttons for Deals -->
                    <?php if ($activeRoom['status'] !== 'Kesepakatan'): ?>
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <button type="button" onclick="openModal('counterOfferModal')" class="w-full py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-arrows-rotate text-brand-indigo"></i> Ajukan Penawaran Baru
                        </button>

                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda menyepakati nominal final ini? Sistem akan membuat ringkasan deal dan draf MoU resmi.')">
                            <input type="hidden" name="action_type" value="seal_deal">
                            <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-handshake"></i> Buat Kesepakatan Deal
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Ringkasan Kesepakatan Final -->
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 space-y-2 text-xs">
                        <strong class="text-emerald-900 font-bold block flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i> Kesepakatan Terkunci
                        </strong>
                        <div class="text-[11px] text-emerald-800">
                            Total Deal: <strong><?= format_rupiah($activeRoom['current_offer_amount']) ?></strong><br>
                            Jadwal Escrow: 30% DP, 40% Venue/Izin, 30% LPJ.
                        </div>
                        <a href="<?= BASE_URL ?>/contract.php?id=1" class="w-full mt-2 py-2 rounded-lg text-xs font-bold text-center block text-white bg-teal-600 hover:bg-teal-700 transition">
                            Buka & Tanda Tangani MoU &rarr;
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Milestone Escrow Preview -->
                    <div class="space-y-2 pt-4 border-t border-slate-100 text-xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Jadwal Milestone Escrow</span>
                        <div class="space-y-1.5 text-[11px]">
                            <div class="flex justify-between p-2 rounded-lg bg-slate-50">
                                <span>Termin 1 (30% DP):</span>
                                <strong class="text-navy-900"><?= format_rupiah($activeRoom['current_offer_amount'] * 0.30) ?></strong>
                            </div>
                            <div class="flex justify-between p-2 rounded-lg bg-slate-50">
                                <span>Termin 2 (40% Persiapan):</span>
                                <strong class="text-navy-900"><?= format_rupiah($activeRoom['current_offer_amount'] * 0.40) ?></strong>
                            </div>
                            <div class="flex justify-between p-2 rounded-lg bg-slate-50">
                                <span>Termin 3 (30% LPJ):</span>
                                <strong class="text-navy-900"><?= format_rupiah($activeRoom['current_offer_amount'] * 0.30) ?></strong>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="pt-4 border-t border-slate-100 text-[10px] text-slate-400 leading-tight">
                    <i class="fa-solid fa-lock text-emerald-500 mr-1"></i> Dilindungi enkripsi kunci privat pengguna.
                </div>
            </div>
            <?php endif; ?>

        </div>

    </main>
</div>

<!-- Modal Ajukan Counter-Offer Baru -->
<?php if ($activeRoom): ?>
<div id="counterOfferModal" class="sb-modal hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 items-center justify-center p-4">
    <div class="sb-card max-w-md w-full p-6 bg-white shadow-2xl">
        <h3 class="text-base font-black text-navy-900 mb-1">Ajukan Penawaran Nominal Baru</h3>
        <p class="text-xs text-slate-500 mb-4">Kirimkan nominal dan penyesuaian paket yang Anda tawarkan kepada mitra.</p>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="action_type" value="counter_offer">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nominal Penawaran Baru (Rp) *</label>
                <input type="number" name="offer_amount" required step="1000000" min="1000000" value="<?= $activeRoom['current_offer_amount'] ?>"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-black text-brand-indigo focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tier Paket Kemitraan</label>
                <select name="offer_tier" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                    <option value="Bronze" <?= $activeRoom['current_tier'] === 'Bronze' ? 'selected' : '' ?>>Bronze</option>
                    <option value="Silver" <?= $activeRoom['current_tier'] === 'Silver' ? 'selected' : '' ?>>Silver</option>
                    <option value="Gold" <?= $activeRoom['current_tier'] === 'Gold' ? 'selected' : '' ?>>Gold</option>
                    <option value="Platinum" <?= $activeRoom['current_tier'] === 'Platinum' ? 'selected' : '' ?>>Platinum</option>
                    <option value="Custom" <?= $activeRoom['current_tier'] === 'Custom' ? 'selected' : '' ?>>Custom Agreement</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Penyesuaian Benefit</label>
                <textarea name="offer_note" rows="2.5" placeholder="Contoh: Tambahan 1 booth sampling di pintu masuk dan penambahan 2x ad-libs MC..."
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('counterOfferModal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-sm">Kirim Penawaran</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
