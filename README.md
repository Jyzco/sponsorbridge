# SponsorBridge - B2B Sponsorship Marketplace Platform

**SponsorBridge** adalah platform web marketplace sponsorship modern, profesional, dan responsif yang menghubungkan **Event Organizer (EO)** yang membutuhkan pendanaan dengan **Perusahaan/Brand (Sponsor)** yang mencari eksposur promosi dan program Corporate Social Responsibility (CSR) berdampak.

Platform ini dibangun mengikuti standar teknis **BINUS University - Web Programming**:
- **Frontend**: HTML5, CSS3, JavaScript modern (Tailwind CSS, FontAwesome 6, Chart.js, Canvas Digital Signature, Confetti).
- **Backend**: Native PHP 8+ (No Frameworks).
- **Database**: MySQL / MariaDB menggunakan ekstensi **MySQLi** (Prepared Statements & Anti-SQL Injection).
- **Struktur Folder Terstandar**: `/assets`, `/includes`, `/database`, `/admin`, dan root views.
- **Reusable Includes**: `header.php`, `footer.php`, `navbar.php`, `sidebar.php`, `db.php`, `auth.php`, `functions.php`.
- **Keamanan**: Sanitasi input XSS (`htmlspecialchars`), hashing password BCRYPT, perlindungan sesi, dan arsitektur privasi End-to-End Encryption (E2EE) di mana administrator tidak dapat membaca pesan obrolan privat.

---

## 🚀 Cara Menjalankan Aplikasi

Aplikasi dapat dijalankan melalui dua cara praktis:

### Opsi 1: Menggunakan XAMPP (Apache & MySQL)
1. Buka **XAMPP Control Panel**, lalu aktifkan modul **Apache** dan **MySQL**.
2. Folder proyek ini telah tersambung secara otomatis ke direktori XAMPP melalui junction di:
   `C:\xampp\htdocs\sponsorbridge`
3. Buka browser dan akses alamat:
   👉 **`http://localhost/sponsorbridge/`**

### Opsi 2: Menggunakan PHP Built-in Web Server
Jika ingin menjalankan secara mandiri tanpa Apache:
```bash
# Pastikan MySQL di XAMPP aktif, lalu jalankan di terminal:
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t "C:\Users\jaydu\.gemini\antigravity\scratch\sponsorbridge"
```
Buka browser dan akses:
👉 **`http://127.0.0.1:8000/`**

---

## 🔑 Akun Demo Pengujian (Tiga Peran)

Untuk mempermudah pengujian dosen dan reviewer, di bagian paling atas halaman telah disediakan **"Top Demo Switcher Bar"** sekali-klik, atau Anda dapat masuk secara manual melalui halaman `/login.php`:

| Peran | Nama Akun | Email | Kata Sandi | Deskripsi |
|---|---|---|---|---|
| **1. Event Organizer (EO)** | Mahakarya Studio Promosindo | `eo@mahakarya.id` | `password123` | Mengelola konser musik, membuat event, mengajukan proposal B2B & mencairkan escrow |
| **2. Sponsor / Brand** | PT TelkoNusa Tbk | `partnership@telkonusa.co.id` | `password123` | Perusahaan telekomunikasi, mereview proposal, negosiasi E2EE, dan memantau analitik ROI |
| **3. Administrator** | SponsorBridge Administrator | `admin@sponsorbridge.id` | `admin123` | Memverifikasi akun, memoderasi event, menangani dispute, dan memantau metadata E2EE |

---

## 📁 Struktur Direktori Proyek

```
sponsorbridge/
├── admin/                     # Panel Administrator Platform
│   ├── index.php              # Dashboard Statistik Super Admin
│   ├── users.php              # Verifikasi Legalitas Akun EO & Sponsor
│   ├── events.php             # Moderasi Event & Kurasi Unggulan
│   ├── disputes.php           # Pusat Resolusi Sengketa Pembayaran Escrow
│   ├── negotiations.php       # Pemantauan Metadata Room (Privasi E2EE)
│   └── categories.php         # Manajemen Taksonomi & CSR
├── assets/                    # Aset Statis
│   ├── css/
│   │   └── style.css          # Desain Tema B2B Navy, Putih & Ungu
│   ├── js/
│   │   └── main.js            # Interaktivitas Signature Pad, ROI Calc, Chat
│   ├── img/                   # Foto dokumentasi & ilustrasi
│   └── uploads/               # File lampiran & dokumen
├── database/                  # Skema & Inisialisasi Database
│   ├── schema.sql             # Skema 14 tabel MySQL
│   ├── setup.php              # Script otomatis inisialisasi & seeding data
├── includes/                  # Reusable PHP Includes
│   ├── db.php                 # Koneksi MySQLi terpusat
│   ├── auth.php               # Manajemen sesi & role-based authentication
│   ├── functions.php          # Sanitasi input, format rupiah, match score AI
│   ├── header.php             # Header template + Top Demo Switcher
│   ├── navbar.php             # Navigasi sticky dengan notifikasi dropdown
│   ├── sidebar.php            # Navigasi sidebar dinamis sesuai role
│   └── footer.php             # Footer profesional dengan trust badges
├── analytics.php              # Dashboard Post-Event Impact Analytics
├── contract.php               # MoU Generator & Digital Signature Pad
├── create-event.php           # Wizard Buat Event & 4 Tier Paket Sponsorship
├── dashboard.php              # Dashboard dinamis (EO / Sponsor)
├── escrow.php                 # Pusat Escrow Payment 3 Tahap (30-40-30)
├── event-detail.php           # Halaman Detail Event & Paket Tiering
├── events.php                 # Event Discovery & Rekomendasi AI Match
├── index.php                  # Public Landing Page & Alur Cara Kerja
├── login.php                  # Halaman Masuk dengan Quick Auto-Fill Demo
├── logout.php                 # Handler Keluar Sesi
├── negotiation.php            # Negotiation Room (E2EE Chat & Counter-Offer)
├── notifications.php          # Pusat Notifikasi Pengguna
├── post-event-report.php      # Form Pengisian Hasil Event (LPJ)
├── profile.php                # Profil Pengguna, Organisasi, dan Rekening Bank
├── proposal-builder.php       # Smart Proposal Builder 8 Struktur & Kalkulator ROI
├── proposal-detail.php        # Tinjauan Detail Proposal & Keputusan Sponsor
├── proposals.php              # Pelacak Proposal (9 Tahapan Pipeline)
├── register.php               # Registrasi dengan Pemilihan Peran
├── roi-calculator.php         # Kalkulator Estimasi ROI Sponsor Interaktif
├── sponsor-detail.php         # Profil Brand & Kriteria Kemitraan
├── sponsors.php               # Marketplace Sponsor & Direktori Brand
└── switch_role.php            # Handler 1-Klik Beralih Peran Demo
```

---

## ✨ Fitur-Fitur Utama

1. **Tampilan Publik Modern**:
   - Hero Section: *"Temukan Partner Sponsorship untuk Event Anda"*.
   - Cara kerja interaktif dengan tab ganda (Untuk EO vs Untuk Brand).
   - Katalog Event Unggulan dan Brand Populer.
   - Testimoni nyata B2B dan CTA pendaftaran akun.
2. **Event Organizer (EO)**:
   - Dashboard: Total Event, Proposal Aktif, Proposal Diterima, Dana Terkumpul.
   - Buat Event: Informasi umum, jadwal, estimasi pengunjung, target sponsorship, dan otomatis membuat 4 paket sponsorship (**Bronze, Silver, Gold, Platinum**).
   - **Smart Proposal Builder**: 8 struktur terstandar (Info event, Latar belakang, Sasaran audiens, Rencana promosi multichannel, Paket sponsorship, Rincian benefit, Anggaran, Lampiran).
   - **Kalkulator Estimasi ROI Sponsor**: Menghitung Cost Per Mille (CPM), Media Value, exposure fisik, dan skor proyeksi multiplier return.
   - **Pelacak Proposal 9 Tahapan**: *Draft -> Dikirim -> Dibaca -> Review -> Negosiasi -> Diterima -> Ditolak -> Kontrak Ditandatangani -> Selesai*.
3. **Sponsor / Brand**:
   - Dashboard: Rekomendasi AI Match, kotak masuk proposal, kontrak aktif, dan total investasi.
   - **Event Discovery**: Filter kategori, lokasi kota, minimal pengunjung, dan anggaran.
   - **Sistem Rekomendasi AI Match**: Skor persentase kecocokan (contoh: *96% Cocok*) beserta alasan analisis demografi audiens dan keselarasan CSR.
   - Review Proposal: Terima penawaran, tolak dengan catatan, atau buka negosiasi.
4. **Negotiation Room (E2EE Encrypted)**:
   - Ruang diskusi khusus terenkripsi End-to-End per proposal.
   - Chat interaktif, riwayat penawaran, penyesuaian tier benefit, dan lampiran file.
   - Tombol *"Ajukan Penawaran Baru"* (counter-offer) dan *"Buat Kesepakatan Deal"*.
   - **Garansi Privasi**: Admin platform **TIDAK BISA** melihat isi obrolan (hanya metadata).
5. **Kontrak MoU & Simulasi Tanda Tangan Digital**:
   - Generator dokumen hukum MoU resmi berdasarkan kesepakatan negosiasi.
   - Kanvas tanda tangan digital interaktif (mendukung mouse stroke dan sentuhan layar) dengan segel verifikasi SHA-256.
   - Perayaan confetti saat kontrak aktif ditandatangani kedua belah pihak.
6. **Escrow Payment Bertahap (30% - 40% - 30%)**:
   - **Termin 1 (30%):** Uang Muka (DP) setelah MoU ditandatangani.
   - **Termin 2 (40%):** Pencairan setelah kesiapan venue, perizinan, dan promosi diverifikasi.
   - **Termin 3 (30%):** Pelunasan setelah acara selesai dan laporan dampak diserahkan.
   - Status milestone: *Pending, Dana Masuk, Diverifikasi, Dicairkan, Disengketakan*.
7. **Post-Event Impact Analytics**:
   - Dashboard evaluasi hasil acara: Audiens aktual vs target, jangkauan impresi media sosial, engagement rate, dan publikasi pers.
   - Grafik interaktif Chart.js dan checklist realisasi benefit sponsorship 100%.
   - Galeri foto dokumentasi penempatan logo dan aktivasi booth.
8. **Admin Panel**:
   - Metrik komprehensif platform: Pengguna, event, proposal, transaksi escrow, dan sengketa.
   - Verifikasi legalitas organisasi EO dan Sponsor.
   - Moderasi event dan kurasi status unggulan.
   - Pusat mediasi dan resolusi sengketa dana escrow.
   - Pemantauan metadata ruang negosiasi tanpa melanggar privasi enkripsi E2EE.
