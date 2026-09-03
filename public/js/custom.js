/* ============================================================
   VoterApp — Custom Component & Election Telemetry JS
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('open');
        });
    }

    sidebar?.querySelectorAll('.sidebar-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    });

    // 2. Smart File Size & Estimated Time Calculation Component
    initFileUploadInspectors();

    // 3. Form Submit Preloader Trigger
    initFormPreloaders();
});

/**
 * Automatically inspects any file upload input and calculates:
 * - Formatted Size (KB / MB)
 * - Estimated records count (e.g. for voter list or delimitation)
 * - Estimated upload & indexing time
 */
function initFileUploadInspectors() {
    const fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(function (input) {
        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            // Find or create preview container
            let previewCard = input.parentElement.querySelector('.file-preview-card');
            if (!previewCard) {
                previewCard = document.createElement('div');
                previewCard.className = 'file-preview-card';
                input.parentElement.appendChild(previewCard);
            }

            const sizeBytes = file.size;
            let formattedSize = '';
            let estRecords = 0;
            let estSeconds = '1 - 2s';

            if (sizeBytes < 1024 * 1024) {
                const kb = (sizeBytes / 1024).toFixed(1);
                formattedSize = kb + ' KB';
                estRecords = Math.round(sizeBytes / 70); // avg ~70 bytes per voter CSV row
                estSeconds = kb < 300 ? '1 - 2 sec' : '2 - 3 sec';
            } else {
                const mb = (sizeBytes / (1024 * 1024)).toFixed(2);
                formattedSize = mb + ' MB';
                estRecords = Math.round(sizeBytes / 75);
                estSeconds = (mb * 2).toFixed(0) + ' - ' + (mb * 3.5).toFixed(0) + ' sec';
            }

            const fileExt = file.name.split('.').pop().toUpperCase();

            previewCard.style.display = 'block';
            previewCard.innerHTML = `
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="w-8 h-8 rounded bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold small">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">${file.name}</div>
                            <small class="text-muted"><span class="badge bg-light text-dark border me-1">${fileExt}</span> Size: <strong>${formattedSize}</strong></small>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success font-monospace"><i class="bi bi-speedometer2 me-1"></i>Est. ~${estRecords.toLocaleString()} rows</span>
                        <div class="small text-muted mt-1"><i class="bi bi-hourglass-split me-1"></i>Est. Processing: <strong class="text-dark">${estSeconds}</strong></div>
                    </div>
                </div>
            `;
        });
    });
}

/**
 * Triggers full-screen preloader on import/export and heavy data submissions
 */
function initFormPreloaders() {
    const heavyForms = document.querySelectorAll('form[enctype="multipart/form-data"], form.with-preloader');
    
    heavyForms.forEach(function (form) {
        form.addEventListener('submit', function () {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing Import...';
            }
            window.VoterAppLoader?.show('Validating file & importing electoral records into database...');
        });
    });
}

// Global Preloader Helper
window.VoterAppLoader = {
    show: function (customMessage) {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            const msgEl = loader.querySelector('#pageLoaderMsg');
            if (msgEl && customMessage) {
                msgEl.innerText = customMessage;
            }
            loader.classList.remove('d-none');
        }
    },
    hide: function () {
        document.getElementById('pageLoader')?.classList.add('d-none');
    },
};

// Toastr Config
if (typeof toastr !== 'undefined') {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 4500,
        extendedTimeOut: 2000,
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
}
