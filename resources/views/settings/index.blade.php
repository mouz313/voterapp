@extends('layouts.app')

@section('title', 'Database Backup & System Settings')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">System Settings &amp; Database Backup</h4>
            <small class="text-muted">Manage full database exports, system backups, and maintenance routines</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-mono">
                <i class="bi bi-database-check me-1"></i> DB: {{ $db_name }}
            </span>
            <span class="badge bg-light text-dark border px-2.5 py-1.5 font-mono">
                <i class="bi bi-hdd me-1"></i> Approx Size: {{ $db_size }} MB
            </span>
        </div>
    </div>

    <!-- 1. FULL DATABASE BACKUP HERO CARD -->
    <div class="card shadow-sm border-0 border-start border-4 border-success mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success text-white d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; font-size: 1.8rem; box-shadow: 0 4px 14px rgba(0, 102, 51, 0.3);">
                        <i class="bi bi-cloud-arrow-down"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Complete Database Backup (.sql)</h5>
                        <p class="text-muted small mb-0">
                            Exports all 19 database tables (Voters, Polling Stations, Block Codes, UCs, Assemblies, Candidates, Branding Images paths, Telemetry Logs) into a single standard SQL file.
                        </p>
                        <small class="text-success fw-semibold mt-1 d-block">
                            <i class="bi bi-shield-check me-1"></i> 100% compatible with phpMyAdmin, MySQL Workbench, and command line restore.
                        </small>
                    </div>
                </div>

                <div>
                    <a href="{{ route('settings.backup.download') }}" class="btn btn-success btn-lg px-4 py-2.5 shadow-sm fw-semibold" id="downloadBackupBtn" onclick="triggerBackupLoader()">
                        <i class="bi bi-download me-2"></i> Download Full Database Backup
                    </a>
                </div>
            </div>

            <!-- Current Database Counts Breakdown -->
            <hr class="my-3">
            <div class="row g-2 text-center text-md-start">
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Total Voters</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['voters']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Union Councils</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['ucs']) }} UCs</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Census Blocks</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['blocks']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Polling Stations</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['stations']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Candidates</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['candidates']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Tables Count</small>
                        <span class="fs-6 fw-bold text-success">19 Tables</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. RECENT BACKUPS STORED ON SERVER -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Server Stored Backups Archive</h6>
            <span class="badge bg-light text-dark border">{{ count($backups) }} Backups Available</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Backup File Name</th>
                        <th>File Size</th>
                        <th>Creation Timestamp</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $b)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-filetype-sql fs-5 text-primary"></i>
                                    <div>
                                        <span class="fw-bold text-dark font-mono">{{ $b['name'] }}</span>
                                        <small class="text-muted d-block">Full SQL Dump</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-mono">{{ $b['size'] }}</span>
                            </td>
                            <td>
                                <span class="small text-muted">{{ $b['date'] }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('settings.backup.file', $b['name']) }}" class="btn btn-sm btn-outline-success me-1" title="Download this backup">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                                <form method="POST" action="{{ route('settings.backup.delete', $b['name']) }}" class="d-inline" onsubmit="return confirm('Delete this backup file from server?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete backup file">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-archive fs-3 d-block text-secondary mb-1"></i>
                                No saved backup files yet. Click the <strong>Download Full Database Backup</strong> button above to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. DATA CLEANUP / RESET SECTION (COLLAPSIBLE) -->
    <div class="card shadow-sm border-0 border-start border-4 border-danger">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Maintenance &amp; Test Data Wipe</h6>
                <small class="text-muted">Use with extreme caution. Take a backup first before running any purge action.</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#purgeCollapse">
                Toggle Wipe Tools
            </button>
        </div>
        <div class="collapse" id="purgeCollapse">
            <div class="card-body bg-light">
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-shield-exclamation me-1"></i> <strong>Important:</strong> Deleting data is irreversible. Please ensure you have clicked the green <strong>Download Full Database Backup</strong> button above before performing any wipe.
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('settings.purge', 'voters') }}" onsubmit="return confirm('WARNING: Are you sure you want to delete ALL voters?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete All Voters ({{ number_format($counts['voters']) }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'polling-stations') }}" onsubmit="return confirm('WARNING: Delete ALL polling stations?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Polling Stations ({{ $counts['stations'] }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'block-codes') }}" onsubmit="return confirm('WARNING: Delete ALL block codes?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Block Codes ({{ $counts['blocks'] }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'locations') }}" onsubmit="return confirm('WARNING: Delete ALL locations (UCs, Tehsils, Districts) and their voters?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Locations ({{ $counts['ucs'] }} UCs)</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'all') }}" onsubmit="return confirm('CRITICAL ALERT: Are you completely sure you want to PURGE EVERYTHING? This will delete all voters, stations, block codes, and UCs.');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-danger btn-sm"><i class="bi bi-exclamation-octagon-fill me-1"></i> Purge Entire System</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Loader Script -->
    <script>
        function triggerBackupLoader() {
            if (typeof toastr !== 'undefined') {
                toastr.info('Generating SQL database dump. Your download will start in a few seconds...');
            }
            window.VoterAppLoader?.show('Generating complete database SQL backup dump. Please wait…');
            // Hide overlay after download begins
            setTimeout(function() {
                window.VoterAppLoader?.hide();
            }, 6000);
        }
    </script>
@endsection