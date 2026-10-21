<?php
// index.php - Halaman Depan Publik (Landing Page)
$page_title = "Temukan Partner Sponsorship untuk Event Anda";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Ambil data Event Unggulan dari database
$featuredEvents = $conn->query("
    SELECT e.*, u.name as eo_name, ep.logo as eo_logo 
    FROM events e 
    JOIN users u ON e.eo_id = u.id 
    LEFT JOIN eo_profiles ep ON ep.user_id = u.id 
    WHERE e.is_featured = 1 
    ORDER BY e.created_at DESC 
    LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

// Ambil data Brand Populer dari database
$popularSponsors = $conn->query("
    SELECT sp.*, u.name as brand_name, u.avatar 
    FROM sponsor_profiles sp 
    JOIN users u ON sp.user_id = u.id 
    ORDER BY sp.max_budget DESC 
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>

<main class="flex-grow">
    <!-- 1. HERO SECTION -->
    <section class="relative bg-gradient-hero text-white overflow-hidden py-20 lg:py-28 border-b border-navy-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-indigo/20 border border-brand-indigo/40 text-brand-indigo font-bold text-xs uppercase tracking-widest mb-6">
                    <i class="fa-solid fa-bolt-lightning text-amber-400"></i> Platform B2B Sponsorship No. 1 Indonesia
                </div>
                
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight text-white mb-6">
                    Temukan Partner Sponsorship <br>
                    <span class="bg-gradient-to-r from-brand-indigo via-brand-purple to-pink-500 bg-clip-text text-transparent">
                        untuk Event Anda
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-slate-300 mb-10 leading-relaxed max-w-2xl mx-auto">
                    Jembatan profesional antara <strong>Event Organizer (EO)</strong> yang membutuhkan pendanaan dengan <strong>Brand & Perusahaan</strong> yang mencari eksposur promosi tertarget serta program CSR berdampak.
                </p>

                <!-- Action CTAs -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="<?= BASE_URL ?>/register.php?role=eo" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-white bg-brand-indigo hover:bg-brand-indigo/90 shadow-lg shadow-brand-indigo/30 transition flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-calendar-plus"></i> Daftar Sebagai EO (Cari Sponsor)
                    </a>
                    <a href="<?= BASE_URL ?>/register.php?role=sponsor" class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-slate-900 bg-white hover:bg-slate-100 shadow-lg transition flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-building"></i> Daftar Sebagai Brand (Sponsori Event)
                    </a>
                </div>

                <!-- Trust Badges Under Hero -->
                <div class="mt-12 pt-8 border-t border-navy-700/60 flex flex-wrap justify-center items-center gap-6 sm:gap-12 text-xs text-slate-400">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-lock text-emerald-400 text-sm"></i>
                        <span>Negosiasi End-to-End Encrypted</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-vault text-blue-400 text-sm"></i>
                        <span>Escrow Bertahap 30% - 40% - 30%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-signature text-purple-400 text-sm"></i>
                        <span>MoU & Tanda Tangan Digital Legal</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. STATS COUNTER BAR -->
    <section class="bg-white border-b border-slate-200 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-x-0 md:divide-x divide-slate-100">
                <div class="p-3">
                    <div class="text-3xl font-black text-navy-900">120+</div>
                    <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1">Event Sukses Digelar</div>
                </div>
                <div class="p-3">
                    <div class="text-3xl font-black text-brand-indigo">Rp 8.5 M+</div>
                    <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1">Total Dana Sponsorship</div>
                </div>
                <div class="p-3">
                    <div class="text-3xl font-black text-brand-purple">250+</div>
                    <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1">Brand & Korporat Mitra</div>
                </div>
                <div class="p-3">
                    <div class="text-3xl font-black text-emerald-600">98.4%</div>
                    <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1">Kepuasan ROI Sponsor</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. CARA KERJA PLATFORM (Dual Track: Untuk EO & Untuk Sponsor) -->
    <section id="cara-kerja" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-xs font-bold uppercase tracking-widest text-brand-indigo mb-2">Alur Kolaborasi Modern</h2>
                <h3 class="text-3xl font-black text-navy-900 tracking-tight">Bagaimana SponsorBridge Bekerja?</h3>
                <p class="text-slate-500 text-sm mt-3">Proses terstruktur dari penemuan partner, kesepakatan penawaran, hingga pencairan bertahap yang aman.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                
                <!-- Track 1: Untuk Event Organizer (EO) -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-brand-indigo/5 rounded-bl-full pointer-events-none"></div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-brand-indigo/10 text-brand-indigo flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-navy-900">Untuk Event Organizer (EO)</h4>
                            <p class="text-xs text-slate-500">Mendapatkan sponsor dengan kredibel dan terukur</p>
                        </div>
                    </div>

                    <div class="space-y-6 relative before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-indigo text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">1</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Daftarkan Event & Paket Sponsorship</h5>
                                <p class="text-xs text-slate-500 mt-1">Unggah detail acara, rundown, dan paket tier Bronze, Silver, Gold, Platinum beserta kuota dan benefit.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-indigo text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">2</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Smart Proposal Builder & ROI Calc</h5>
                                <p class="text-xs text-slate-500 mt-1">Gunakan template proposal terstruktur otomatis dan sertakan estimasi ROI impresi untuk menarik sponsor.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-indigo text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">3</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Negosiasi E2EE Langsung</h5>
                                <p class="text-xs text-slate-500 mt-1">Diskusikan custom benefit dan revisi penawaran nominal di ruang chat khusus terenkripsi end-to-end.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-indigo text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">4</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Tanda Tangan MoU & Escrow 30-40-30</h5>
                                <p class="text-xs text-slate-500 mt-1">Tanda tangani kontrak secara digital dan terima pencairan dana bertahap sesuai verifikasi milestone event.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Track 2: Untuk Brand & Sponsor -->
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-brand-purple/5 rounded-bl-full pointer-events-none"></div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-brand-purple/10 text-brand-purple flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-navy-900">Untuk Brand & Perusahaan (Sponsor)</h4>
                            <p class="text-xs text-slate-500">Investasi sponsorship tepat sasaran dan bergaransi</p>
                        </div>
                    </div>

                    <div class="space-y-6 relative before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-purple text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">1</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Eksplorasi Event & AI Match Score</h5>
                                <p class="text-xs text-slate-500 mt-1">Temukan event yang selaras dengan target demografi pelanggan atau pilar CSR Anda dengan skor kecocokan AI.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-purple text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">2</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Review Proposal & Ajukan Penawaran</h5>
                                <p class="text-xs text-slate-500 mt-1">Tinjau portofolio EO dan rincian benefit. Terima, tolak, atau buka negosiasi nominal dengan satu klik.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-purple text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">3</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Pembayaran Aman via Rekening Escrow</h5>
                                <p class="text-xs text-slate-500 mt-1">Dana Anda aman di rekening penampung resmi dan baru dicairkan setelah EO menyelesaikan setiap milestone.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 relative">
                            <div class="w-8 h-8 rounded-full bg-brand-purple text-white font-bold text-xs flex items-center justify-center shrink-0 z-10">4</div>
                            <div>
                                <h5 class="text-sm font-bold text-slate-800">Post-Event Impact Analytics</h5>
                                <p class="text-xs text-slate-500 mt-1">Dapatkan laporan terverifikasi: jumlah audiens riil, impresi media sosial, dokumentasi foto logo, dan evaluasi ROI.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 4. DAFTAR EVENT UNGGULAN -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4 mb-12">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Pilihan Kurasi Terbaik</span>
                    <h3 class="text-3xl font-black text-navy-900 tracking-tight mt-1">Event Unggulan Membuka Sponsor</h3>
                    <p class="text-slate-500 text-sm mt-1">Jelajahi event terverifikasi dengan jangkauan pengunjung masif dan paket kemitraan jelas.</p>
                </div>
                <a href="<?= BASE_URL ?>/events.php" class="inline-flex items-center gap-2 text-sm font-bold text-brand-indigo hover:text-brand-purple transition">
                    Lihat Semua Event <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featuredEvents as $event): 
                    $progressPct = min(100, round(($event['funds_raised'] / max(1, $event['sponsorship_target'])) * 100));
                ?>
                <div class="sb-card flex flex-col justify-between overflow-hidden group">
                    <div>
                        <!-- Card Header / Image Placeholder -->
                        <div class="relative h-44 bg-gradient-to-br from-navy-900 via-navy-800 to-indigo-950 p-4 flex flex-col justify-between text-white">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/20 backdrop-blur-md border border-white/30 text-white uppercase tracking-wider">
                                    <?= clean_input($event['category']) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/90 text-white">
                                    <?= clean_input($event['status']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] text-slate-300 block mb-0.5"><i class="fa-regular fa-calendar mr-1"></i><?= format_date($event['start_date']) ?></span>
                                <h4 class="text-base font-extrabold text-white group-hover:text-indigo-300 transition line-clamp-2">
                                    <?= clean_input($event['title']) ?>
                                </h4>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-4">
                            <div class="flex items-center gap-2 text-xs text-slate-600">
                                <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] text-brand-indigo border border-slate-200">
                                    <?= strtoupper(substr($event['eo_name'], 0, 1)) ?>
                                </div>
                                <span class="truncate font-medium"><?= clean_input($event['eo_name']) ?></span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs py-2 border-y border-slate-100">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Estimasi Peserta</span>
                                    <span class="font-bold text-navy-900"><i class="fa-solid fa-users text-slate-400 mr-1"></i><?= number_format($event['attendee_estimate']) ?></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Lokasi</span>
                                    <span class="font-bold text-navy-900 truncate block"><i class="fa-solid fa-location-dot text-slate-400 mr-1"></i><?= clean_input($event['city']) ?></span>
                                </div>
                            </div>

                            <!-- Progress Terkumpul -->
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-[10px] text-slate-400">Target: <?= format_rupiah($event['sponsorship_target']) ?></span>
                                    <span class="font-bold text-brand-indigo"><?= $progressPct ?>%</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-brand-indigo to-brand-purple rounded-full" style="width: <?= $progressPct ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 pt-0">
                        <a href="<?= BASE_URL ?>/event-detail.php?id=<?= $event['id'] ?>" class="w-full py-2.5 rounded-xl font-bold text-xs text-center block bg-slate-100 text-slate-700 hover:bg-brand-indigo hover:text-white transition">
                            Lihat Paket & Detail Event
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 5. DAFTAR BRAND / SPONSOR POPULER -->
    <section class="py-20 bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4 mb-12">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-purple">Mitra Terpercaya</span>
                    <h3 class="text-3xl font-black text-navy-900 tracking-tight mt-1">Brand Aktif Mencari Kemitraan</h3>
                    <p class="text-slate-500 text-sm mt-1">Brand dari berbagai sektor industri siap mendanai event kreatif dan program CSR berdampak.</p>
                </div>
                <a href="<?= BASE_URL ?>/sponsors.php" class="inline-flex items-center gap-2 text-sm font-bold text-brand-purple hover:text-brand-indigo transition">
                    Jelajahi Direktori Brand <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($popularSponsors as $sp): ?>
                <div class="sb-card p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-navy-800 to-brand-purple flex items-center justify-center font-bold text-white text-base shadow-sm">
                                <?= strtoupper(substr($sp['company_name'], 0, 1)) ?>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-brand-purple border border-purple-200">
                                <?= clean_input($sp['industry']) ?>
                            </span>
                        </div>
                        <h4 class="text-base font-extrabold text-navy-900 leading-snug"><?= clean_input($sp['company_name']) ?></h4>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2"><?= clean_input($sp['description']) ?></p>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-semibold">Alokasi Anggaran:</span>
                                <span class="font-bold text-emerald-600"><?= format_rupiah($sp['min_budget']) ?> - <?= format_rupiah($sp['max_budget']) ?></span>
                            </div>
                            <?php if (!empty($sp['csr_focus'])): ?>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-semibold">Fokus CSR:</span>
                                <span class="text-slate-700 text-xs line-clamp-1"><i class="fa-solid fa-leaf text-emerald-500 mr-1"></i><?= clean_input($sp['csr_focus']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100">
                        <a href="<?= BASE_URL ?>/sponsor-detail.php?id=<?= $sp['user_id'] ?>" class="w-full py-2 rounded-xl text-xs font-bold text-center block text-brand-purple bg-purple-50 hover:bg-brand-purple hover:text-white transition">
                            Lihat Profil & Kriteria Kemitraan
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 6. TESTIMONI PENGGUNA B2B -->
    <section class="py-20 bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">Kisah Sukses B2B</span>
                <h3 class="text-3xl font-black text-navy-900 tracking-tight mt-1">Dipercaya Oleh Ratusan Event & Korporasi</h3>
                <p class="text-slate-500 text-sm mt-2">Dengar langsung pengalaman penyelenggara event dan brand manager yang berkolaborasi di SponsorBridge.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimoni 1 -->
                <div class="sb-card p-6 bg-slate-50/70 border-slate-200 relative">
                    <div class="flex items-center gap-1 text-amber-400 text-xs mb-4">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed italic mb-6">
                        "Sebelumnya butuh waktu 2 bulan untuk mencari sponsor konser kami. Di SponsorBridge, kami terhubung dengan Bank Mandiri dalam 5 hari dan menyepakati deal Rp 150 Juta melalui ruang negosiasi E2EE yang sangat praktis."
                    </p>
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
                        <div class="w-9 h-9 rounded-full bg-brand-indigo text-white font-bold text-xs flex items-center justify-center">M</div>
                        <div>
                            <div class="text-xs font-bold text-navy-900">Mahakarya Studio Promosindo</div>
                            <div class="text-[10px] text-slate-500">Penyelenggara SoundFest Carnival (Deal Rp 150 Jt)</div>
                        </div>
                    </div>
                </div>

                <!-- Testimoni 2 -->
                <div class="sb-card p-6 bg-slate-50/70 border-slate-200 relative">
                    <div class="flex items-center gap-1 text-amber-400 text-xs mb-4">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed italic mb-6">
                        "Fitur Escrow bertahap 30-40-30 memberikan rasa aman mutlak bagi manajemen kami saat mengeluarkan anggaran CSR. Kami bisa memverifikasi progres persiapan sebelum termin dana kedua dicairkan."
                    </p>
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
                        <div class="w-9 h-9 rounded-full bg-brand-purple text-white font-bold text-xs flex items-center justify-center">R</div>
                        <div>
                            <div class="text-xs font-bold text-navy-900">Rian Kusuma</div>
                            <div class="text-[10px] text-slate-500">Head of Brand Partnership, PT TelkoNusa Tbk</div>
                        </div>
                    </div>
                </div>

                <!-- Testimoni 3 -->
                <div class="sb-card p-6 bg-slate-50/70 border-slate-200 relative">
                    <div class="flex items-center gap-1 text-amber-400 text-xs mb-4">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed italic mb-6">
                        "Smart Proposal Builder dan kalkulator ROI sangat membantu tim mahasiswa kami menyusun penawaran yang terstandar B2B. Kami berhasil mendapatkan 3 sponsor korporat untuk Art & Culture Fair."
                    </p>
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
                        <div class="w-9 h-9 rounded-full bg-teal-600 text-white font-bold text-xs flex items-center justify-center">B</div>
                        <div>
                            <div class="text-xs font-bold text-navy-900">BEM Universitas Indonesia</div>
                            <div class="text-[10px] text-slate-500">Divisi Hubungan Luar & Sponsorship</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. CTA BANNER AKHIR -->
    <section class="py-16 bg-gradient-navy text-white">
        <div class="max-w-5xl mx-auto px-4 text-center">
            <h3 class="text-3xl sm:text-4xl font-black tracking-tight mb-4">
                Siap Mewujudkan Sponsorship Event Impian Anda?
            </h3>
            <p class="text-slate-300 text-sm max-w-xl mx-auto mb-8 leading-relaxed">
                Bergabunglah sekarang bersama ribuan Event Organizer dan Brand profesional di seluruh Indonesia. Akses gratis, transparan, dan aman.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="<?= BASE_URL ?>/register.php?role=eo" class="w-full sm:w-auto px-7 py-3.5 rounded-xl font-bold bg-brand-indigo hover:bg-brand-indigo/90 text-white text-xs uppercase tracking-wider transition shadow-lg shadow-brand-indigo/30">
                    <i class="fa-solid fa-handshake mr-1.5"></i> Mulai Sebagai EO
                </a>
                <a href="<?= BASE_URL ?>/register.php?role=sponsor" class="w-full sm:w-auto px-7 py-3.5 rounded-xl font-bold bg-white text-navy-900 hover:bg-slate-100 text-xs uppercase tracking-wider transition shadow-lg">
                    <i class="fa-solid fa-gem mr-1.5"></i> Mulai Sebagai Brand
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
