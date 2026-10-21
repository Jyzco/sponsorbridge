<?php
// includes/footer.php - Footer Publik & Penutup Dokumen
?>
    <!-- Global Footer -->
    <footer class="bg-navy-950 text-slate-400 text-xs border-t border-navy-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                
                <!-- Brand Col -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-indigo to-brand-purple flex items-center justify-center text-white shadow-md">
                            <i class="fa-solid fa-handshake-angle text-base"></i>
                        </div>
                        <span class="text-xl font-black text-white tracking-tight">Sponsor<span class="text-brand-indigo">Bridge</span></span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed max-w-sm">
                        Platform B2B marketplace sponsorship terpercaya di Indonesia. Menghubungkan Event Organizer dengan perusahaan dan brand melalui smart matching, ruang negosiasi E2EE, dan pembayaran bertahap escrow aman.
                    </p>
                    <div class="flex items-center gap-4 text-slate-400">
                        <span class="flex items-center gap-1.5 text-xs text-emerald-400 font-semibold">
                            <i class="fa-solid fa-shield-halved"></i> E2EE Encrypted Chat
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-sky-400 font-semibold">
                            <i class="fa-solid fa-vault"></i> 3-Stage Escrow Protection
                        </span>
                    </div>
                </div>

                <!-- EO Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-3">Event Organizer</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= BASE_URL ?>/create-event.php" class="hover:text-white transition">Buat Event Baru</a></li>
                        <li><a href="<?= BASE_URL ?>/proposal-builder.php" class="hover:text-white transition">Smart Proposal Builder</a></li>
                        <li><a href="<?= BASE_URL ?>/roi-calculator.php" class="hover:text-white transition">Kalkulator ROI Sponsor</a></li>
                        <li><a href="<?= BASE_URL ?>/sponsors.php" class="hover:text-white transition">Direktori Brand & CSR</a></li>
                        <li><a href="<?= BASE_URL ?>/proposals.php" class="hover:text-white transition">Pelacak Status Proposal</a></li>
                    </ul>
                </div>

                <!-- Sponsor Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-3">Brand & Sponsor</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= BASE_URL ?>/events.php" class="hover:text-white transition">Eksplorasi Event</a></li>
                        <li><a href="<?= BASE_URL ?>/events.php?view=match" class="hover:text-white transition">Rekomendasi AI Match</a></li>
                        <li><a href="<?= BASE_URL ?>/escrow.php" class="hover:text-white transition">Sistem Escrow Terjamin</a></li>
                        <li><a href="<?= BASE_URL ?>/analytics.php" class="hover:text-white transition">Impact Analytics Dashboard</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php?role=sponsor" class="hover:text-white transition">Daftar sebagai Brand</a></li>
                    </ul>
                </div>

                <!-- Keamanan & Regulasi -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-3">Keamanan & Mitra</h4>
                    <p class="text-[11px] text-slate-400 leading-relaxed mb-3">
                        Transaksi sponsorship diamankan dengan rekening escrow berizin dan tanda tangan digital terenkripsi SHA-256.
                    </p>
                    <div class="space-y-1.5 text-[10px] text-slate-400 font-mono">
                        <div><i class="fa-solid fa-lock text-emerald-400 mr-1.5"></i> SSL 256-bit Encryption</div>
                        <div><i class="fa-solid fa-check-double text-blue-400 mr-1.5"></i> Escrow Milestone 30-40-30</div>
                        <div><i class="fa-solid fa-user-shield text-amber-400 mr-1.5"></i> Admin E2EE Privacy Policy</div>
                    </div>
                </div>

            </div>

            <div class="border-t border-navy-800 mt-8 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                <p>&copy; <?= date('Y') ?> SponsorBridge Indonesia. All rights reserved. Tugas Proyek Pemrograman Web - BINUS University.</p>
                <div class="flex items-center gap-6">
                    <a href="#" class="hover:text-white transition">Syarat & Ketentuan</a>
                    <a href="#" class="hover:text-white transition">Kebijakan Privasi</a>
                    <a href="<?= BASE_URL ?>/admin/index.php" class="text-slate-400 hover:text-amber-400 transition font-mono text-[11px]">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Admin Portal
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Main JavaScript File -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
