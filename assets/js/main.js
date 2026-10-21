/**
 * assets/js/main.js - SponsorBridge Interactive UI & Helper Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Sidebar / Menu Toggle
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
      if (sidebarOverlay) {
        sidebarOverlay.classList.toggle('hidden');
      }
    });

    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
      });
    }
  }

  // 2. Notification Dropdown Toggle
  const notifBtn = document.getElementById('notifDropdownBtn');
  const notifMenu = document.getElementById('notifDropdownMenu');
  if (notifBtn && notifMenu) {
    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      notifMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => {
      notifMenu.classList.add('hidden');
    });
  }

  // 3. User Profile Dropdown Toggle
  const userMenuBtn = document.getElementById('userMenuBtn');
  const userMenu = document.getElementById('userMenuDropdown');
  if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      userMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => {
      userMenu.classList.add('hidden');
    });
  }

  // 4. Modal Open & Close Helper
  window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      document.body.style.overflow = '';
    }
  };

  // Close modals on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.sb-modal:not(.hidden)').forEach(modal => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      });
      document.body.style.overflow = '';
    }
  });

  // 5. Inisialisasi Canvas Tanda Tangan Digital (Digital Signature Pad)
  const sigCanvas = document.getElementById('signaturePadCanvas');
  if (sigCanvas) {
    const ctx = sigCanvas.getContext('2d');
    let isDrawing = false;
    let hasDrawn = false;

    // Set resolusi kanvas
    function resizeCanvas() {
      const rect = sigCanvas.getBoundingClientRect();
      sigCanvas.width = rect.width;
      sigCanvas.height = rect.height;
      ctx.strokeStyle = '#0f172a';
      ctx.lineWidth = 2.5;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    function startDraw(e) {
      isDrawing = true;
      hasDrawn = true;
      const pos = getPos(e);
      ctx.beginPath();
      ctx.moveTo(pos.x, pos.y);
    }

    function draw(e) {
      if (!isDrawing) return;
      e.preventDefault();
      const pos = getPos(e);
      ctx.lineTo(pos.x, pos.y);
      ctx.stroke();
    }

    function stopDraw() {
      isDrawing = false;
      const signatureInput = document.getElementById('signatureDataInput');
      if (signatureInput && hasDrawn) {
        signatureInput.value = sigCanvas.toDataURL('image/png');
      }
    }

    function getPos(e) {
      const rect = sigCanvas.getBoundingClientRect();
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;
      return {
        x: clientX - rect.left,
        y: clientY - rect.top
      };
    }

    sigCanvas.addEventListener('mousedown', startDraw);
    sigCanvas.addEventListener('mousemove', draw);
    sigCanvas.addEventListener('mouseup', stopDraw);
    sigCanvas.addEventListener('mouseleave', stopDraw);

    sigCanvas.addEventListener('touchstart', startDraw, { passive: false });
    sigCanvas.addEventListener('touchmove', draw, { passive: false });
    sigCanvas.addEventListener('touchend', stopDraw);

    const clearBtn = document.getElementById('clearSignatureBtn');
    if (clearBtn) {
      clearBtn.addEventListener('click', (e) => {
        e.preventDefault();
        ctx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        hasDrawn = false;
        const signatureInput = document.getElementById('signatureDataInput');
        if (signatureInput) signatureInput.value = '';
      });
    }
  }

  // 6. Interactive Real-Time ROI Calculator
  const roiAttendeesInput = document.getElementById('roiAttendees');
  const roiSocialPostsInput = document.getElementById('roiSocialPosts');
  const roiImpressionsInput = document.getElementById('roiImpressions');
  const roiPackageCostInput = document.getElementById('roiPackageCost');

  function updateRoiCalculations() {
    if (!roiAttendeesInput || !roiPackageCostInput) return;

    const attendees = parseInt(roiAttendeesInput.value) || 0;
    const socialPosts = parseInt(roiSocialPostsInput ? roiSocialPostsInput.value : 5) || 0;
    const impressions = parseInt(roiImpressionsInput ? roiImpressionsInput.value : 100000) || 0;
    const packageCost = Math.max(1, parseFloat(roiPackageCostInput.value) || 50000000);

    // CPM formula: Rp 45.000 / 1000 impressions
    const onlineValue = (impressions / 1000) * 45000;
    // Onsite exposure: Rp 25.000 / attendee
    const onsiteValue = attendees * 25000;
    // Social content post: Rp 150.000 / official post
    const socialValue = socialPosts * 150000;

    const totalMediaValue = onlineValue + onsiteValue + socialValue;
    const roiMultiplier = (totalMediaValue / packageCost).toFixed(2);
    const roiPercentage = (((totalMediaValue - packageCost) / packageCost) * 100).toFixed(1);

    const formatRp = (num) => 'Rp ' + Math.round(num).toLocaleString('id-ID');

    // Update elemen DOM jika ada
    const elOnline = document.getElementById('resOnlineValue');
    const elOnsite = document.getElementById('resOnsiteValue');
    const elSocial = document.getElementById('resSocialValue');
    const elTotal = document.getElementById('resTotalMediaValue');
    const elMultiplier = document.getElementById('resRoiMultiplier');
    const elPct = document.getElementById('resRoiPercentage');

    if (elOnline) elOnline.innerText = formatRp(onlineValue);
    if (elOnsite) elOnsite.innerText = formatRp(onsiteValue);
    if (elSocial) elSocial.innerText = formatRp(socialValue);
    if (elTotal) elTotal.innerText = formatRp(totalMediaValue);
    if (elMultiplier) elMultiplier.innerText = roiMultiplier + 'x';
    if (elPct) {
      elPct.innerText = (roiPercentage >= 0 ? '+' : '') + roiPercentage + '%';
      if (roiPercentage >= 0) {
        elPct.className = 'text-2xl font-black text-emerald-600';
      } else {
        elPct.className = 'text-2xl font-black text-rose-600';
      }
    }
  }

  if (roiAttendeesInput) {
    [roiAttendeesInput, roiSocialPostsInput, roiImpressionsInput, roiPackageCostInput].forEach(input => {
      if (input) {
        input.addEventListener('input', updateRoiCalculations);
      }
    });
    updateRoiCalculations();
  }

  // 7. Auto-scroll Chat Feed di Ruang Negosiasi
  const chatFeed = document.getElementById('negotiationChatFeed');
  if (chatFeed) {
    chatFeed.scrollTop = chatFeed.scrollHeight;
  }
});
