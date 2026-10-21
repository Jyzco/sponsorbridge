<?php
// sponsors.php - Marketplace Sponsor / Direktori Brand
$page_title = "Marketplace Sponsor & Brand";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Filter parameter
$search = clean_input($_GET['search'] ?? '');
$industry = clean_input($_GET['industry'] ?? '');
$city = clean_input($_GET['city'] ?? '');
$csr = clean_input($_GET['csr'] ?? '');

$sql = "SELECT sp.*, u.name as brand_name, u.email as brand_email, u.phone as brand_phone, u.is_verified
        FROM sponsor_profiles sp
        JOIN users u ON sp.user_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (sp.company_name LIKE ? OR sp.description LIKE ? OR sp.target_audience LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = &$likeSearch;
    $params[] = &$likeSearch;
    $params[] = &$likeSearch;
    $types .= "sss";
}

if (!empty($industry)) {
    $sql .= " AND sp.industry = ?";
    $params[] = &$industry;
    $types .= "s";
}

if (!empty($city)) {
    $sql .= " AND sp.city = ?";
    $params[] = &$city;
    $types .= "s";
}

if (!empty($csr)) {
    $sql .= " AND sp.csr_focus LIKE ?";
    $likeCsr = "%$csr%";
    $params[] = &$likeCsr;
    $types .= "s";
}

$sql .= " ORDER BY sp.max_budget DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$sponsors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil list industri & kota unik
$industryList = $conn->query("SELECT DISTINCT industry FROM sponsor_profiles WHERE industry != '' ORDER BY industry ASC")->fetch_all(MYSQLI_ASSOC);
$cityList = $conn->query("SELECT DISTINCT city FROM sponsor_profiles WHERE city != '' ORDER BY city ASC")->fetch_all(MYSQLI_ASSOC);
?>

<main class="flex-grow py-8 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Page -->
        <div class="mb-8">
            <span class="text-xs font-bold uppercase tracking-widest text-brand-purple">Direktori Kemitraan</span>
            <h1 class="text-2xl sm:text-3xl font-black text-navy-900 tracking-tight mt-1">Marketplace Sponsor & Brand</h1>
            <p class="text-xs text-slate-500 mt-1">Temukan perusahaan dan brand aktif yang mencari peluang aktivasi pemasaran, sponsorship event, dan alokasi dana CSR.</p>
        </div>

        <!-- Filter Bar -->
        <div class="sb-card p-5 mb-8 bg-white">
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Cari Brand / Kata Kunci</label>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                            <input type="text" name="search" value="<?= clean_input($search) ?>" placeholder="Nama brand, audiens..." 
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-purple/30 focus:border-brand-purple">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Sektor Industri</label>
                        <select name="industry" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-purple/30 focus:border-brand-purple bg-white">
                            <option value="">Semua Industri</option>
                            <?php foreach ($industryList as $ind): ?>
                                <option value="<?= clean_input($ind['industry']) ?>" <?= $industry === $ind['industry'] ? 'selected' : '' ?>>
                                    <?= clean_input($ind['industry']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Kota Kantor Pusat</label>
                        <select name="city" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-purple/30 focus:border-brand-purple bg-white">
                            <option value="">Semua Kota</option>
                            <?php foreach ($cityList as $c): ?>
                                <option value="<?= clean_input($c['city']) ?>" <?= $city === $c['city'] ? 'selected' : '' ?>>
                                    <?= clean_input($c['city']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Fokus Program CSR</label>
                        <input type="text" name="csr" value="<?= clean_input($csr) ?>" placeholder="Contoh: Digital, Lingkungan, UMKM" 
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-purple/30 focus:border-brand-purple">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <span class="text-xs text-slate-500 font-medium">Ditemukan <strong><?= count($sponsors) ?></strong> brand terverifikasi.</span>
                    <div class="flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/sponsors.php" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100">Reset</a>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-brand-purple hover:bg-brand-purple/90 shadow-sm transition">
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sponsor Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($sponsors as $sp): ?>
            <div class="sb-card p-6 flex flex-col justify-between hover:border-brand-purple/50">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-navy-900 to-brand-purple text-white font-black text-lg flex items-center justify-center shadow-sm">
                            <?= strtoupper(substr($sp['company_name'], 0, 1)) ?>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-brand-purple border border-purple-200">
                                <?= clean_input($sp['industry']) ?>
                            </span>
                            <?php if ($sp['is_verified']): ?>
                                <span class="text-[10px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Terverifikasi
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h3 class="text-base font-extrabold text-navy-900"><?= clean_input($sp['company_name']) ?></h3>
                    <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed"><?= clean_input($sp['description']) ?></p>

                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2.5 text-xs text-slate-600">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Alokasi Anggaran:</span>
                            <span class="font-bold text-emerald-600"><?= format_rupiah($sp['min_budget']) ?> - <?= format_rupiah($sp['max_budget']) ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Target Audiens:</span>
                            <span class="text-slate-700 line-clamp-1"><?= clean_input($sp['target_audience']) ?></span>
                        </div>
                        <?php if (!empty($sp['csr_focus'])): ?>
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Pilar CSR:</span>
                            <span class="text-emerald-700 line-clamp-1"><i class="fa-solid fa-leaf text-emerald-500 mr-1"></i><?= clean_input($sp['csr_focus']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-5 mt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                    <a href="<?= BASE_URL ?>/sponsor-detail.php?id=<?= $sp['user_id'] ?>" class="py-2 rounded-xl text-xs font-bold text-center block text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        Detail Profil
                    </a>
                    <a href="<?= BASE_URL ?>/proposal-builder.php?sponsor_id=<?= $sp['user_id'] ?>" class="py-2 rounded-xl text-xs font-bold text-center block text-white bg-brand-purple hover:bg-brand-purple/90 transition shadow-sm">
                        Kirim Proposal
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
