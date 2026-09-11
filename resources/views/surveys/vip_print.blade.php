<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate VIP Daurah Schedule & Route Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #fff;
            color: #000;
            font-size: 13px;
        }
        .print-header {
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 10mm;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Print Controls -->
        <div class="no-print d-flex align-items-center justify-content-between mb-4 p-3 bg-light rounded border">
            <div>
                <strong>VIP Daurah Route Sheet (دورہ شیڈول)</strong>
                <span class="text-muted ms-2">&bull; {{ $records->count() }} Households Listed</span>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer me-1"></i> Print / Save as PDF
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Official Header -->
        <div class="print-header d-flex align-items-center justify-content-between">
            <div>
                <h3 class="fw-bold mb-1">CANDIDATE VIP CAMPAIGN DAURAH ITINERARY</h3>
                <div class="text-muted">
                    <span>Generated on: <strong>{{ now()->format('d M Y, h:i A') }}</strong></span>
                    @if($candidate)
                        <span class="ms-3">&bull; Candidate: <strong>{{ $candidate->name }}</strong> ({{ $candidate->party_name ?? 'Independent' }})</span>
                    @endif
                </div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark fs-6 px-3 py-2 text-white">
                    TOTAL TARGETS: {{ $records->count() }}
                </div>
            </div>
        </div>

        <!-- Schedule Table -->
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 40px;">#</th>
                    <th style="width: 110px;">Block Code</th>
                    <th style="width: 90px;">Gharana #</th>
                    <th>Influencer / Family Head</th>
                    <th style="width: 130px;">Contact Number</th>
                    <th style="width: 80px;">Votes</th>
                    <th>Issues / Community Demands / Notes</th>
                    <th style="width: 100px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $index => $item)
                    <tr>
                        <td class="text-center fw-bold">{{ $index + 1 }}</td>
                        <td class="text-center font-monospace fw-bold">{{ $item->block_code }}</td>
                        <td class="text-center fw-bold">#{{ $item->gharana_no }}</td>
                        <td>
                            <strong>{{ $item->influencer_name ?: 'Family Head' }}</strong>
                            <div class="text-muted small">
                                {{ $item->blockCodeModel?->area_name ?? 'Assigned Area' }}
                            </div>
                        </td>
                        <td class="text-center font-monospace">{{ $item->influencer_phone ?: '-' }}</td>
                        <td class="text-center fw-bold fs-6">{{ $item->voter_count ?: 1 }}</td>
                        <td>
                            <div class="small">{{ $item->notes ?: 'General visit requested' }}</div>
                            @if($item->sentiment === 'kacha')
                                <span class="badge bg-light text-dark border mt-1">Swing Voter</span>
                            @elseif($item->sentiment === 'pakka')
                                <span class="badge bg-light text-success border mt-1">Pakka Supporter</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="border rounded p-2 text-muted small" style="border-style: dashed !important;">
                                [ &nbsp; ] Visited
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No households found matching the current VIP route parameters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer Sign-off -->
        <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted small">
            <div>
                <span>Report prepared via VoterApp Campaign Intelligence Console</span>
            </div>
            <div>
                <span>Candidate / Campaign Manager Signature: _______________________</span>
            </div>
        </div>
    </div>
</body>
</html>
