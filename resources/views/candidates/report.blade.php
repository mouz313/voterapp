<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Performance Report — {{ $candidate->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
        }

        .report-page {
            max-width: 900px;
            margin: 25px auto;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            padding: 35px 45px;
        }

        .report-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 18px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 14px;
            margin-top: 22px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px;
            text-align: center;
        }

        .kpi-value {
            font-size: 22px;
            font-weight: 800;
            font-family: monospace;
            color: #0f172a;
        }

        .kpi-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .table-report th {
            background-color: #f1f5f9 !important;
            color: #334155;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
            padding: 8px 10px;
            border-color: #e2e8f0;
        }

        .table-report td {
            padding: 7px 10px;
            font-size: 12px;
            border-color: #e2e8f0;
            vertical-align: middle;
        }

        .signature-box {
            border-top: 1px dashed #94a3b8;
            margin-top: 45px;
            padding-top: 6px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }

        /* Printable A4 Media Query */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .report-page {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 10mm !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

<!-- Non-printable Action Toolbar -->
<div class="container-fluid no-print bg-dark py-2 px-4 sticky-top shadow">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div class="text-white d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <span class="fw-semibold">Executive Performance Dossier &bull; {{ $candidate->name }}</span>
            <span class="badge bg-secondary font-mono">Official ECP Delimitation</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-success btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-printer-fill"></i>
                <span>Print / Save as PDF</span>
            </button>
            <a href="{{ route('candidates.report', ['candidate' => $candidate->id, 'download' => 'pdf']) }}" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-download"></i>
                <span>Direct PDF Stream</span>
            </a>
            <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-speedometer2"></i>
                <span>Back to Matrix</span>
            </a>
        </div>
    </div>
</div>

<div class="report-page">
    <!-- Header Block -->
    <div class="report-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white text-uppercase px-2 py-1" style="letter-spacing: 1px; font-size: 10px;">Electoral Management System</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 10px;">Official Field Report</span>
                </div>
                <h3 class="fw-bold text-dark mt-2 mb-1">CANDIDATE FIELD PERFORMANCE REPORT</h3>
                <div class="text-muted small">
                    Telemetry, Ground Verification & Voter Reach Assessment
                </div>
            </div>

            <div class="text-end">
                <div class="fw-bold text-dark font-mono" style="font-size: 11px;">REPORT REF: #VTR-{{ $candidate->id }}-{{ date('Ymd') }}</div>
                <div class="text-muted" style="font-size: 11px;">Date Generated: {{ now()->format('d F Y, h:i A') }}</div>
                <div class="text-muted" style="font-size: 11px;">Status: <strong class="text-success text-uppercase">{{ $candidate->status }}</strong></div>
            </div>
        </div>
    </div>

    <!-- Candidate Profile & Party Identity -->
    <div class="row g-3 align-items-center p-3 rounded mb-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
        <div class="col-auto">
            @if ($candidate->candidate_image && file_exists(public_path($candidate->candidate_image)))
                <img src="{{ asset($candidate->candidate_image) }}" alt="{{ $candidate->name }}" class="rounded border p-1 bg-white" style="width: 74px; height: 74px; object-fit: cover;">
            @else
                <div class="rounded bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center border" style="width: 74px; height: 74px; font-size: 1.8rem;">
                    {{ strtoupper(substr($candidate->name, 0, 2)) }}
                </div>
            @endif
        </div>
        <div class="col">
            <h4 class="fw-bold text-dark mb-1">{{ $candidate->name }}</h4>
            <div class="d-flex flex-wrap gap-2 mb-1">
                @if ($candidate->is_independent)
                    <span class="badge bg-secondary">Azad Umedwar (Independent)</span>
                @else
                    <span class="badge bg-primary">{{ $candidate->party_name ?: 'Party Nominee' }}</span>
                @endif
                @if ($candidate->candidate_symbol)
                    <span class="badge bg-light text-dark border font-mono">Symbol: {{ $candidate->candidate_symbol }}</span>
                @endif
            </div>
            <div class="text-muted small">
                <span><i class="bi bi-envelope me-1"></i>{{ $candidate->email }}</span> &bull; 
                <span><i class="bi bi-telephone me-1"></i>{{ $candidate->phone ?: 'N/A' }}</span>
            </div>
        </div>
        <div class="col-auto text-end d-flex align-items-center gap-3">
            @if ($candidate->candidate_symbol_image && file_exists(public_path($candidate->candidate_symbol_image)))
                <div class="text-center">
                    <img src="{{ asset($candidate->candidate_symbol_image) }}" alt="Symbol" class="rounded border p-1 bg-white" style="width: 50px; height: 50px; object-fit: contain;">
                    <div style="font-size: 9px;" class="text-muted text-uppercase mt-1">Symbol</div>
                </div>
            @endif
            @if ($candidate->party_logo && file_exists(public_path($candidate->party_logo)))
                <div class="text-center">
                    <img src="{{ asset($candidate->party_logo) }}" alt="Logo" class="rounded border p-1 bg-white" style="width: 50px; height: 50px; object-fit: contain;">
                    <div style="font-size: 9px;" class="text-muted text-uppercase mt-1">Party Flag</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Executive Performance KPI Matrix -->
    <div class="section-title">
        <i class="bi bi-speedometer2 text-primary"></i> Ground Performance & Telemetry Matrix
    </div>
    <div class="row g-2 mb-3">
        <div class="col-3">
            <div class="kpi-card">
                <div class="kpi-label">Registered UC Voters</div>
                <div class="kpi-value text-primary">{{ number_format($totalUcVoters) }}</div>
                <div class="small text-muted" style="font-size: 10px;">Assigned Territory Base</div>
            </div>
        </div>
        <div class="col-3">
            <div class="kpi-card">
                <div class="kpi-label">Total Field Searches</div>
                <div class="kpi-value text-success">{{ number_format($totalSearches) }}</div>
                <div class="small text-muted" style="font-size: 10px;">Queries Executed</div>
            </div>
        </div>
        <div class="col-3">
            <div class="kpi-card">
                <div class="kpi-label">Active Handsets</div>
                <div class="kpi-value text-dark">{{ $activeDevices }}</div>
                <div class="small text-success fw-bold" style="font-size: 10px;">Unlimited Devices Allowed</div>
            </div>
        </div>
        <div class="col-3">
            <div class="kpi-card">
                <div class="kpi-label">Voter Reach / Penetration</div>
                <div class="kpi-value text-warning-emphasis">{{ $reachPct }}%</div>
                <div class="small text-muted" style="font-size: 10px;">Voter Base Covered</div>
            </div>
        </div>
    </div>

    <!-- Constituency & Infrastructure Profile -->
    <div class="section-title">
        <i class="bi bi-geo-alt text-danger"></i> Electoral Territory & Polling Delimitation
    </div>
    <div class="row g-3 mb-3">
        <div class="col-6">
            <table class="table table-report table-sm mb-0">
                <tr>
                    <td class="text-muted fw-bold" style="width: 45%;">Assigned Union Council:</td>
                    <td class="fw-bold text-dark">{{ $candidate->uc ? ($candidate->uc->name . ' (UC #' . ($candidate->uc->uc_no ?: $candidate->uc->id) . ')') : 'Unassigned' }}</td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Tehsil:</td>
                    <td>{{ $candidate->uc?->tehsil?->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">District:</td>
                    <td>{{ $candidate->uc?->tehsil?->district?->name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="table table-report table-sm mb-0">
                <tr>
                    <td class="text-muted fw-bold" style="width: 45%;">National Assembly (NA):</td>
                    <td><span class="badge bg-primary-subtle text-primary font-mono fw-bold">{{ $candidate->uc?->nationalAssembly?->code ?? 'N/A' }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Provincial Assembly (PP/PS/PK):</td>
                    <td><span class="badge bg-success-subtle text-success font-mono fw-bold">{{ $candidate->uc?->provincialAssembly?->code ?? 'N/A' }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Polling Infrastructure:</td>
                    <td><strong>{{ $totalPollingStations }}</strong> Stations &bull; <strong>{{ $totalBlockCodes }}</strong> Census Blocks</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Search Query Breakdown Table -->
    <div class="section-title">
        <i class="bi bi-search text-info"></i> Query Distribution Breakdown
    </div>
    <table class="table table-report table-bordered mb-3">
        <thead>
            <tr>
                <th>Search Method / Filter</th>
                <th class="text-center">Queries Processed</th>
                <th class="text-center">Proportion (%)</th>
                <th>Field Operator Purpose</th>
            </tr>
        </thead>
        <tbody>
            @php
                $tot = max(1, $totalSearches);
            @endphp
            <tr>
                <td class="fw-bold"><i class="bi bi-person-vcard text-primary me-1"></i> CNIC Direct Lookup</td>
                <td class="text-center font-mono fw-bold">{{ number_format($cnicCount) }}</td>
                <td class="text-center font-mono">{{ round(($cnicCount / $tot) * 100, 1) }}%</td>
                <td class="text-muted small">Computerized National Identity Card exact matches</td>
            </tr>
            <tr>
                <td class="fw-bold"><i class="bi bi-fonts text-success me-1"></i> Name & Parentage Lookup</td>
                <td class="text-center font-mono fw-bold">{{ number_format($nameCount) }}</td>
                <td class="text-center font-mono">{{ round(($nameCount / $tot) * 100, 1) }}%</td>
                <td class="text-muted small">Voter Urdu/English spelling matching</td>
            </tr>
            <tr>
                <td class="fw-bold"><i class="bi bi-house-door text-warning me-1"></i> Gharana (Family) Number</td>
                <td class="text-center font-mono fw-bold">{{ number_format($gharanaCount) }}</td>
                <td class="text-center font-mono">{{ round(($gharanaCount / $tot) * 100, 1) }}%</td>
                <td class="text-muted small">Household family group identification</td>
            </tr>
            <tr>
                <td class="fw-bold"><i class="bi bi-hash text-danger me-1"></i> Silsala (Serial) Number</td>
                <td class="text-center font-mono fw-bold">{{ number_format($silsalaCount) }}</td>
                <td class="text-center font-mono">{{ round(($silsalaCount / $tot) * 100, 1) }}%</td>
                <td class="text-muted small">Electoral roll sequence identification</td>
            </tr>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th class="fw-bold">Total Aggregated Field Telemetry</th>
                <th class="text-center font-mono fw-bold">{{ number_format($totalSearches) }}</th>
                <th class="text-center font-mono fw-bold">100.0%</th>
                <th class="text-muted small">All field queries logged without duplication</th>
            </tr>
        </tfoot>
    </table>

    <!-- Deployed Field Devices Table -->
    <div class="section-title">
        <i class="bi bi-phone text-secondary"></i> Deployed Field Handsets & Agent Logs
    </div>
    <table class="table table-report table-bordered mb-4">
        <thead>
            <tr>
                <th>#</th>
                <th>Device Name / Model</th>
                <th>Platform & App</th>
                <th class="text-center">Queries Handled</th>
                <th>Last Active Time</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($candidate->devices as $idx => $d)
                <tr>
                    <td class="text-muted">{{ $idx + 1 }}</td>
                    <td class="fw-semibold">
                        {{ $d->device_name ?: 'Android Handset' }}
                        <small class="text-muted d-block font-mono" style="font-size: 9px;">UID: {{ Str::limit($d->device_uid, 22) }}</small>
                    </td>
                    <td>
                        {{ $d->platform ?: 'Android' }}
                        @if ($d->app_version) <span class="badge bg-light text-dark border font-mono">v{{ $d->app_version }}</span> @endif
                    </td>
                    <td class="text-center font-mono fw-bold text-success">{{ number_format($d->searches_count ?? 0) }}</td>
                    <td>
                        @if ($d->last_active_at)
                            {{ $d->last_active_at->format('d M Y, h:i A') }}
                        @else
                            <span class="text-muted">Never Active</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($d->is_revoked)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Revoked</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">No mobile handsets registered for this candidate.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Official Signatures & Verification Block -->
    <div class="row pt-4 mt-4">
        <div class="col-4">
            <div class="signature-box">
                <strong>System Administrator</strong><br>
                <span>VoterApp Central Command</span>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="width: 70px; height: 70px; border: 2px dashed #94a3b8; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #64748b; text-transform: uppercase;">
                Official Seal
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <strong>Candidate / Chief Polling Agent</strong><br>
                <span>{{ $candidate->name }}</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
