<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Performance Report — {{ $candidate->name }}</title>
    <style>
        @page {
            margin: 12mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 9px;
            color: #64748b;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-primary { background-color: #2563eb; color: #ffffff; }
        .badge-success { background-color: #16a34a; color: #ffffff; }
        .badge-dark { background-color: #0f172a; color: #ffffff; }
        .badge-light { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }

        .profile-table {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 15px;
        }
        .profile-table td {
            padding: 8px 10px;
            vertical-align: middle;
        }

        .section-heading {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .kpi-table {
            margin-bottom: 15px;
        }
        .kpi-cell {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: center;
            width: 25%;
        }
        .kpi-num {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            font-family: monospace;
            margin-top: 4px;
        }
        .kpi-sub {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }

        .data-table {
            border: 1px solid #cbd5e1;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9.5px;
            text-transform: uppercase;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .fw-bold { font-weight: bold; }

        .signature-table {
            margin-top: 30px;
            padding-top: 20px;
        }
        .signature-line {
            border-top: 1px dashed #64748b;
            text-align: center;
            font-size: 9px;
            color: #475569;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <span class="badge badge-dark">ECP DELIMITATION SYSTEM</span>
                <span class="badge badge-success">OFFICIAL DOSSIER</span>
                <div class="title" style="margin-top: 4px;">CANDIDATE PERFORMANCE REPORT</div>
                <div class="subtitle">Field Operations, Search Telemetry & Voter Reach Matrix</div>
            </td>
            <td class="text-right" style="width: 30%;">
                <div class="fw-bold font-mono" style="font-size: 10px;">REF: #VTR-{{ $candidate->id }}-{{ date('Ymd') }}</div>
                <div style="font-size: 9px; color: #64748b;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                <div style="font-size: 9px; color: #16a34a; font-weight: bold;">STATUS: {{ strtoupper($candidate->status) }}</div>
            </td>
        </tr>
    </table>

    <!-- Candidate Profile Table -->
    <table class="profile-table">
        <tr>
            <td style="width: 65%;">
                <div style="font-size: 14px; font-weight: bold; color: #0f172a;">{{ $candidate->name }}</div>
                <div style="margin-top: 2px;">
                    @if ($candidate->is_independent)
                        <span class="badge badge-light">Independent (آزاد امیدوار)</span>
                    @else
                        <span class="badge badge-primary">{{ $candidate->party_name ?: 'Party Nominee' }}</span>
                    @endif
                    @if ($candidate->candidate_symbol)
                        <span class="badge badge-light font-mono">Nishan: {{ $candidate->candidate_symbol }}</span>
                    @endif
                </div>
                <div style="font-size: 9.5px; color: #475569; margin-top: 4px;">
                    Email: <strong>{{ $candidate->email }}</strong> &bull; Phone: <strong>{{ $candidate->phone ?: 'N/A' }}</strong>
                </div>
            </td>
            <td style="width: 35%; text-align: right;">
                <div style="font-size: 9px; color: #64748b; text-transform: uppercase;">Constituency</div>
                <div style="font-size: 11px; font-weight: bold; color: #0f172a;">
                    {{ $candidate->uc ? ($candidate->uc->name . ' (UC #' . ($candidate->uc->uc_no ?: $candidate->uc->id) . ')') : 'Unassigned' }}
                </div>
                <div style="font-size: 9px; color: #475569;">
                    {{ $candidate->uc?->tehsil?->name }} &bull; {{ $candidate->uc?->tehsil?->district?->name }}
                </div>
            </td>
        </tr>
    </table>

    <!-- KPI Matrix Table -->
    <div class="section-heading">Ground Performance & Telemetry Matrix</div>
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-sub">Total UC Voters</div>
                <div class="kpi-num" style="color: #2563eb;">{{ number_format($totalUcVoters) }}</div>
                <div style="font-size: 8px; color: #64748b;">Registered Base</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-sub">Field Queries Run</div>
                <div class="kpi-num" style="color: #16a34a;">{{ number_format($totalSearches) }}</div>
                <div style="font-size: 8px; color: #64748b;">Total Processed</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-sub">Active Handsets</div>
                <div class="kpi-num">{{ $activeDevices }}</div>
                <div style="font-size: 8px; color: #16a34a;">Unlimited (No Cap)</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-sub">Voter Reach %</div>
                <div class="kpi-num" style="color: #d97706;">{{ $reachPct }}%</div>
                <div style="font-size: 8px; color: #64748b;">Penetration Rate</div>
            </td>
        </tr>
    </table>

    <!-- Constituency & Polling Details -->
    <div class="section-heading">Constituency & Polling Demographics</div>
    <table class="data-table">
        <tr>
            <td class="fw-bold" style="width: 25%; background-color: #f8fafc;">National Assembly (NA):</td>
            <td class="font-mono fw-bold" style="width: 25%;">{{ $candidate->uc?->nationalAssembly?->code ?? 'N/A' }}</td>
            <td class="fw-bold" style="width: 25%; background-color: #f8fafc;">Provincial Assembly:</td>
            <td class="font-mono fw-bold" style="width: 25%;">{{ $candidate->uc?->provincialAssembly?->code ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="fw-bold" style="background-color: #f8fafc;">Polling Stations in UC:</td>
            <td><strong>{{ $totalPollingStations }}</strong> Stations</td>
            <td class="fw-bold" style="background-color: #f8fafc;">Census Block Codes:</td>
            <td><strong>{{ $totalBlockCodes }}</strong> Blocks</td>
        </tr>
    </table>

    <!-- Query Breakdown Table -->
    <div class="section-heading">Search Telemetry Breakdown (Single Aggregated Row)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Query Method</th>
                <th class="text-center">Queries Processed</th>
                <th class="text-center">Proportion (%)</th>
                <th>Field Operator Purpose</th>
            </tr>
        </thead>
        <tbody>
            @php $tot = max(1, $totalSearches); @endphp
            <tr>
                <td class="fw-bold">CNIC Direct Searches</td>
                <td class="text-center font-mono fw-bold">{{ number_format($cnicCount) }}</td>
                <td class="text-center font-mono">{{ round(($cnicCount / $tot) * 100, 1) }}%</td>
                <td style="color: #64748b;">Computerized National Identity Card exact matches</td>
            </tr>
            <tr>
                <td class="fw-bold">Name & Parentage Searches</td>
                <td class="text-center font-mono fw-bold">{{ number_format($nameCount) }}</td>
                <td class="text-center font-mono">{{ round(($nameCount / $tot) * 100, 1) }}%</td>
                <td style="color: #64748b;">Voter full name and parentage searches</td>
            </tr>
            <tr>
                <td class="fw-bold">Gharana (Family) Number</td>
                <td class="text-center font-mono fw-bold">{{ number_format($gharanaCount) }}</td>
                <td class="text-center font-mono">{{ round(($gharanaCount / $tot) * 100, 1) }}%</td>
                <td style="color: #64748b;">Family household grouping identification</td>
            </tr>
            <tr>
                <td class="fw-bold">Silsala (Serial) Number</td>
                <td class="text-center font-mono fw-bold">{{ number_format($silsalaCount) }}</td>
                <td class="text-center font-mono">{{ round(($silsalaCount / $tot) * 100, 1) }}%</td>
                <td style="color: #64748b;">Electoral serial number verification</td>
            </tr>
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc;">
                <th class="fw-bold">Total Processed Queries</th>
                <th class="text-center font-mono fw-bold">{{ number_format($totalSearches) }}</th>
                <th class="text-center font-mono fw-bold">100.0%</th>
                <th style="color: #475569;">Non-duplicating cumulative count</th>
            </tr>
        </tfoot>
    </table>

    <!-- Deployed Field Devices Table -->
    <div class="section-heading">Deployed Field Handsets</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Device Operator / Name</th>
                <th style="width: 20%;">Platform</th>
                <th class="text-center" style="width: 20%;">Searches</th>
                <th class="text-center" style="width: 20%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($candidate->devices as $idx => $d)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="fw-bold">
                        {{ $d->device_name ?: 'Android Handset' }}
                        <div style="font-size: 8px; color: #64748b; font-family: monospace;">{{ Str::limit($d->device_uid, 24) }}</div>
                    </td>
                    <td>{{ $d->platform ?: 'Android' }} @if($d->app_version) (v{{ $d->app_version }}) @endif</td>
                    <td class="text-center font-mono fw-bold" style="color: #16a34a;">{{ number_format($d->searches_count ?? 0) }}</td>
                    <td class="text-center">
                        @if ($d->is_revoked)
                            <span style="color: #dc2626; font-weight: bold;">Revoked</span>
                        @else
                            <span style="color: #16a34a; font-weight: bold;">Active</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 12px; color: #64748b;">No devices paired with this candidate account.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Official Signatures Table -->
    <table class="signature-table">
        <tr>
            <td style="width: 35%;">
                <div class="signature-line">
                    <strong>SYSTEM ADMINISTRATOR</strong><br>
                    <span>VoterApp Management</span>
                </div>
            </td>
            <td style="width: 30%; text-align: center;">
                <div style="width: 55px; height: 55px; border: 1.5px dashed #94a3b8; border-radius: 50%; margin: 0 auto; line-height: 55px; font-size: 8px; color: #64748b; text-align: center;">
                    SEAL
                </div>
            </td>
            <td style="width: 35%;">
                <div class="signature-line">
                    <strong>CANDIDATE / REPRESENTATIVE</strong><br>
                    <span>{{ $candidate->name }}</span>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
