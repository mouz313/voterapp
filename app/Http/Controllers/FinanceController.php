<?php

namespace App\Http\Controllers;

use App\Models\CandidateSale;
use App\Models\FinanceSetting;
use App\Models\Partner;
use App\Models\PartnerPayout;
use App\Models\SalesParty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    /**
     * Show the security unlock screen for the Finance & Sales Vault.
     */
    public function showUnlockForm()
    {
        if (session('finance_unlocked')) {
            return redirect()->route('finance.index');
        }

        return view('finance.unlock');
    }

    /**
     * Authenticate session with security password.
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (FinanceSetting::verifyPassword($request->password)) {
            session([
                'finance_unlocked' => true,
                'finance_unlocked_at' => now(),
            ]);

            $intendedUrl = session()->pull('finance_intended_url', route('finance.index'));

            return redirect()->to($intendedUrl)
                ->with('toast', ['type' => 'success', 'message' => 'Finance & Sales Vault unlocked successfully.']);
        }

        return back()
            ->withInput()
            ->with('toast', ['type' => 'error', 'message' => 'Incorrect security password. Access denied.']);
    }

    /**
     * Lock the finance vault immediately.
     */
    public function lock()
    {
        session()->forget(['finance_unlocked', 'finance_unlocked_at', 'finance_intended_url']);

        return redirect()->route('finance.unlock')
            ->with('toast', ['type' => 'info', 'message' => 'Finance Vault has been locked. Password required to re-enter.']);
    }

    /**
     * Executive Finance & Sales Command Dashboard.
     */
    public function index()
    {
        // 1. High Level Sales KPIs
        $totalSalesCount = CandidateSale::count();
        $totalInvoiced = (float) CandidateSale::sum('sale_amount');
        $totalRevenue = (float) CandidateSale::sum('amount_paid');
        $totalPending = max(0, $totalInvoiced - $totalRevenue);
        $paidSalesCount = CandidateSale::where('payment_status', 'paid')->count();
        $pendingSalesCount = CandidateSale::where('payment_status', '!=', 'paid')->count();

        // 2. Party A vs Party B Distribution Breakdown
        $parties = SalesParty::withCount('sales')
            ->with(['sales', 'partners' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->map(function ($p) {
                $invoiced = (float) $p->sales->sum('sale_amount');
                $revenue = (float) $p->sales->sum('amount_paid');
                $count = $p->sales_count;
                $avgPrice = $count > 0 ? round($invoiced / $count, 0) : 0;

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                    'sales_count' => $count,
                    'total_invoiced' => $invoiced,
                    'total_revenue' => $revenue,
                    'pending_amount' => max(0, $invoiced - $revenue),
                    'average_price' => $avgPrice,
                    'default_price' => (float) $p->default_price,
                    'is_active' => $p->is_active,
                    'partners' => $p->partners->map(fn ($pt) => [
                        'id' => $pt->id,
                        'name' => $pt->name,
                        'type' => $pt->type,
                        'profit_share_pct' => (float) $pt->profit_share_pct,
                        'invested_capital' => (float) $pt->invested_capital,
                    ]),
                ];
            });

        // 3. Investor Capital & Payback Progress
        $totalInvestedCapital = (float) Partner::where('type', 'investor')->sum('invested_capital');
        $totalCapitalReturned = (float) PartnerPayout::where('payout_type', 'capital_return')->sum('amount');
        $outstandingCapital = max(0, $totalInvestedCapital - $totalCapitalReturned);
        $capitalProgressPct = $totalInvestedCapital > 0 ? min(100.0, round(($totalCapitalReturned / $totalInvestedCapital) * 100, 1)) : 100.0;

        // 4. Net Profit Pool & Partner Shares
        // Net profit pool = Total collected revenue minus capital returned so far
        $netProfitPool = max(0, $totalRevenue - $totalCapitalReturned);

        $partners = Partner::with('payouts')
            ->where('is_active', true)
            ->get()
            ->map(function ($pt) use ($netProfitPool) {
                $profitSharePct = (float) $pt->profit_share_pct;
                $accruedProfit = round($netProfitPool * ($profitSharePct / 100), 2);
                $profitPaid = (float) $pt->payouts->where('payout_type', 'profit_distribution')->sum('amount');
                $capitalReturned = (float) $pt->payouts->where('payout_type', 'capital_return')->sum('amount');
                $capitalDue = max(0, (float) $pt->invested_capital - $capitalReturned);
                $netBalanceDue = max(0, $accruedProfit - $profitPaid) + ($pt->type === 'investor' ? $capitalDue : 0);

                return [
                    'id' => $pt->id,
                    'name' => $pt->name,
                    'type' => $pt->type,
                    'phone' => $pt->phone,
                    'invested_capital' => (float) $pt->invested_capital,
                    'capital_returned' => $capitalReturned,
                    'capital_due' => $capitalDue,
                    'profit_share_pct' => $profitSharePct,
                    'accrued_profit' => $accruedProfit,
                    'profit_paid' => $profitPaid,
                    'net_balance_due' => $netBalanceDue,
                ];
            });

        // 5. Recent 10 Candidate Sales
        $recentSales = CandidateSale::with(['candidate.uc.tehsil', 'party'])
            ->latest()
            ->take(10)
            ->get();

        return view('finance.index', compact(
            'totalSalesCount',
            'totalInvoiced',
            'totalRevenue',
            'totalPending',
            'paidSalesCount',
            'pendingSalesCount',
            'parties',
            'totalInvestedCapital',
            'totalCapitalReturned',
            'outstandingCapital',
            'capitalProgressPct',
            'netProfitPool',
            'partners',
            'recentSales'
        ));
    }

    /**
     * Tabular Sales Ledger with Search, Filters, and Export.
     */
    public function sales(Request $request)
    {
        $parties = SalesParty::orderBy('name')->get();

        $query = CandidateSale::with(['candidate.uc.tehsil.district', 'party'])
            ->when($request->query('party_id'), fn ($q, $pId) => $q->where('sales_party_id', $pId))
            ->when($request->query('status'), fn ($q, $st) => $q->where('payment_status', $st))
            ->when($request->query('date_from'), fn ($q, $df) => $q->whereDate('payment_date', '>=', $df))
            ->when($request->query('date_to'), fn ($q, $dt) => $q->whereDate('payment_date', '<=', $dt))
            ->when($request->query('search'), function ($q, $s) {
                $q->whereHas('candidate', function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%")
                        ->orWhereHas('uc', fn ($ucQ) => $ucQ->where('name', 'like', "%{$s}%"));
                });
            });

        // CSV Export Trigger
        if ($request->query('export') === 'csv') {
            return $this->exportSalesCsv($query->get());
        }

        $sales = $query->latest()->paginate(20)->withQueryString();

        $summary = [
            'total_count' => CandidateSale::count(),
            'total_invoiced' => (float) CandidateSale::sum('sale_amount'),
            'total_collected' => (float) CandidateSale::sum('amount_paid'),
            'total_pending' => (float) CandidateSale::where('payment_status', '!=', 'paid')->sum(DB::raw('sale_amount - amount_paid')),
        ];

        return view('finance.sales', compact('sales', 'parties', 'summary'));
    }

    /**
     * Update payment details for a specific sale.
     */
    public function updateSale(Request $request, CandidateSale $sale)
    {
        $validated = $request->validate([
            'sale_amount' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,pending,partial',
            'payment_method' => 'nullable|string|max:50',
            'payment_date' => 'nullable|date',
            'sales_party_id' => 'nullable|exists:sales_parties,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $sale->update($validated);

        return back()->with('toast', ['type' => 'success', 'message' => 'Candidate sale record updated successfully.']);
    }

    /**
     * List and manage Sales Parties (Party A, Party B, etc.).
     */
    public function parties()
    {
        $parties = SalesParty::withCount('sales')
            ->with([
                'sales' => fn ($q) => $q->latest(),
                'partners' => fn ($q) => $q->where('is_active', true),
            ])
            ->get();

        return view('finance.parties', compact('parties'));
    }

    /**
     * Create new Selling Party.
     */
    public function storeParty(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sales_parties,code',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'default_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['code'] = strtoupper(Str::slug($validated['code'], '_'));
        $validated['is_active'] = $request->has('is_active');
        $validated['commission_rate_pct'] = 0.00;

        SalesParty::create($validated);

        return back()->with('toast', ['type' => 'success', 'message' => "Selling Party [{$validated['name']}] created successfully."]);
    }

    /**
     * Update existing Selling Party.
     */
    public function updateParty(Request $request, SalesParty $party)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:sales_parties,code,' . $party->id,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'default_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['code'] = strtoupper(Str::slug($validated['code'], '_'));
        $validated['is_active'] = $request->has('is_active');

        $party->update($validated);

        return back()->with('toast', ['type' => 'success', 'message' => "Selling Party [{$party->name}] updated successfully."]);
    }

    /**
     * List and manage Partners & Investors.
     */
    public function partners()
    {
        $salesParties = SalesParty::where('is_active', true)->orderBy('name')->get();

        $partners = Partner::with(['payouts' => fn ($q) => $q->latest(), 'salesParty'])
            ->orderBy('type', 'desc') // investors first
            ->orderBy('name')
            ->get();

        $totalInvested = (float) Partner::where('type', 'investor')->sum('invested_capital');
        $totalCapitalReturned = (float) PartnerPayout::where('payout_type', 'capital_return')->sum('amount');
        $totalProfitDistributed = (float) PartnerPayout::where('payout_type', 'profit_distribution')->sum('amount');
        $totalProfitPctAssigned = (float) Partner::where('is_active', true)->sum('profit_share_pct');

        return view('finance.partners', compact(
            'partners',
            'salesParties',
            'totalInvested',
            'totalCapitalReturned',
            'totalProfitDistributed',
            'totalProfitPctAssigned'
        ));
    }

    /**
     * Create new Partner or Investor.
     */
    public function storePartner(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:investor,partner',
            'sales_party_id' => 'nullable|exists:sales_parties,id',
            'phone' => 'nullable|string|max:50',
            'invested_capital' => 'nullable|numeric|min:0',
            'profit_share_pct' => 'required|numeric|min:0|max:100',
            'bank_details' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->has('is_active');
        $requestedPct = (float) $validated['profit_share_pct'];

        // Percentage Sum Validation: Combined active partners must not exceed 100%
        if ($isActive) {
            $existingTotal = (float) Partner::where('is_active', true)->sum('profit_share_pct');
            $availableCapacity = max(0.0, round(100.0 - $existingTotal, 2));

            if (round($existingTotal + $requestedPct, 2) > 100.0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'profit_share_pct' => "Tamam partners ka kul profit share 100% se zyada nahi ho sakta. Pehle se {$existingTotal}% assigned hai, baki sirf {$availableCapacity}% gunjaish mojood hai.",
                    ])
                    ->with('toast', [
                        'type' => 'error',
                        'message' => "Total Profit Share cannot exceed 100%! Only {$availableCapacity}% available.",
                    ]);
            }
        }

        $validated['sales_party_id'] = $request->filled('sales_party_id') ? $request->input('sales_party_id') : null;
        $validated['invested_capital'] = $validated['invested_capital'] ?? 0.00;
        $validated['is_active'] = $isActive;

        Partner::create($validated);

        return back()->with('toast', ['type' => 'success', 'message' => "Partner [{$validated['name']}] created successfully."]);
    }

    /**
     * Update Partner or Investor details.
     */
    public function updatePartner(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:investor,partner',
            'sales_party_id' => 'nullable|exists:sales_parties,id',
            'phone' => 'nullable|string|max:50',
            'invested_capital' => 'nullable|numeric|min:0',
            'profit_share_pct' => 'required|numeric|min:0|max:100',
            'bank_details' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->has('is_active');
        $requestedPct = (float) $validated['profit_share_pct'];

        // Percentage Sum Validation: Combined active partners must not exceed 100% (excluding current partner)
        if ($isActive) {
            $existingTotal = (float) Partner::where('is_active', true)
                ->where('id', '!=', $partner->id)
                ->sum('profit_share_pct');
            $availableCapacity = max(0.0, round(100.0 - $existingTotal, 2));

            if (round($existingTotal + $requestedPct, 2) > 100.0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'profit_share_pct' => "Tamam partners ka kul profit share 100% se zyada nahi ho sakta. Baki partners ko {$existingTotal}% assigned hai, is partner ke liye ziada se ziada {$availableCapacity}% ki gunjaish hai.",
                    ])
                    ->with('toast', [
                        'type' => 'error',
                        'message' => "Total Profit Share cannot exceed 100%! Only {$availableCapacity}% available for this partner.",
                    ]);
            }
        }

        $validated['sales_party_id'] = $request->filled('sales_party_id') ? $request->input('sales_party_id') : null;
        $validated['invested_capital'] = $validated['invested_capital'] ?? 0.00;
        $validated['is_active'] = $isActive;

        $partner->update($validated);

        return back()->with('toast', ['type' => 'success', 'message' => "Partner [{$partner->name}] updated successfully."]);
    }

    /**
     * Record Payout to Partner or Investor (Capital Return or Profit Dividend).
     */
    public function storePayout(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'amount' => 'required|numeric|min:1',
            'payout_type' => 'required|in:capital_return,profit_distribution',
            'payout_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $payout = PartnerPayout::create($validated);
        $partner = $payout->partner;

        $typeLabel = $validated['payout_type'] === 'capital_return' ? 'Capital Return' : 'Profit Distribution';

        return back()->with('toast', ['type' => 'success', 'message' => "{$typeLabel} of PKR " . number_format($validated['amount']) . " recorded for [{$partner->name}]."]);
    }

    /**
     * Show Security Settings (Change Password & Session Timeout).
     */
    public function securitySettings()
    {
        $timeoutMinutes = FinanceSetting::get('session_timeout_minutes', '60');

        return view('finance.security', compact('timeoutMinutes'));
    }

    /**
     * Update Finance Security Password and Timeout.
     */
    public function updateSecurity(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
            'session_timeout_minutes' => 'required|integer|min:5|max:480',
        ]);

        if (!FinanceSetting::verifyPassword($request->current_password)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Current security password does not match.']);
        }

        FinanceSetting::setPassword($request->new_password);
        FinanceSetting::set('session_timeout_minutes', $request->session_timeout_minutes);

        return back()->with('toast', ['type' => 'success', 'message' => 'Finance Security Password and session timeout updated successfully.']);
    }

    /**
     * Stream CSV export of sales records.
     */
    protected function exportSalesCsv($sales)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="candidate_sales_ledger_' . date('Ymd_His') . '.csv"',
        ];

        $columns = ['Date', 'Candidate Name', 'Candidate Phone', 'Email', 'Tehsil', 'Union Council', 'Selling Party', 'Sale Price (PKR)', 'Amount Paid (PKR)', 'Status', 'Payment Method', 'Notes'];

        $callback = function () use ($columns, $sales) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($sales as $s) {
                fputcsv($file, [
                    $s->payment_date ? $s->payment_date->format('Y-m-d') : $s->created_at->format('Y-m-d'),
                    $s->candidate->name ?? 'N/A',
                    $s->candidate->phone ?? '',
                    $s->candidate->email ?? '',
                    $s->candidate->uc->tehsil->name ?? '',
                    $s->candidate->uc->name ?? '',
                    $s->party->name ?? 'Direct',
                    $s->sale_amount,
                    $s->amount_paid,
                    strtoupper($s->payment_status),
                    $s->payment_method,
                    $s->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
