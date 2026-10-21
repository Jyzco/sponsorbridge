<?php
// roi-calculator.php - Kalkulator Estimasi ROI Sponsor Standalone
$page_title = "Kalkulator Estimasi ROI Sponsor";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow py-8 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 text-center">
            <span class="text-xs font-bold uppercase tracking-widest text-brand-indigo">B2B Valuation Tool</span>
            <h1 class="text-2xl sm:text-3xl font-black text-navy-900 tracking-tight mt-1">Kalkulator Estimasi ROI Sponsorship</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl mx-auto">
                Hitung proyeksi <strong>Earned Media Value (EMV)</strong> dan return on investment sponsor berdasarkan estimasi peserta, exposure tatap muka, dan impresi digital.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            
            <!-- Input Parameters -->
            <div class="sb-card p-6 bg-white space-y-5">
                <h3 class="text-sm font-black text-navy-900 border-b border-slate-100 pb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-brand-indigo"></i> Parameter Input Event
                </h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" for="roiAttendees">
                        Estimasi Jumlah Pengunjung Fisik
                    </label>
                    <input type="number" id="roiAttendees" value="15000" min="100" step="500"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    <span class="text-[10px] text-slate-400 mt-1 block">Rata-rata audiens hadir langsung di venue acara.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" for="roiSocialPosts">
                        Jumlah Postingan Media Sosial Resmi
                    </label>
                    <input type="number" id="roiSocialPosts" value="10" min="1" step="1"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    <span class="text-[10px] text-slate-400 mt-1 block">Feed Instagram, Reels, TikTok, dan rilis resmi.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" for="roiImpressions">
                        Total Estimasi Impresi Digital (Views & Reach)
                    </label>
                    <input type="number" id="roiImpressions" value="500000" min="1000" step="10000"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-navy-900 focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    <span class="text-[10px] text-slate-400 mt-1 block">Gabungan jangkauan media sosial & digital campaign.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1" for="roiPackageCost">
                        Nilai Paket Investasi Sponsor (Rp)
                    </label>
                    <input type="number" id="roiPackageCost" value="75000000" min="1000000" step="5000000"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-black text-brand-indigo focus:ring-2 focus:ring-brand-indigo/30 focus:border-brand-indigo">
                    <span class="text-[10px] text-slate-400 mt-1 block">Besaran dana yang diajukan kepada pihak sponsor.</span>
                </div>
            </div>

            <!-- Output Real-Time Results -->
            <div class="sb-card p-6 bg-gradient-to-br from-navy-900 via-navy-800 to-indigo-950 text-white flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-navy-700 pb-3 mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Hasil Analisis Nilai B2B</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-white/10 text-slate-200">Formula CPM Industri</span>
                    </div>

                    <!-- Highlight ROI Multiplier -->
                    <div class="p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-center mb-6">
                        <span class="text-xs text-slate-300 font-semibold uppercase tracking-wider block mb-1">Proyeksi ROI Multiplier</span>
                        <div class="text-4xl sm:text-5xl font-black text-emerald-400" id="resRoiMultiplier">5.33x</div>
                        <div class="text-xs font-bold text-emerald-300 mt-1">
                            Pertumbuhan Nilai: <span id="resRoiPercentage">+433.3%</span>
                        </div>
                    </div>

                    <!-- Breakdown Components -->
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-navy-700/80">
                            <span class="text-slate-300">1. Nilai Impresi Digital (CPM):</span>
                            <strong class="text-white" id="resOnlineValue">Rp 22.500.000</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-navy-700/80">
                            <span class="text-slate-300">2. Nilai Eksposur Fisik (On-Site):</span>
                            <strong class="text-white" id="resOnsiteValue">Rp 375.000.000</strong>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-navy-700/80">
                            <span class="text-slate-300">3. Nilai Konten Media Sosial:</span>
                            <strong class="text-white" id="resSocialValue">Rp 1.500.000</strong>
                        </div>
                        <div class="flex justify-between py-2 text-sm font-extrabold text-indigo-300 pt-2">
                            <span>Total Nilai Earned Media (EMV):</span>
                            <span class="text-white text-base" id="resTotalMediaValue">Rp 399.000.000</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-navy-700 mt-4 text-[11px] text-slate-300 italic">
                    💡 Gunakan angka kalkulasi ini pada dokumen Smart Proposal Builder untuk meningkatkan peluang persetujuan dari divisi marketing dan CSR brand.
                </div>
            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
