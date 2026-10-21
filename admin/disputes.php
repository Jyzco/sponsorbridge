<?php
// admin/disputes.php - Pusat Resolusi Sengketa Pembayaran Escrow
$page_title = "Resolusi Sengketa Escrow";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_role('admin');

// Handler Resolusi Sengketa oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_dispute'])) {
    $disputeId = (int)$_POST['dispute_id'];
    $decision = clean_input($_POST['decision'] ?? 'resolved'); // 'resolved' (cairkan ke EO) atau 'refunded' (kembalikan ke sponsor)
    $notes = clean_input($_POST['admin_notes'] ?? '');

    // Ambil info sengketa
    $d = $conn->query("SELECT * FROM disputes WHERE id = {$disputeId}")->fetch_assoc();
    if ($d) {
        $stmtUp = $conn->prepare("UPDATE disputes SET status = ?, admin_notes = ?, resolved_at = NOW() WHERE id = ?");
        $stmtUp->bind_param("ssi", $decision, $notes, $disputeId);
        $stmtUp->execute();

        if ($decision === 'resolved') {
            // Cairkan dana termin ke EO
            $conn->query("UPDATE escrow_milestones SET status = 'Dicairkan', updated_at = NOW() WHERE id = {$d['milestone_id']}");
            set_flash('success', 'Sengketa diselesaikan: Dana termin diputuskan untuk dicairkan kepada Event Organizer.');
        } else {
            // Refund dana ke sponsor
            $conn->query("UPDATE escrow_milestones SET status = 'Pending', updated_at = NOW() WHERE id = {$d['milestone_id']}");
            set_flash('info', 'Sengketa diselesaikan: Dana termin diputuskan untuk dikembalikan kepada Sponsor.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/disputes.php');
    exit;
}

$disputes = $conn->query("
    SELECT d.*, c.contract_number, c.agreement_amount,
           em.milestone_number, em.milestone_title, em.amount as milestone_amount,
           u.name as complainant_name, u.role as complainant_role
    FROM disputes d
    JOIN contracts c ON d.contract_id = c.id
    JOIN escrow_milestones em ON d.milestone_id = em.id
    JOIN users u ON d.complainant_id = u.id
    ORDER BY d.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-rose-500">Kepatuhan & Mediasi</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Pusat Mediasi Sengketa Escrow</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Tinjau laporan dispute dari kedua belah pihak berdasarkan dokumen bukti yang diunggah.</p>
                </div>
            </div>

            <div class="sb-card overflow-hidden bg-white">
                <?php if (empty($disputes)): ?>
                    <div class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-scale-balanced text-4xl mb-3 text-slate-300"></i>
                        <p class="text-sm font-bold text-navy-900">Tidak ada laporan sengketa aktif</p>
                        <p class="text-xs text-slate-500 mt-1">Seluruh transaksi escrow berjalan aman dan sesuai kesepakatan.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3.5">No. Kontrak & Milestone</th>
                                    <th class="px-5 py-3.5">Pelapor</th>
                                    <th class="px-5 py-3.5">Alasan Sengketa</th>
                                    <th class="px-5 py-3.5">Nominal Sengketa</th>
                                    <th class="px-5 py-3.5">Status Mediasi</th>
                                    <th class="px-5 py-3.5 text-right">Keputusan Admin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($disputes as $dp): ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3.5">
                                        <div class="font-extrabold text-navy-900"><?= clean_input($dp['contract_number']) ?></div>
                                        <span class="text-[10px] text-slate-400">Termin <?= $dp['milestone_number'] ?> &bull; <?= clean_input($dp['milestone_title']) ?></span>
                                    </td>
                                    <td class="px-5 py-3.5 font-bold text-slate-800">
                                        <?= clean_input($dp['complainant_name']) ?>
                                        <span class="text-[10px] text-slate-400 block uppercase font-normal">(<?= $dp['complainant_role'] ?>)</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-700 max-w-xs">
                                        <?= clean_input($dp['reason']) ?>
                                    </td>
                                    <td class="px-5 py-3.5 font-black text-rose-600">
                                        <?= format_rupiah($dp['milestone_amount']) ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $dp['status'] === 'open' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                                            <?= strtoupper($dp['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <?php if ($dp['status'] === 'open'): ?>
                                            <form method="POST" action="" class="inline-flex gap-1.5">
                                                <input type="hidden" name="dispute_id" value="<?= $dp['id'] ?>">
                                                <input type="hidden" name="resolve_dispute" value="1">
                                                <button type="submit" name="decision" value="resolved" class="px-2.5 py-1 rounded bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition" title="Cairkan ke EO">
                                                    Cairkan EO
                                                </button>
                                                <button type="submit" name="decision" value="refunded" class="px-2.5 py-1 rounded bg-slate-200 text-slate-800 font-bold hover:bg-slate-300 transition" title="Kembalikan ke Sponsor">
                                                    Refund
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-400">Selesai: <?= clean_input($dp['admin_notes'] ?? '-') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
