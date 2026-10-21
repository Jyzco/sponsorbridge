<?php
// database/setup.php - Script Otomatis Setup Database & Seeding Data SponsorBridge
header('Content-Type: text/plain; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'sponsorbridge_db';

echo "=== MEMULAI SETUP DATABASE SPONSORBRIDGE ===\n";

// 1. Buat koneksi awal ke MySQL
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error . "\n");
}
$conn->set_charset("utf8mb4");

// 2. Buat database
if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") === TRUE) {
    echo "✔ Database '$dbname' siap digunakan.\n";
} else {
    die("Gagal membuat database: " . $conn->error . "\n");
}

$conn->select_db($dbname);

// 3. Baca dan jalankan schema.sql
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
if ($conn->multi_query($schemaSql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "✔ Skema tabel berhasil dibuat.\n";
} else {
    die("Gagal mengeksekusi schema.sql: " . $conn->error . "\n");
}

// 4. Bersihkan data lama jika ada
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$tables = ['disputes', 'event_analytics', 'escrow_milestones', 'contracts', 'negotiation_messages', 'negotiation_rooms', 'proposals', 'sponsorship_packages', 'events', 'sponsor_profiles', 'eo_profiles', 'notifications', 'categories', 'users'];
foreach ($tables as $tbl) {
    $conn->query("TRUNCATE TABLE `$tbl`");
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "✔ Data lama dibersihkan.\n";

// 5. Hash password standar: 'password123'
$hashedPassword = password_hash('password123', PASSWORD_BCRYPT);
$adminPassword = password_hash('admin123', PASSWORD_BCRYPT);

// 6. Masukkan Data Users
$usersData = [
    // Admin
    ['SponsorBridge Administrator', 'admin@sponsorbridge.id', $adminPassword, 'admin', '081122334455', 'admin.png', 1],
    
    // EO Users (1-6)
    ['Mahakarya Studio Promosindo', 'eo@mahakarya.id', $hashedPassword, 'eo', '081298765432', 'eo1.jpg', 1],
    ['BEM Universitas Indonesia', 'bem@ui.ac.id', $hashedPassword, 'eo', '081311223344', 'eo2.jpg', 1],
    ['TechNusantara Foundation', 'info@technusantara.id', $hashedPassword, 'eo', '081233445566', 'eo3.jpg', 1],
    ['Java Indie Sound Collective', 'kontak@javaindie.org', $hashedPassword, 'eo', '081277889900', 'eo4.jpg', 1],
    ['Jakarta Berlari Runners Club', 'halo@jakartaberlari.com', $hashedPassword, 'eo', '081399887766', 'eo5.jpg', 1],
    ['Yayasan Hutan Lestari Indonesia', 'kerjasama@hutanlestari.or.id', $hashedPassword, 'eo', '081255667788', 'eo6.jpg', 0], // Pending verifikasi
    
    // Sponsor Users (7-17)
    ['PT TelkoNusa Tbk', 'partnership@telkonusa.co.id', $hashedPassword, 'sponsor', '021500123', 'sp1.jpg', 1],
    ['Bank Mandiri Sejahtera', 'sponsorship@mandirisejahtera.co.id', $hashedPassword, 'sponsor', '021500456', 'sp2.jpg', 1],
    ['Kopi Kenangan Rasa', 'brand@kopikenanganrasa.id', $hashedPassword, 'sponsor', '08111223344', 'sp3.jpg', 1],
    ['TokoHijau Marketplace', 'csr@tokohijau.com', $hashedPassword, 'sponsor', '021500789', 'sp4.jpg', 1],
    ['By.U Gen-Z Mobile', 'youth@byu.id', $hashedPassword, 'sponsor', '085112345678', 'sp5.jpg', 1],
    ['GoRide Mobility Indonesia', 'partners@goride.co.id', $hashedPassword, 'sponsor', '021500321', 'sp6.jpg', 1],
    ['Hydrate Mineral Water', 'marketing@hydratemineral.com', $hashedPassword, 'sponsor', '021500654', 'sp7.jpg', 1],
    ['CloudNusa Technologies', 'growth@cloudnusa.tech', $hashedPassword, 'sponsor', '021500987', 'sp8.jpg', 1],
    ['Wardah Aurora Cosmetics', 'collaborations@wardahaurora.co.id', $hashedPassword, 'sponsor', '021500111', 'sp9.jpg', 1],
    ['AutoMobilindo Electric Motors', 'corporate@automobilindo.id', $hashedPassword, 'sponsor', '021500222', 'sp10.jpg', 1],
    ['NutriBoost Herbal Health', 'promo@nutriboost.co.id', $hashedPassword, 'sponsor', '081288991122', 'sp11.jpg', 0] // Pending verifikasi
];

$stmtUser = $conn->prepare("INSERT INTO users (name, email, password, role, phone, avatar, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($usersData as $u) {
    $stmtUser->bind_param("ssssssi", $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $u[6]);
    $stmtUser->execute();
}
echo "✔ Data Users (Admin, 6 EO, 11 Sponsor) berhasil diinput.\n";

// Map ID User:
// Admin: 1
// EO 1-6: ID 2 - 7
// Sponsor 1-11: ID 8 - 18

// 7. Masukkan Profil EO
$eoProfiles = [
    [2, 'Mahakarya Studio Promosindo', 'Event management profesional berpengalaman lebih dari 8 tahun dalam mengelola festival musik skala nasional, konser artis internasional, dan expo kreatif dengan rata-rata 15.000+ pengunjung per event.', 'Jl. Senopati No. 45, Kebayoran Baru', 'Jakarta Selatan', 'https://mahakaryastudio.id', '@mahakarya.promosindo', 'mahakarya-studio', 24, 4.95, 'Musik, Festival, Seni Kreatif', 'Berhasil menggelar 24 festival musik besar dengan total 350.000 pengunjung dan kepuasan sponsor 98%.', 'BCA', '1234567890', 'PT Mahakarya Studio Promosindo'],
    [3, 'BEM Universitas Indonesia', 'Badan Eksekutif Mahasiswa Universitas Indonesia Divisi Kemitraan Strategis, mengorganisir program tahunan terbesar berskala nasional di bidang seni budaya, kepemudaan, sains, dan kewirausahaan.', 'Gedung Pusgiwa UI, Kampus Depok', 'Depok', 'https://bem.ui.ac.id', '@bemui_official', 'bem-universitas-indonesia', 18, 4.88, 'Kampus, Edukasi, Budaya, Kepemudaan', 'Mengelola UI Art & Culture Fair dan National Youth Summit dengan rata-rata 25.000 mahasiswa dari 80 universitas.', 'Bank Mandiri', '1570009876543', 'BEM Universitas Indonesia'],
    [4, 'TechNusantara Foundation', 'Komunitas dan inisiator konferensi teknologi terkemuka di Asia Tenggara, mempertemukan 10.000+ developer, founder startup, investor VC, dan pengambil kebijakan industri teknologi digital.', 'Satrio Tower Lt. 18, Mega Kuningan', 'Jakarta Selatan', 'https://technusantara.id', '@technusantara_id', 'technusantara-foundation', 12, 4.92, 'Teknologi, AI, Startup, Developer', 'Menyelenggarakan Hackathon Terbesar Se-Indonesia dan Tech Expo tahunan sejak 2019 dengan partisipasi 120+ startup.', 'Bank BNI', '0987654321', 'Yayasan TechNusantara'],
    [5, 'Java Indie Sound Collective', 'Kolektif kurasi musik independen dan festival seni alternatif yang mempromosikan talenta musisi muda serta produk lokal di wilayah Jawa Barat, Jawa Tengah, dan DIY.', 'Jl. Dago Asri No. 12', 'Bandung', 'https://javaindie.org', '@javaindiesound', 'java-indie-collective', 15, 4.85, 'Musik Indie, Kuliner Lokal, Komunitas', 'Kolektif festival indie teraktif dengan 15 kali gelaran di Bandung, Yogyakarta, dan Solo.', 'Bank Permata', '4123987654', 'Kolektif Java Indie'],
    [6, 'Jakarta Berlari Runners Club', 'Komunitas lari terbesar di Jabodetabek dengan 12.000 anggota aktif, penyelenggara ajang marathon ramah lingkungan bersertifikasi PASI dan rute terstandardisasi.', 'Jl. Asia Afrika Kav. 8, Senayan', 'Jakarta Pusat', 'https://jakartaberlari.com', '@jakartaberlari', 'jakarta-berlari', 9, 4.90, 'Olahraga, Marathon, Lifestyle Sehat, CSR Lingkungan', 'Penyelenggara Car Free Day Run Series dan Half Marathon tahunan dengan tingkat safety 100% dan zero accident.', 'Bank CIMB Niaga', '7051234567', 'Komunitas Jakarta Berlari'],
    [7, 'Yayasan Hutan & Bahari Lestari', 'LSM konservasi alam yang menginisiasi gerakan penanaman pohon pesisir, pembersihan sampah laut, dan kampanye edukasi iklim untuk generasi muda.', 'Jl. Hang Tuah Raya No. 20', 'Denpasar', 'https://hutanlestari.or.id', '@hutanlestari_id', 'hutan-lestari', 6, 4.75, 'Lingkungan, Konservasi Alam, CSR Ekologi', 'Menanam lebih dari 80.000 mangrove bersama puluhan mitra korporasi dalam 3 tahun terakhir.', 'Bank BRI', '002101009988501', 'Yayasan Hutan Lestari']
];

$stmtEo = $conn->prepare("INSERT INTO eo_profiles (user_id, organization_name, description, address, city, website, instagram, linkedin, total_events_held, avg_rating, categories, track_record, bank_name, bank_account, bank_holder) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($eoProfiles as $ep) {
    $stmtEo->bind_param("isssssssidsssss", $ep[0], $ep[1], $ep[2], $ep[3], $ep[4], $ep[5], $ep[6], $ep[7], $ep[8], $ep[9], $ep[10], $ep[11], $ep[12], $ep[13], $ep[14]);
    $stmtEo->execute();
}
echo "✔ Profil 6 EO berhasil diinput.\n";

// 8. Masukkan Profil Sponsor
$sponsorProfiles = [
    [8, 'PT TelkoNusa Tbk', 'Telekomunikasi & Digital', 'Perusahaan penyedia infrastruktur telekomunikasi digital nomor 1 di Indonesia dengan 160 juta pelanggan seluler dan jaringan 5G terluas di 40 kota.', 'Telko Tower, Jl. Gatot Subroto Kav. 52', 'Jakarta Selatan', 'https://telkonusa.co.id', 50000000, 1500000000, 'Gen Z, Milenial, Mahasiswa, Profesional Muda, Tech Savvy (18-35 tahun)', 'Literasi Digital Pemuda, Akselerasi Internet Pelosok, Pemberdayaan Talenta AI Nusantara', 'Rian Kusuma (Head of Brand Partnership)', 'rian.kusuma@telkonusa.co.id'],
    [9, 'Bank Mandiri Sejahtera', 'Perbankan & Finansial', 'Institusi perbankan nasional terdepan yang aktif mendukung pertumbuhan ekonomi kreatif, pembiayaan UMKM, dan transformasi transaksi perbankan digital tanpa uang tunai.', 'Plaza Mandiri, Jl. Jend. Sudirman Kav. 36', 'Jakarta Pusat', 'https://mandirisejahtera.co.id', 100000000, 2000000000, 'Pelaku Bisnis, Mahasiswa, Komunitas Startup, Keluarga Muda (20-45 tahun)', 'Inklusi & Literasi Finansial Pelajar, Digitalisasi UMKM Tradisional, Beasiswa Prestasi', 'Dewi Anggraini (CSR & Sponsorship Manager)', 'dewi.anggraini@mandirisejahtera.co.id'],
    [10, 'Kopi Kenangan Rasa', 'Food & Beverage / Retail', 'Brand jaringan kedai kopi retail modern dengan 800+ gerai di seluruh Indonesia, berfokus pada pengalaman nongkrong kekinian dan dukungan komunitas pemuda.', 'Jl. Gandaria I No. 19, Kebayoran Baru', 'Jakarta Selatan', 'https://kopikenanganrasa.id', 20000000, 400000000, 'Remaja, Mahasiswa, Content Creator, Pecinta Kopi (16-30 tahun)', 'Pengembangan Komunitas Kreatif Lokal, Pengelolaan Daur Ulang Ampas Kopi Ramah Lingkungan', 'Bima Sakti (Community Marketing Lead)', 'bima@kopikenanganrasa.id'],
    [11, 'TokoHijau Nusantara', 'E-Commerce & Logistik', 'Ekosistem marketplace dan logistik terdepan yang memfasilitasi jutaan merchant lokal dan mengkampanyekan transaksi hijau berkelanjutan.', 'TokoHijau Green Tower, BSD City', 'Tangerang', 'https://tokohijau.com', 75000000, 1200000000, 'Shopper Online, Mahasiswa, Ibu Rumah Tangga Modern, UMKM Kreatif (18-40 tahun)', 'Zero Plastic Packaging Initiative, Pemberdayaan 100.000 Toko Perempuan Lokal', 'Siti Rahma (Partnership Director)', 'siti.rahma@tokohijau.com'],
    [12, 'By.U Gen-Z Mobile', 'Youth Telco & Lifestyle', 'Operator seluler serba digital pertama di Indonesia yang didesain khusus untuk gaya hidup anak muda, gaming, streaming, dan media sosial tanpa batasan.', 'The East Building Lt. 10, Mega Kuningan', 'Jakarta Selatan', 'https://byu.id', 30000000, 600000000, 'Gen Z, Pelajar SMA, Mahasiswa, Gamers, TikToker (15-24 tahun)', 'Turnamen Esports Pelajar, Workshop Kreasi Konten Digital, Mental Health Gen-Z', 'Fajar Ramadhan (Youth Brand Activation)', 'fajar.r@byu.id'],
    [13, 'GoRide Mobility Indonesia', 'Transportasi & Logistik On-Demand', 'Platform on-demand terkemuka yang melayani mobilitas harian, pesan antar makanan, dan layanan logistik terintegrasi untuk masyarakat perkotaan.', 'Gedung Pasaraya Blok M, Lt. 6', 'Jakarta Selatan', 'https://goride.co.id', 50000000, 800000000, 'Komuter Harian, Pengunjung Festival, Mahasiswa, Pelancong (18-50 tahun)', 'Keselamatan Pengemudi & Penumpang, Edukasi Helm Berstandar, Subsidi Transportasi Event', 'Amanda Putri (Public Relations & Sponsorship)', 'amanda@goride.co.id'],
    [14, 'Hydrate Mineral Water', 'FMCG Minuman', 'Produsen air mineral alami kaya elektrolit yang mendukung hidrasi sehat para atlet, penikmat olahraga, dan audiens event outdoor di seluruh nusantara.', 'Jl. TB Simatupang No. 88', 'Jakarta Selatan', 'https://hydratemineral.com', 25000000, 500000000, 'Pelari, Pecinta Olahraga, Pengunjung Konser, Generasi Aktif Sehat (15-50 tahun)', 'Edukasi Hidrasi Sehat, Water Station Tanpa Botol Plastik Sekali Pakai, Konservasi Mata Air', 'Hendro Wijaya (Sports Marketing Manager)', 'hendro.w@hydratemineral.com'],
    [15, 'CloudNusa Technologies', 'SaaS & Enterprise Cloud', 'Penyedia infrastruktur cloud berdaulat, AI platform, dan cybersecurity terkemuka untuk perusahaan rintisan dan korporasi di Indonesia.', 'AIA Central Lt. 22, Jl. Jend. Sudirman', 'Jakarta Pusat', 'https://cloudnusa.tech', 40000000, 900000000, 'Software Engineer, Data Scientist, Startup CTO, Mahasiswa IT (20-40 tahun)', 'Sponsorship Hackathon Nasional, Penyediaan Kredit Cloud Gratis Mahasiswa, Women in Tech', 'Kevin Tan (Head of Ecosystem)', 'kevin.tan@cloudnusa.tech'],
    [16, 'Wardah Aurora Cosmetics', 'Kecantikan & Personal Care', 'Pelopor kosmetik halal modern yang mengusung nilai keberanian, ekspresi positif diri, dan kecantikan sejati perempuan Indonesia.', 'Jl. Ciledug Raya No. 10', 'Jakarta Selatan', 'https://wardahaurora.co.id', 35000000, 700000000, 'Perempuan Muda, Mahasiswi, Beauty Enthusiast, Traveler (17-38 tahun)', 'Pemberdayaan Kemandirian Perempuan, Edukasi Perawatan Diri Ramah Lingkungan, Beasiswa Siswi', 'Nadia Syahrini (Brand Experience Specialist)', 'nadia.s@wardahaurora.co.id'],
    [17, 'AutoMobilindo Electric', 'Otomotif & Mobilitas Hijau', 'Manufaktur kendaraan listrik inovatif dengan baterai jarak jauh dan stasiun pengisian daya terdistribusi di kota-kota besar.', 'Jl. Raya Boulevard Barat Blok LC-8, Kelapa Gading', 'Jakarta Utara', 'https://automobilindo.id', 80000000, 1800000000, 'Pecinta Otomotif, Kalangan Profesional, Penggiat Lingkungan (25-55 tahun)', 'Dekarbonisasi Udara Perkotaan, Penyediaan Shuttle Listrik Gratis di Event Publik', 'Budi Santoso (Corporate Partnership Director)', 'budi.s@automobilindo.id'],
    [18, 'NutriBoost Herbal Health', 'Kesehatan & Suplemen Herbal', 'Produsen minuman nutrisi herbal alami berkhasiat untuk menjaga imunitas dan stamina gaya hidup dinamis.', 'Jl. Soekarno Hatta No. 400', 'Bandung', 'https://nutriboost.co.id', 15000000, 300000000, 'Keluarga, Pekerja Kantoran, Pecinta Kebugaran (20-55 tahun)', 'Cek Kesehatan Gratis di Komunitas, Edukasi Gizi Seimbang Keluarga Sehat', 'Dra. Ratna Juwita (Marketing Head)', 'ratna.j@nutriboost.co.id']
];

$stmtSp = $conn->prepare("INSERT INTO sponsor_profiles (user_id, company_name, industry, description, address, city, website, min_budget, max_budget, target_audience, csr_focus, contact_person, contact_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($sponsorProfiles as $sp) {
    $stmtSp->bind_param("issssssiissss", $sp[0], $sp[1], $sp[2], $sp[3], $sp[4], $sp[5], $sp[6], $sp[7], $sp[8], $sp[9], $sp[10], $sp[11], $sp[12]);
    $stmtSp->execute();
}
echo "✔ Profil 11 Sponsor berhasil diinput.\n";

// 9. Masukkan Data 12 Event Realistis
$eventsData = [
    // 1
    [2, 'Jakarta Tech & AI Summit 2026', 'Teknologi', 'Jakarta Convention Center (JCC) Senayan', 'Jakarta Pusat', '2026-10-15', '2026-10-17', 12000, 'Developer, Founder Startup, Tech Enthusiast, C-Level Executives (21-40 tahun)', 850000000, 600000000, 450000000, 'Konferensi teknologi dan kecerdasan buatan terbesar di Asia Tenggara yang menghadirkan 80 pembicara global, zona pameran 150 startup, hackathon 48 jam, dan sesi pitch venture capital.', "Hari 1: Opening Keynote & AI Infrastructure Trends\nHari 2: Startup Showcase & Investor Matchmaking\nHari 3: Hackathon Finals & Awarding Ceremony", 'event_tech.jpg', 'open', 1],
    // 2
    [2, 'Nusantara SoundFest Carnival 2026', 'Musik', 'Gambir Expo Kemayoran', 'Jakarta Pusat', '2026-11-06', '2026-11-08', 35000, 'Pecinta Musik, Remaja, Mahasiswa, Komunitas Seni (17-32 tahun)', 1800000000, 1200000000, 950000000, 'Festival musik 3 panggung megah yang merayakan keberagaman musik Indonesia, dari indie pop, rock alternatif, hiphop, hingga electronic jazz bersama 45 musisi top tanah air.', "Jumat: 15.00 - 23.30 (Indie Vibes Stage)\nSabtu: 13.00 - 24.00 (Headliner Main Stage)\nMinggu: 13.00 - 23.30 (Grand Finale & Fireworks)", 'event_music.jpg', 'open', 1],
    // 3
    [3, 'UI Art & Culture Festival 2026', 'Kampus', 'Balairung & Danau Kenanga Kampus UI Depok', 'Depok', '2026-10-22', '2026-10-24', 22000, 'Mahasiswa Jabodetabek, Akademisi, Pecinta Seni & Budaya Tradisional (18-25 tahun)', 450000000, 320000000, 200000000, 'Pesta seni dan budaya tahunan mahasiswa terbesar di Indonesia. Menampilkan pawai 38 provinsi, teater musikal nusantara, bazar kuliner daerah, dan pameran fotografi mahasiswa.', "Hari 1: Karnaval Kostum & Kuliner Tradisional\nHari 2: Lomba Band Mahasiswa & Teater Terbuka\nHari 3: Konser Malam Puncak Bintang Tamu Nasional", 'event_kampus.jpg', 'open', 1],
    // 4
    [5, 'Jakarta Green Marathon 10K & 21K', 'Olahraga', 'Plaza Barat Gelora Bung Karno Senayan', 'Jakarta Pusat', '2026-11-22', '2026-11-22', 15000, 'Pelari Komunitas, Pegiat Gaya Hidup Sehat, Korporat Eksekutif, Keluarga (20-50 tahun)', 650000000, 480000000, 380000000, 'Ajang lari jalan raya berskala internasional dengan konsep Zero Waste Event. Rute melewati landmark ikonik Sudirman-Thamrin dengan water station ramah lingkungan.', "05.00: Flag-off Half Marathon 21K\n05.45: Flag-off 10K Category\n08.30: Podium Awarding & Eco Health Expo", 'event_marathon.jpg', 'open', 1],
    // 5
    [4, 'Indonesia Startup Pitch Battle 2026', 'Bisnis', 'Main Atrium Gandaria City & Ballroom', 'Jakarta Selatan', '2026-11-12', '2026-11-13', 8000, 'Founder Startup, Investor Angel, Modal Ventura, Pebisnis Muda (22-45 tahun)', 400000000, 300000000, 250000000, 'Kompetisi pitching inovasi startup berskala nasional dengan total hadiah hibah pendanaan Rp 1 Miliar dan eksposur langsung ke 50+ pemodal ventura papan atas.', "Hari 1: Demo Day Top 20 Startups & Masterclass\nHari 2: Grand Final Pitching & Investor Speed Dating", 'event_startup.jpg', 'open', 0],
    // 6
    [2, 'Festival Kuliner Tradisional Nusantara', 'Kuliner', 'Lapangan Banteng Park', 'Jakarta Pusat', '2026-10-02', '2026-10-04', 30000, 'Pecinta Kuliner, Keluarga, Turis Domestik & Mancanegara, Foodies (Semua Umur)', 550000000, 350000000, 280000000, 'Pesta rasa kuliner otentik menghadirkan lebih dari 120 tenant kuliner legendaris dari Aceh hingga Papua dengan transaksi digital terintegrasi.', "Setiap Hari: 10.00 - 22.00 WIB (Cooking Demo, Lomba Masak, & Panggung Musik Akustik)", 'event_kuliner.jpg', 'open', 0],
    // 7
    [3, 'Pekan Raya Mahasiswa Sains & Robotika', 'Edukasi', 'Pusat Studi Jepang & Fakultas Teknik UI', 'Depok', '2026-11-18', '2026-11-20', 14000, 'Siswa SMA/SMK, Mahasiswa STEM, Guru, Peneliti Muda (15-25 tahun)', 380000000, 250000000, 180000000, 'Olimpiade robotika otomasi, kompetisi drone pintar, dan pameran prototipe teknologi terapan karya pemuda terbaik nusantara.', "Hari 1: Kontes Robot Line Tracer & Drone Obstacle\nHari 2: Pameran Sains Terapan & Workshop AI Robot\nHari 3: Pengumuman Juara & Beasiswa Riset", 'event_sains.jpg', 'open', 0],
    // 8
    [7, 'Aksi Tanam 10.000 Mangrove Pesisir', 'Lingkungan', 'Kawasan Hutan Lindung Angke Kapuk', 'Jakarta Utara', '2026-10-25', '2026-10-25', 5000, 'Relawan Lingkungan, Karyawan Korporasi, Komunitas Kampus (18-40 tahun)', 220000000, 180000000, 180000000, 'Aksi nyata mitigasi abrasi pesisir utara Jakarta dengan menanam 10.000 bibit mangrove bersertifikat penyerapan karbon bersama para mitra sponsor.', "06.30: Registrasi Relawan & Safety Briefing\n07.30: Aksi Penanaman Serentak di 5 Zona\n10.30: Edukasi Ekosistem Bakau & Penyerahan Plakat Sponsor", 'event_mangrove.jpg', 'completed', 0],
    // 9
    [4, 'Indie Film & Creative Media Expo 2026', 'Seni & Kreatif', 'Taman Ismail Marzuki (TIM) Cikini', 'Jakarta Pusat', '2026-12-04', '2026-12-06', 10000, 'Sineas Muda, Content Creator, Desainer Grafis, Penikmat Sinema (18-35 tahun)', 320000000, 240000000, 120000000, 'Eksibisi film independen pendek dan dokumenter pilihan kurator nasional, disertai workshop sinematografi, pameran alat rekam, dan networking night.', "Jumat: Screening Film Pembuka & Panel Diskusi\nSabtu: Masterclass Directing & Editing\nMinggu: Malam Penganugerahan Piala Kreatif", 'event_film.jpg', 'open', 0],
    // 10
    [2, 'National High School Esports Championship', 'Gaming', 'BritAma Arena Mahaka Square Kelapa Gading', 'Jakarta Utara', '2026-11-28', '2026-11-29', 18000, 'Siswa SMA/SMK, Komunitas Gamers, Gen Z, Content Creator (14-22 tahun)', 600000000, 450000000, 320000000, 'Turnamen esports antar pelajar terbesar se-Indonesia mempertandingkan Mobile Legends & Valorant dengan sistem liga dan siaran live streaming berstandar broadcast pro.', "Sabtu: Babak Perempat Final & Semifinal (10.00 - 21.00)\nMinggu: Grand Finals & Celebrity Match All-Stars (11.00 - 22.00)", 'event_esports.jpg', 'open', 1],
    // 11
    [3, 'Nusantara Fashion & Batik Week 2026', 'Fashion', 'Grand Ballroom Hotel Indonesia Kempinski', 'Jakarta Pusat', '2026-12-11', '2026-12-13', 12000, 'Pecinta Wastra Nusantara, Desainer Muda, Pembeli Ritel, Influencer (20-50 tahun)', 500000000, 350000000, 150000000, 'Peragaan busana batik kontemporer dan wastra tenun berkelanjutan rancangan 40 desainer muda binaan kampus dan perajin daerah.', "Hari 1: Gala Opening & Sustainable Runway\nHari 2: Trunk Show & B2B Buyer Meetup\nHari 3: Consumer Bazaar & Hijab Chic Styling", 'event_fashion.jpg', 'open', 0],
    // 12
    [5, 'Health & Mental Wellness Weekend 2026', 'Kesehatan', 'Taman Suropati & Hutan Kota GBK', 'Jakarta Pusat', '2026-10-31', '2026-11-01', 8500, 'Praktisi Yoga, Komunitas Meditasi, Pekerja Kantoran Stres, Pecinta Gaya Hidup Holistik (22-45 tahun)', 280000000, 200000000, 160000000, 'Festival kesehatan mental dan fisik pertama dengan sesi yoga massal, sound healing terbuka, konsultasi psikolog gratis, dan stan nutrisi organik.', "Sabtu Pagi: Sunrise Yoga & Sound Bath Therapy\nSabtu Siang: Talkshow Mindfulness & Manajemen Stres\nMinggu: Walk for Mind Marathon & Healthy Food Market", 'event_wellness.jpg', 'open', 0]
];

$stmtEv = $conn->prepare("INSERT INTO events (eo_id, title, category, location, city, start_date, end_date, attendee_estimate, target_audience, total_budget, sponsorship_target, funds_raised, description, rundown, poster, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($eventsData as $ev) {
    $stmtEv->bind_param("issssssisiiissssi", $ev[0], $ev[1], $ev[2], $ev[3], $ev[4], $ev[5], $ev[6], $ev[7], $ev[8], $ev[9], $ev[10], $ev[11], $ev[12], $ev[13], $ev[14], $ev[15], $ev[16]);
    $stmtEv->execute();
}
echo "✔ Data 12 Event berhasil diinput.\n";

// 10. Paket Sponsorship untuk tiap event (Bronze, Silver, Gold, Platinum)
$packageStmt = $conn->prepare("INSERT INTO sponsorship_packages (event_id, tier, name, price, quota, slots_taken, benefits) VALUES (?, ?, ?, ?, ?, ?, ?)");

for ($eventId = 1; $eventId <= 12; $eventId++) {
    $packages = [
        [$eventId, 'Bronze', 'Bronze Supporter', 15000000, 10, 3, "• Logo pada backdrop pameran (Ukuran S)\n• Penyebutan nama brand (ad-lib) oleh MC 2x per hari\n• 2 tiket VIP pass masuk acara\n• Postingan logo bersama di akun Instagram resmi event"],
        [$eventId, 'Silver', 'Silver Partner', 35000000, 6, 2, "• Logo pada backdrop utama panggung & umbul-umbul venue (Ukuran M)\n• Stand booth promosi ukuran 2x2 meter di area pengunjung\n• Penyebutan ad-lib MC 5x per hari\n• 5 tiket VIP & akses lounge pembicara\n• 1x Instagram Feed & 3x Story promosi produk sponsor\n• Distribusi brosur/goodie bag sponsor kepada peserta"],
        [$eventId, 'Gold', 'Gold Co-Sponsor', 75000000, 4, 1, "• Logo eksklusif pada seluruh media cetak, billboard, dan layar LED panggung (Ukuran L)\n• Booth strategis ukuran 3x3 meter dekat pintu masuk utama\n• Sesi pemaparan produk (Product Talkshow) selama 15 menit di panggung utama\n• Penyebutan ad-lib MC 10x per hari\n• 10 tiket VIP & undangan gala dinner\n• 3x Instagram Feed Reels, 5x Stories, dan broadcast newsletter email ke 20.000 database audiens\n• Hak penempatan sampling produk secara eksklusif"],
        [$eventId, 'Platinum', 'Platinum Title Sponsor', 150000000, 2, 1, "• Penamaan titel resmi event (contoh: 'Presented by Brand Anda')\n• Logo paling dominan (XL) di seluruh materi promosi TV, digital, dan venue utama\n• Booth mega pameran ukuran 6x6 meter di titik paling premium\n• Keynote speech pimpinan perusahaan selama 30 menit di pembukaan acara\n• Liputan media khusus dan wawancara pers rilis bersama media partner nasional\n• Hak eksklusivitas kategori industri (tidak ada kompetitor sejenis)\n• Akses database prospek pengunjung (sesuai regulasi PDP) & laporan ROI pasca-event lengkap\n• 20 tiket VVIP All-Access"]
    ];

    foreach ($packages as $pkg) {
        $packageStmt->bind_param("issiiis", $pkg[0], $pkg[1], $pkg[2], $pkg[3], $pkg[4], $pkg[5], $pkg[6]);
        $packageStmt->execute();
    }
}
echo "✔ Paket sponsorship (Bronze, Silver, Gold, Platinum) untuk 12 event berhasil dibuat.\n";

// 11. Masukkan Proposal dengan variasi status
// Status: Draft, Dikirim, Dibaca, Review, Negosiasi, Diterima, Ditolak, Kontrak Ditandatangani, Selesai
$proposalsData = [
    // 1: Status 'Negosiasi' (Event 1 - EO 2 - Sponsor TelkoNusa ID 8)
    [1, 2, 8, 3, 'Proposal Kemitraan Strategis Jakarta Tech Summit 2026', 'Mendukung transformasi kecerdasan buatan nasional dengan menghubungkan solusi cloud dan konektivitas TelkoNusa kepada 12.000 talenta digital muda.', '12.000 peserta dengan komposisi 65% software developer, 20% founder startup, dan 15% eksekutif teknologi.', 'Kampanye digital terpadu melalui Instagram, TikTok, LinkedIn, dan email blast ke 45.000 subscriber tech.', 'Penyediaan booth 3x3 di zona AI, sesi talkshow 15 menit, dan branding logo Platinum.', 75000000, 310.00, 'Negosiasi', NULL],
    
    // 2: Status 'Kontrak Ditandatangani' (Event 2 - EO 2 - Sponsor Bank Mandiri ID 9)
    [2, 2, 9, 7, 'Sponsorship Official Banking Partner Nusantara SoundFest 2026', 'Menjadikan Bank Mandiri Sejahtera sebagai mitra perbankan eksklusif untuk seluruh transaksi tiket dan merchant F&B nirsentuh di festival musik.', '35.000 penikmat musik muda dengan daya beli tinggi rentang usia 18-32 tahun.', 'Branding di tiket gelang RFID, gerbang masuk utama, dan promo cashback aplikasi perbankan digital.', 'Cashless payment exclusivity, branding main stage, dan booth 4x4 meter.', 150000000, 380.00, 'Kontrak Ditandatangani', NULL],
    
    // 3: Status 'Review' (Event 3 - EO 3 - Sponsor By.U ID 12)
    [3, 3, 12, 10, 'Kemitraan Youth Connectivity UI Art & Culture Fair 2026', 'Mengakuisisi pengguna kartu perdana mahasiswa baru dengan aktivasi interaktif di lingkungan kampus UI.', '22.000 mahasiswa aktif dari berbagai universitas di Jabodetabek.', 'Roadshow pra-acara ke 14 fakultas dan media partner radio mahasiswa.', 'Booth gaming corner dan voucher kuota eksklusif untuk setiap pengunjung.', 35000000, 260.00, 'Review', NULL],
    
    // 4: Status 'Dikirim' (Event 4 - EO 5 - Sponsor Hydrate Mineral ID 14)
    [4, 5, 14, 14, 'Official Hydration Partner Jakarta Green Marathon 2026', 'Penyediaan hidrasi sehat ramah lingkungan di 8 water station sepanjang rute 21K dan 10K marathon.', '15.000 pelari aktif dan 25.000 penonton di area GBK Senayan.', 'Promosi di race pack jersey, medali finisher, dan kampanye media sosial lari.', 'Distribusi produk di garis finish dan logo di seluruh race bib pelari.', 50000000, 290.00, 'Dikirim', NULL],
    
    // 5: Status 'Ditolak' (Event 5 - EO 4 - Sponsor AutoMobilindo ID 17)
    [5, 4, 17, 18, 'Peluang Sponsorship Startup Pitch Battle 2026', 'Pengenalan armada mobil listrik eksekutif kepada 8000 founder startup dan ekosistem pemodal ventura.', 'Komunitas investor dan founder bisnis teknologi.', 'Showcase display mobil listrik di lobi utama konvensi.', 'Unit display di main entrance dan presentasi mobilitas hijau.', 75000000, 180.00, 'Ditolak', 'Alokasi anggaran promosi Q4 dialihkan untuk peluncuran showroom regional.'],
    
    // 6: Status 'Diterima' (Event 10 - EO 2 - Sponsor By.U ID 12)
    [10, 2, 12, 38, 'Title Sponsor National High School Esports 2026', 'Dominasi brand By.U sebagai operator seluler pilihan gamer pelajar dengan live stream turnamen berpenonton 200.000+ online.', '18.000 penonton arena dan 250.000 pemirsa live YouTube & TikTok Esports.', 'Highlight turnamen, in-game banner, dan streaming overlay terintegrasi.', 'Piala bergilir By.U, booth fun match, dan naming rights turnamen.', 120000000, 420.00, 'Diterima', NULL],
    
    // 7: Status 'Selesai' (Event 8 - EO 7 - Sponsor TokoHijau ID 11)
    [8, 7, 11, 30, 'Program CSR Penanaman 10.000 Mangrove Pesisir', 'Aksi pelestarian pesisir sebagai wujud pilar keberlanjutan TokoHijau dalam komitmen nol emisi.', '5.000 relawan dan liputan pers lingkungan nasional.', 'Rilis media di 15 portal berita online, dokumentasi video drone profesional.', 'Sertifikat pohon digital untuk pelanggan setia TokoHijau.', 60000000, 350.00, 'Selesai', NULL]
];

$stmtProp = $conn->prepare("INSERT INTO proposals (event_id, eo_id, sponsor_id, package_id, title, background, target_audience, promotion_plan, custom_benefits, proposed_amount, estimated_roi_pct, status, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($proposalsData as $pr) {
    $stmtProp->bind_param("iiiisssssdsss", $pr[0], $pr[1], $pr[2], $pr[3], $pr[4], $pr[5], $pr[6], $pr[7], $pr[8], $pr[9], $pr[10], $pr[11], $pr[12]);
    $stmtProp->execute();
}
echo "✔ Data Proposal beragam status berhasil diinput.\n";

// 12. Negotiation Room & E2EE Chat History (Untuk Proposal 1: Jakarta Tech Summit 2026)
$conn->query("INSERT INTO negotiation_rooms (proposal_id, eo_id, sponsor_id, status, current_offer_amount, current_tier, deal_summary_json) VALUES 
(1, 2, 8, 'Aktif', 70000000, 'Gold', '{\"agreed_amount\": 70000000, \"tier\": \"Gold Partner (Custom)\", \"extra_perks\": [\"Booth 3x3 di Main Lobby\", \"MC Ad-lib 8x sehari\", \"1x Keynote Panel 20 menit\", \"Database leads pengunjung yang opt-in\"]}'),
(2, 2, 9, 'Kesepakatan', 150000000, 'Platinum', '{\"agreed_amount\": 150000000, \"tier\": \"Official Banking Partner\", \"extra_perks\": [\"Eksklusivitas QRIS & Mesin EDC Mandiri\", \"Logo VVIP di Seluruh Gelang Tiket\", \"Booth 4x4 Dekat Stage Utama\"]}')
");

$roomId1 = 1;
$roomId2 = 2;

// Pesan Chat E2EE untuk Room 1 (Proposal 1: Tech Summit - TelkoNusa & Mahakarya)
$messagesRoom1 = [
    [$roomId1, 8, 'Halo rekan Mahakarya Studio! Kami dari tim partnership TelkoNusa sudah meninjau proposal Jakarta Tech Summit 2026. Topik AI Summit sangat sesuai dengan fokus kami tahun ini.', 0, NULL, NULL, NULL],
    [$roomId1, 2, 'Salam hangat Pak Rian! Terima kasih atas ketertarikan TelkoNusa. Apakah ada poin benefit dari paket Gold yang ingin disesuaikan dengan program aktivasi TelkoNusa?', 0, NULL, NULL, NULL],
    [$roomId1, 8, 'Dari pihak manajemen kami tertarik mengambil paket Gold, namun kami ingin mengajukan penyesuaian nominal menjadi Rp 70.000.000 dengan tambahan 1 sesi Keynote Panel selama 20 menit membahas Digital Infrastructure.', 0, 'Revisi_Kerjasama_TelkoNusa.pdf', 'assets/uploads/sample_spec.pdf', 70000000],
    [$roomId1, 2, 'Penawaran yang sangat baik! Kami menyetujui nominal Rp 70.000.000 dan penambahan sesi Keynote Panel 20 menit di panggung utama hari pertama. Kami kirimkan pembaruan rundown.', 0, 'Rundown_Revisi_Day1.pdf', 'assets/uploads/sample_rundown.pdf', 70000000],
    [$roomId1, 8, 'Luar biasa, kami sepakat dengan draf kesepakatan ini. Silakan buat kesepakatan final agar dapat kami teruskan ke proses MoU dan pencairan escrow tahap pertama.', 0, NULL, NULL, 70000000]
];

$stmtMsg = $conn->prepare("INSERT INTO negotiation_messages (room_id, sender_id, message, is_system, attachment_name, attachment_url, offer_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($messagesRoom1 as $m) {
    $stmtMsg->bind_param("iisisss", $m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
    $stmtMsg->execute();
}
echo "✔ Negotiation room & riwayat chat E2EE berhasil diinput.\n";

// 13. Kontrak & MoU (Untuk Proposal 2: SoundFest - Bank Mandiri)
$termsText = "PERJANJIAN KERJASAMA SPONSORSHIP RESMI\nNomor: SB/MOU/2026/09/002\n\nAntara:\nPIHAK PERTAMA: Mahakarya Studio Promosindo (Event Organizer)\nPIHAK KEDUA: PT Bank Mandiri Sejahtera Tbk (Sponsor Resmi)\n\nPasal 1: Ruang Lingkup Kerjasama\nPIHAK PERTAMA menyelenggarakan event 'Nusantara SoundFest Carnival 2026' dan menunjuk PIHAK KEDUA sebagai 'Official Banking Partner'.\n\nPasal 2: Nilai Kontrak & Pembayaran Escrow\nTotal nilai kesepakatan adalah sebesar Rp 150.000.000 (Seratus Lima Puluh Juta Rupiah) yang disalurkan melalui Rekening Escrow SponsorBridge dengan tahapan:\n- Termin 1 (30%): Rp 45.000.000 setelah penandatanganan MoU\n- Termin 2 (40%): Rp 60.000.000 setelah kesiapan venue dan publikasi promosi diverifikasi\n- Termin 3 (30%): Rp 45.000.000 setelah event selesai dan laporan dampak diserahkan\n\nPasal 3: Hak & Kewajiban Sponsor\nPIHAK KEDUA berhak atas penempatan logo VVIP, eksklusivitas metode pembayaran QRIS/EDC, dan booth pameran 4x4 meter.";

$stmtContract = $conn->prepare("INSERT INTO contracts (proposal_id, room_id, contract_number, agreement_amount, terms_text, eo_signed_at, sponsor_signed_at, eo_signature, sponsor_signature, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$propId = 2;
$roomId = 2;
$contractNum = 'SB-MOU-2026-09-002';
$agreementAmount = 150000000;
$eoSignTime = '2026-09-01 10:30:00';
$spSignTime = '2026-09-02 14:15:00';
$eoSig = 'SIGNED_BY_EO_MAHAKARYA_DIGITAL_KEY_HASH';
$spSig = 'SIGNED_BY_MANDIRI_LEGAL_SEAL_HASH';
$contractStatus = 'Aktif';

$stmtContract->bind_param("iisissssss", $propId, $roomId, $contractNum, $agreementAmount, $termsText, $eoSignTime, $spSignTime, $eoSig, $spSig, $contractStatus);
$stmtContract->execute();

$contractId = $conn->insert_id;

// Milestones untuk Kontrak 2 (30% - 40% - 30%)
$conn->query("INSERT INTO escrow_milestones (contract_id, milestone_number, milestone_title, percentage, amount, status, verification_notes, proof_file, proof_uploaded_at, released_at) VALUES 
($contractId, 1, 'Pembayaran Uang Muka (DP) & Komitmen Penandatanganan MoU', 30, 45000000, 'Dicairkan', 'Kontrak telah ditandatangani kedua belah pihak secara digital. Dana DP dicairkan ke rekening EO.', 'bukti_kontrak_signed.pdf', '2026-09-02 15:00:00', '2026-09-03 09:30:00'),
($contractId, 2, 'Verifikasi Kesiapan Venue, Perizinan & Kampanye Promosi Awal', 40, 60000000, 'Diverifikasi', 'Izin keramaian Mabes Polri & sewa Gambir Expo terlampir lengkap. Materi promosi telah terpasang di 20 billboard dan medsos.', 'bukti_venue_dan_izin.pdf', '2026-09-10 11:20:00', NULL),
($contractId, 3, 'Penyelesaian Event & Penyerahan Laporan Pertanggungjawaban (LPJ)', 30, 45000000, 'Pending', 'Menunggu pelaksanaan event pada tanggal 6-8 November 2026 dan submission laporan analitik.', NULL, NULL, NULL)
");
echo "✔ Kontrak aktif & 3 Milestone Escrow bertahap berhasil diinput.\n";

// 14. Event Analytics (Untuk Event Selesai: Aksi Tanam Mangrove - TokoHijau)
$benefitRealization = json_encode([
    ['benefit' => 'Penempatan Logo di Spanduk & Backdrop Utama', 'status' => 'Tercapai 100%', 'notes' => 'Terpasang di backdrop panggung dan 10 tenda edukasi'],
    ['benefit' => 'Sertifikat Penanaman Digital untuk Pelanggan', 'status' => 'Tercapai 100%', 'notes' => 'Terkirim ke 5.000 email donatur aplikasi TokoHijau'],
    ['benefit' => 'Liputan Media Partner Nasional', 'status' => 'Tercapai 120%', 'notes' => 'Masuk di 18 media nasional (target awal 15 media)'],
    ['benefit' => 'Dokumentasi Video Drone & Foto HD', 'status' => 'Tercapai 100%', 'notes' => 'Paket dokumentasi video 4K diserahkan via Google Drive']
]);

$docPhotos = json_encode([
    'assets/img/analytics/mangrove_1.jpg',
    'assets/img/analytics/mangrove_2.jpg',
    'assets/img/analytics/mangrove_3.jpg',
    'assets/img/analytics/mangrove_4.jpg'
]);

$stmtAnalytics = $conn->prepare("INSERT INTO event_analytics (event_id, sponsor_id, actual_attendees, social_impressions, engagement_rate, media_coverage_count, estimated_roi_pct, roi_multiplier, documentation_photos_json, benefit_realization_json, report_document, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$anEventId = 8;
$anSponsorId = 11;
$anAttendees = 5420;
$anImpressions = 845000;
$anEngagement = 8.40;
$anMediaCount = 18;
$anRoiPct = 385.00;
$anRoiMul = 3.85;
$anReportDoc = 'Laporan_Dampak_CSR_Mangrove_TokoHijau.pdf';
$anDate = '2026-08-28 16:00:00';

$stmtAnalytics->bind_param("iiiididdssss", $anEventId, $anSponsorId, $anAttendees, $anImpressions, $anEngagement, $anMediaCount, $anRoiPct, $anRoiMul, $docPhotos, $benefitRealization, $anReportDoc, $anDate);
$stmtAnalytics->execute();
echo "✔ Data Post-Event Impact Analytics berhasil diinput.\n";

// 15. Taksonomi Kategori, Industri, & CSR
$categoriesData = [
    // Event Categories
    ['Teknologi', 'event', 'cpu'],
    ['Musik & Konser', 'event', 'music'],
    ['Kampus & Mahasiswa', 'event', 'graduation-cap'],
    ['Olahraga & Kesehatan', 'event', 'activity'],
    ['Bisnis & Startup', 'event', 'briefcase'],
    ['Kuliner & Festival', 'event', 'utensils'],
    ['Lingkungan & Alam', 'event', 'tree-pine'],
    ['Seni & Kreatif', 'event', 'palette'],
    ['Gaming & Esports', 'event', 'gamepad-2'],
    ['Fashion & Gaya Hidup', 'event', 'sparkles'],

    // Industries
    ['Telekomunikasi & Digital', 'industry', 'smartphone'],
    ['Perbankan & Finansial', 'industry', 'landmark'],
    ['Food & Beverage', 'industry', 'coffee'],
    ['E-Commerce & Logistik', 'industry', 'shopping-bag'],
    ['Otomotif & Transportasi', 'industry', 'car'],
    ['Kecantikan & Personal Care', 'industry', 'sparkles'],
    ['Teknologi & Cloud', 'industry', 'cloud'],
    ['Kesehatan & Farmasi', 'industry', 'heart-pulse'],

    // CSR Focus
    ['Pendidikan & Beasiswa', 'csr', 'book-open'],
    ['Lingkungan & Nol Emisi', 'csr', 'leaf'],
    ['Pemberdayaan UMKM', 'csr', 'store'],
    ['Kesehatan Masyarakat', 'csr', 'activity'],
    ['Literasi Digital Pemuda', 'csr', 'code']
];

$stmtCat = $conn->prepare("INSERT INTO categories (name, type, icon) VALUES (?, ?, ?)");
foreach ($categoriesData as $c) {
    $stmtCat->bind_param("sss", $c[0], $c[1], $c[2]);
    $stmtCat->execute();
}
echo "✔ Taksonomi Kategori, Industri, dan CSR berhasil diinput.\n";

// 16. Notifikasi Awal
$notificationsData = [
    [2, 'Pembaruan Penawaran Negosiasi', 'PT TelkoNusa Tbk telah mengajukan penawaran baru senilai Rp 70.000.000 untuk Jakarta Tech Summit 2026.', 'negotiation.php?id=1', 0],
    [2, 'Dana Escrow Tahap 1 Telah Dicairkan', 'Dana DP termin 1 sebesar Rp 45.000.000 untuk Nusantara SoundFest telah berhasil masuk ke rekening Anda.', 'escrow.php?id=1', 1],
    [8, 'Pesan Baru di Ruang Negosiasi', 'Mahakarya Studio Promosindo menyetujui penawaran sponsor Anda dan mengunggah draf rundown baru.', 'negotiation.php?id=1', 0],
    [9, 'Dokumen Persiapan Event Telah Diunggah', 'EO telah mengunggah bukti perizinan dan kesiapan venue untuk SoundFest Carnival 2026.', 'escrow.php?id=1', 0],
    [1, 'Verifikasi Akun Baru Membutuhkan Review', 'Organisasi Yayasan Hutan Lestari dan NutriBoost Herbal Health menunggu verifikasi dokumen legalitas.', 'admin/users.php', 0]
];

$stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, is_read) VALUES (?, ?, ?, ?, ?)");
foreach ($notificationsData as $n) {
    $stmtNotif->bind_param("isssi", $n[0], $n[1], $n[2], $n[3], $n[4]);
    $stmtNotif->execute();
}
echo "✔ Data Notifikasi awal berhasil diinput.\n";

echo "\n=== SETUP DATABASE & SEEDING SELESAI DENGAN SUKSES! ===\n";
