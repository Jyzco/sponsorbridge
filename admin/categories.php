<?php
// admin/categories.php - Pengelolaan Taksonomi Kategori Event, Industri Brand, & Pilar CSR
$page_title = "Manajemen Taksonomi";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

require_role('admin');

// Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = clean_input($_POST['name'] ?? '');
    $type = clean_input($_POST['type'] ?? 'event');
    $icon = clean_input($_POST['icon'] ?? 'tag');

    if (!empty($name) && in_array($type, ['event', 'industry', 'csr'])) {
        $stmt = $conn->prepare("INSERT INTO categories (name, type, icon) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $type, $icon);
        $stmt->execute();
        set_flash('success', "Taksonomi '{$name}' berhasil ditambahkan.");
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

// Hapus Kategori
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM categories WHERE id = {$delId}");
    set_flash('info', 'Item taksonomi dihapus.');
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

$eventCats = $conn->query("SELECT * FROM categories WHERE type = 'event' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$industryCats = $conn->query("SELECT * FROM categories WHERE type = 'industry' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$csrCats = $conn->query("SELECT * FROM categories WHERE type = 'csr' ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex-grow flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="flex-1 lg:pl-64 p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen">
        <div class="max-w-5xl mx-auto space-y-6">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-amber-500">Master Data Platform</span>
                    <h1 class="text-2xl font-black text-navy-900 tracking-tight mt-1">Taksonomi Kategori, Industri & CSR</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kelola klasifikasi standar untuk rekomendasi kecocokan AI dan filter pencarian.</p>
                </div>

                <button onclick="openModal('addCatModal')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-navy-900 hover:bg-navy-800 transition shadow-sm flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Tambah Taksonomi Baru
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Kategori Event -->
                <div class="sb-card p-5 bg-white space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h3 class="text-xs font-black uppercase text-navy-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-calendar text-brand-indigo"></i> Kategori Event
                        </h3>
                        <span class="text-[10px] text-slate-400 font-bold"><?= count($eventCats) ?> Item</span>
                    </div>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($eventCats as $c): ?>
                        <div class="py-2 flex items-center justify-between">
                            <span class="font-bold text-slate-700"><?= clean_input($c['name']) ?></span>
                            <a href="?delete_id=<?= $c['id'] ?>" onclick="return confirm('Hapus kategori ini?')" class="text-slate-300 hover:text-rose-500">
                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sektor Industri Brand -->
                <div class="sb-card p-5 bg-white space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h3 class="text-xs font-black uppercase text-navy-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-building text-brand-purple"></i> Sektor Industri Brand
                        </h3>
                        <span class="text-[10px] text-slate-400 font-bold"><?= count($industryCats) ?> Item</span>
                    </div>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($industryCats as $c): ?>
                        <div class="py-2 flex items-center justify-between">
                            <span class="font-bold text-slate-700"><?= clean_input($c['name']) ?></span>
                            <a href="?delete_id=<?= $c['id'] ?>" onclick="return confirm('Hapus industri ini?')" class="text-slate-300 hover:text-rose-500">
                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pilar Program CSR -->
                <div class="sb-card p-5 bg-white space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h3 class="text-xs font-black uppercase text-navy-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-leaf text-emerald-600"></i> Fokus Pilar CSR
                        </h3>
                        <span class="text-[10px] text-slate-400 font-bold"><?= count($csrCats) ?> Item</span>
                    </div>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($csrCats as $c): ?>
                        <div class="py-2 flex items-center justify-between">
                            <span class="font-bold text-slate-700"><?= clean_input($c['name']) ?></span>
                            <a href="?delete_id=<?= $c['id'] ?>" onclick="return confirm('Hapus fokus CSR ini?')" class="text-slate-300 hover:text-rose-500">
                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<!-- Modal Tambah Taksonomi -->
<div id="addCatModal" class="sb-modal hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 items-center justify-center p-4">
    <div class="sb-card max-w-md w-full p-6 bg-white shadow-2xl">
        <h3 class="text-base font-black text-navy-900 mb-1">Tambah Item Taksonomi Baru</h3>
        <p class="text-xs text-slate-500 mb-4">Tambahkan kategori baru ke dalam sistem filter & AI matching.</p>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="add_category" value="1">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tipe Taksonomi</label>
                <select name="type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30 bg-white">
                    <option value="event">Kategori Event</option>
                    <option value="industry">Sektor Industri Brand</option>
                    <option value="csr">Fokus Pilar CSR</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Taksonomi *</label>
                <input type="text" name="name" required placeholder="Contoh: Otomotif EV, Seni Rupa, dsb..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-brand-indigo/30">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('addCatModal')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-navy-900 hover:bg-navy-800 shadow-sm">Simpan Taksonomi</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
