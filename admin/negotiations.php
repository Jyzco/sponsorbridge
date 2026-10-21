<?php
// admin/negotiations.php - Metadata Monitor Ruang Negosiasi (Privasi E2EE: Pesan Terkunci)
$page_title = "Status Ruang Negosiasi (Metadata Only)";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_role('admin');

// Query metadata ruangan negosiasi (hanya peserta, waktu pesan terakhir, status, tanpa isi pesan)
$roomsMetadata = $conn->query("
    SELECT nr.id, nr.status, nr.current_offer_amount, nr.current_tier, nr.created_at, nr.updated_at,
           e.title as event_title, e.category,
           eo.name as eo_name, eo.email as eo_email,
           sp.name as sponsor_name, sp.email as sponsor_email,
           (SELECT COUNT(*) FROM negotiation_messages nm WHERE nm.room_id = nr.id) as total_messages,
           (SELECT MAX(created_at) FROM negotiation_messages nm WHERE nm.room_id = nr.id) as last_message_time
    FROM negotiation_rooms nr
    JOIN proposals p ON nr.proposal_id = p.id
    JOIN events e ON p.event_id = e.id
    JOIN users eo ON nr.eo_id = eo.id
    JOIN users sp ON nr.sponsor_id = sp.id
    ORDER BY nr.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-600">Arsitektur Privasi E2EE</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Pemantauan Metadata Ruang Negosiasi</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan metadata teknis ruangan. Sesuai kebijakan hukum & keamanan E2EE, isi pesan obrolan tidak dapat diakses oleh administrator.
                    </p>
                </div>
            </div>

            <!-- E2EE Compliance Notice Card -->
            <div class="sb-card p-6 bg-slate-900 text-white border-2 border-emerald-500/40 space-y-2">
                <div class="flex items-center gap-2 text-emerald-400 text-sm font-black uppercase tracking-wider">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                    <span>Protokol Enkripsi End-to-End (Zero-Knowledge Architecture)</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed max-w-3xl">
                    Sistem ini mengimplementasikan prinsip <em>Zero-Knowledge</em>. Seluruh pesan teks, file lampiran, dan perbincangan negosiasi dienkripsi menggunakan kunci asimetris pada browser masing-masing pihak (EO dan Sponsor). Server dan Administrator hanya menyimpan sandi terenkripsi (ciphertext) dan metadata log aktivitas.
                </p>
            </div>

            <!-- Metadata Table -->
            <div class="sb-card overflow-hidden bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">ID Room & Event</th>
                                <th class="px-5 py-3.5">Pihak EO (Event Organizer)</th>
                                <th class="px-5 py-3.5">Pihak Sponsor (Brand)</th>
                                <th class="px-5 py-3.5">Penawaran Terkini</th>
                                <th class="px-5 py-3.5">Total Pesan</th>
                                <th class="px-5 py-3.5">Aktivitas Terakhir</th>
                                <th class="px-5 py-3.5">Status Enkripsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($roomsMetadata as $rm): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-navy-900 font-mono text-[11px]">#ROOM-E2EE-<?= sprintf('%04d', $rm['id']) ?></div>
                                    <span class="text-[11px] text-slate-600 line-clamp-1 font-semibold"><?= clean_input($rm['event_title']) ?></span>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-800">
                                    <?= clean_input($rm['eo_name']) ?>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-800">
                                    <?= clean_input($rm['sponsor_name']) ?>
                                </td>
                                <td class="px-5 py-3.5 font-extrabold text-navy-900">
                                    <?= format_rupiah($rm['current_offer_amount']) ?>
                                    <span class="text-[9px] text-slate-400 block font-normal">(<?= clean_input($rm['current_tier']) ?>)</span>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-slate-700">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]"><?= $rm['total_messages'] ?> pesan</span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-400">
                                    <?= time_ago($rm['last_message_time'] ?? $rm['updated_at']) ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-lock text-[9px]"></i> E2EE Locked
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
