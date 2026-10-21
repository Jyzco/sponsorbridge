<?php
// create-event.php - Halaman Pembuat Event Baru untuk EO
$page_title = "Buat Event Baru";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

require_role('eo');
$currentUser = current_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $category = clean_input($_POST['category'] ?? '');
    $location = clean_input($_POST['location'] ?? '');
    $city = clean_input($_POST['city'] ?? '');
    $startDate = clean_input($_POST['start_date'] ?? '');
    $endDate = clean_input($_POST['end_date'] ?? '');
    $attendeeEstimate = (int)($_POST['attendee_estimate'] ?? 0);
    $targetAudience = clean_input($_POST['target_audience'] ?? '');
    $totalBudget = (float)($_POST['total_budget'] ?? 0);
    $sponsorshipTarget = (float)($_POST['sponsorship_target'] ?? 0);
    $description = clean_input($_POST['description'] ?? '');
    $rundown = clean_input($_POST['rundown'] ?? '');

    if (empty($title) || empty($category) || empty($location) || empty($startDate) || empty($endDate) || $attendeeEstimate <= 0) {
        $error = 'Harap lengkapi semua kolom wajib.';
    } else {
        // Simpan event ke database
        $stmt = $conn->prepare("
            INSERT INTO events (eo_id, title, category, location, city, start_date, end_date, attendee_estimate, target_audience, total_budget, sponsorship_target, funds_raised, description, rundown, status, is_featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'open', 0)
        ");
        $stmt->bind_param("issssssisddss", $currentUser['id'], $title, $category, $location, $city, $startDate, $endDate, $attendeeEstimate, $targetAudience, $totalBudget, $sponsorshipTarget, $description, $rundown);

        if ($stmt->execute()) {
            $newEventId = $conn->insert_id;

            // Generate otomatis 4 Tier Sponsorship Packages
            $tiers = [
                ['Bronze', 'Bronze Supporter', round($sponsorshipTarget * 0.05), 10, "• Logo pada backdrop (Ukuran S)\n• Penyebutan nama brand oleh MC 2x\n• 2 tiket VIP masuk acara"],
                ['Silver', 'Silver Partner', round($sponsorshipTarget * 0.15), 5, "• Logo pada backdrop & umbul-umbul (Ukuran M)\n• Booth promosi ukuran 2x2 meter\n• Penyebutan MC 5x\n• 1x Instagram Feed & 3x Stories"],
                ['Gold', 'Gold Co-Sponsor', round($sponsorshipTarget * 0.35), 3, "• Logo dominan pada materi cetak & LED panggung (Ukuran L)\n• Booth strategis ukuran 3x3 meter\n• Sesi product talkshow 15 menit di panggung utama\n• 10 tiket VIP & broadcast newsletter ke audiens"],
                ['Platinum', 'Platinum Title Sponsor', round($sponsorshipTarget * 0.60), 1, "• Penamaan titel resmi event ('Presented by Brand')\n• Logo paling dominan (XL) di seluruh media\n• Booth mega pameran ukuran 6x6 meter\n• Keynote speech pimpinan perusahaan saat opening\n• Hak eksklusivitas kategori industri (tanpa kompetitor)"]
            ];

            $stmtPkg = $conn->prepare("INSERT INTO sponsorship_packages (event_id, tier, name, price, quota, slots_taken, benefits) VALUES (?, ?, ?, ?, ?, 0, ?)");
            foreach ($tiers as $t) {
                $stmtPkg->bind_param("issiis", $newEventId, $t[0], $t[1], $t[2], $t[3], $t[4]);
                $stmtPkg->execute();
            }

            set_flash('success', "Event <strong>{$title}</strong> berhasil dibuat beserta 4 paket sponsorship standar! Anda dapat menyusun proposal cerdas sekarang.");
            header('Location: ' . BASE_URL . '/event-detail.php?id=' . $newEventId);
            exit;
        } else {
            $error = 'Gagal menyimpan event: ' . $conn->error;
        }
    }
}
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-4xl mx-auto">
            
            <div class="mb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Form Pendaftaran Event</span>
                <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Buat Event & Buka Peluang Sponsorship</h1>
                <p class="text-xs text-slate-500 mt-1">Lengkapi data event Anda secara akurat agar brand dapat menilai potensi eksposur dan kecocokan audiens.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
                    <span><?= clean_input($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="sb-card p-6 sm:p-8 bg-white">
                <form method="POST" action="" class="space-y-6">
                    
                    <!-- Bagian 1: Informasi Dasar -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-extrabold text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-50 text-brand-indigo text-xs flex items-center justify-center font-black">1</span>
                            Informasi Umum Event
                        </h3>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="title">
                                Nama Resmi Event *
                            </label>
                            <input type="text" id="title" name="title" required placeholder="Contoh: Indonesia Creative Tech Expo 2026"
                                value="<?= isset($_POST['title']) ? clean_input($_POST['title']) : '' ?>"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="category">
                                    Kategori Event *
                                </label>
                                <select id="category" name="category" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo bg-white">
                                    <option value="">Pilih Kategori</option>
                                    <option value="Teknologi">Teknologi & AI</option>
                                    <option value="Musik">Musik & Konser</option>
                                    <option value="Kampus">Kampus & Edukasi</option>
                                    <option value="Olahraga">Olahraga & Marathon</option>
                                    <option value="Bisnis">Bisnis & Startup</option>
                                    <option value="Kuliner">Kuliner & Festival</option>
                                    <option value="Lingkungan">Lingkungan & CSR</option>
                                    <option value="Seni & Kreatif">Seni & Kreatif</option>
                                    <option value="Gaming">Gaming & Esports</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="city">
                                    Kota Penyelenggaraan *
                                </label>
                                <input type="text" id="city" name="city" required placeholder="Contoh: Jakarta Selatan"
                                    value="<?= isset($_POST['city']) ? clean_input($_POST['city']) : '' ?>"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="location">
                                Alamat / Venue Spesifik *
                            </label>
                            <input type="text" id="location" name="location" required placeholder="Contoh: Jakarta Convention Center (JCC) Hall A & B"
                                value="<?= isset($_POST['location']) ? clean_input($_POST['location']) : '' ?>"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="start_date">
                                    Tanggal Mulai *
                                </label>
                                <input type="date" id="start_date" name="start_date" required
                                    value="<?= isset($_POST['start_date']) ? clean_input($_POST['start_date']) : '' ?>"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="end_date">
                                    Tanggal Selesai *
                                </label>
                                <input type="date" id="end_date" name="end_date" required
                                    value="<?= isset($_POST['end_date']) ? clean_input($_POST['end_date']) : '' ?>"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian 2: Audiens & Anggaran -->
                    <div class="space-y-4 pt-4">
                        <h3 class="text-sm font-extrabold text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-50 text-brand-indigo text-xs flex items-center justify-center font-black">2</span>
                            Audiens & Anggaran Finansial
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="attendee_estimate">
                                    Estimasi Pengunjung *
                                </label>
                                <input type="number" id="attendee_estimate" name="attendee_estimate" required min="100" placeholder="10000"
                                    value="<?= isset($_POST['attendee_estimate']) ? clean_input($_POST['attendee_estimate']) : '10000' ?>"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="total_budget">
                                    Total Anggaran Event (Rp) *
                                </label>
                                <input type="number" id="total_budget" name="total_budget" required min="1000000" placeholder="500000000"
                                    value="<?= isset($_POST['total_budget']) ? clean_input($_POST['total_budget']) : '500000000' ?>"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="sponsorship_target">
                                    Target Dana Sponsor (Rp) *
                                </label>
                                <input type="number" id="sponsorship_target" name="sponsorship_target" required min="1000000" placeholder="300000000"
                                    value="<?= isset($_POST['sponsorship_target']) ? clean_input($_POST['sponsorship_target']) : '300000000' ?>"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="target_audience">
                                Karakteristik & Target Demografi Audiens *
                            </label>
                            <input type="text" id="target_audience" name="target_audience" required placeholder="Contoh: Mahasiswa & Fresh Graduates usia 18-25 tahun, Tech Enthusiast, Pengguna Dompet Digital"
                                value="<?= isset($_POST['target_audience']) ? clean_input($_POST['target_audience']) : '' ?>"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                        </div>
                    </div>

                    <!-- Bagian 3: Deskripsi & Rundown -->
                    <div class="space-y-4 pt-4">
                        <h3 class="text-sm font-extrabold text-navy-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-50 text-brand-indigo text-xs flex items-center justify-center font-black">3</span>
                            Deskripsi, Latar Belakang & Rundown
                        </h3>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="description">
                                Deskripsi Detail & Nilai Jual Event *
                            </label>
                            <textarea id="description" name="description" rows="5" required placeholder="Jelaskan konsep acara, keunikan, bintang tamu, daya tarik pengunjung, dan alasan mengapa brand harus mensponsori acara ini..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"><?= isset($_POST['description']) ? clean_input($_POST['description']) : '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="rundown">
                                Ringkasan Rundown / Jadwal Kegiatan
                            </label>
                            <textarea id="rundown" name="rundown" rows="4" placeholder="Hari 1: 09.00 - Registrasi & Opening&#10;Hari 2: 13.00 - Pameran & Talkshow Utama&#10;Hari 3: 19.00 - Malam Puncak Konser"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo"><?= isset($_POST['rundown']) ? clean_input($_POST['rundown']) : '' ?></textarea>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="<?= BASE_URL ?>/dashboard.php" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-7 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-indigo to-brand-purple hover:opacity-95 shadow-md shadow-brand-indigo/25 transition text-xs uppercase tracking-wider">
                            Simpan & Buat 4 Paket Sponsorship <i class="fa-solid fa-arrow-right ml-1"></i>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
