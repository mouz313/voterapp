<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UC;
use App\Models\Voter;
use App\Services\ParchiQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicParchiController extends Controller
{
    /**
     * Show the public voter slip search page.
     */
    public function index(Request $request)
    {
        return view('public_parchi.index');
    }

    /**
     * Process CNIC and Mobile number search, enrich voter phone, and show digital slip.
     * Integrates high-load concurrency limiter, result caching, and virtual waiting queue.
     */
    public function search(Request $request)
    {
        if ($request->isMethod('get') && !$request->filled('cnic')) {
            return redirect()->route('public.parchi');
        }

        $request->validate([
            'cnic' => 'required|string',
            'phone' => 'required|string|min:10|max:20',
        ], [
            'cnic.required' => 'براہ کرم شناختی کارڈ نمبر درج کریں / Please enter your CNIC number.',
            'phone.required' => 'براہ کرم اپنا موبائل نمبر درج کریں / Please enter your mobile number.',
        ]);

        $rawCnic = $request->input('cnic');
        $normalizedCnic = Voter::normalizeCnic($rawCnic);

        if (strlen($normalizedCnic) !== 13) {
            return back()->withInput()->with('error', 'شناختی کارڈ نمبر 13 ہندسوں پر مشتمل ہونا چاہیے / CNIC must be 13 digits.');
        }

        // Normalize Phone number
        $rawPhone = $request->input('phone');
        $cleanPhone = preg_replace('/[^\d+]/', '', $rawPhone);
        if (str_starts_with($cleanPhone, '+92')) {
            $cleanPhone = '0' . substr($cleanPhone, 3);
        } elseif (str_starts_with($cleanPhone, '92') && strlen($cleanPhone) === 12) {
            $cleanPhone = '0' . substr($cleanPhone, 2);
        }

        if (strlen($cleanPhone) < 10) {
            return back()->withInput()->with('error', 'براہ کرم درست موبائل نمبر درج کریں / Please enter a valid mobile number (e.g. 03001234567).');
        }

        // Global Uniqueness Check: Ensure phone number is not registered to another CNIC
        $phoneUsedByOther = Voter::where('phone', $cleanPhone)
            ->where('cnic', '!=', $normalizedCnic)
            ->exists();

        if ($phoneUsedByOther) {
            return back()->withInput()->with('error', 'معذرت! یہ موبائل نمبر پہلے سے کسی اور شناختی کارڈ کے ساتھ رجسٹرڈ ہے۔ ہر ووٹر کے لیے الگ اور منفرد موبائل نمبر درج کرنا لازمی ہے۔ / This mobile number is already registered with another CNIC.');
        }

        // FAST-PATH: Check if voter id is cached (Instant sub-2ms repeat search)
        $cachedVoterId = ParchiQueueService::getCachedVoterId($normalizedCnic);
        if ($cachedVoterId) {
            $voter = Voter::with(['uc.tehsil.district', 'blockCode', 'pollingStation'])->find($cachedVoterId);
            if ($voter) {
                // Prevent updating if already registered with a different phone
                if (!empty($voter->phone) && $voter->phone !== $cleanPhone) {
                    return back()->withInput()->with('error', 'اس شناختی کارڈ کے ساتھ پہلے سے موبائل نمبر رجسٹرڈ ہے۔ ایک بار درج ہونے کے بعد نمبر تبدیل نہیں کیا جا سکتا۔ براہ کرم اپنا رجسٹرڈ موبائل نمبر درج کریں۔ / A mobile number is already registered for this CNIC and cannot be updated. Please enter your registered mobile number.');
                }

                // Attach phone ONLY once if currently empty
                if (empty($voter->phone)) {
                    $voter->phone = $cleanPhone;
                    $voter->save();
                }

                $candidates = ParchiQueueService::getCachedCandidatesForUc($voter->uc_id, $voter->uc?->tehsil_id);
                $shareData = $this->buildWhatsAppShare($voter, $candidates);

                return view('public_parchi.slip', array_merge([
                    'voter' => $voter,
                    'candidates' => $candidates,
                ], $shareData));
            }
        }

        // LOAD-SHEDDING / WAIT & HOLD CHECK:
        // If system is experiencing heavy peak traffic, place excess requests into the Virtual Waiting Room
        if (ParchiQueueService::isUnderHeavyLoad()) {
            $queueData = ParchiQueueService::enqueueTicket($normalizedCnic, $cleanPhone);
            return view('public_parchi.waiting_room', [
                'ticketId' => $queueData['ticket_id'],
                'position' => $queueData['position'],
                'estimatedWait' => $queueData['estimated_wait'],
            ]);
        }

        // NORMAL PATH: Acquire Concurrency Slot
        ParchiQueueService::acquireSlot();

        try {
            // Search voter in database
            $voter = Voter::with(['uc.tehsil.district', 'blockCode', 'pollingStation'])
                ->where('cnic', $normalizedCnic)
                ->first();

            if (!$voter) {
                return back()->withInput()->with('error', 'معذرت! یہ شناختی کارڈ ہماری ووٹر لسٹ میں موجود نہیں ہے۔ براہ کرم اپنا شناختی کارڈ نمبر دوبارہ چیک کریں یا اپنے قریبی الیکشن کیمپ سے رابطہ کریں۔ / Sorry, this CNIC was not found in the voter list.');
            }

            // Prevent updating if already registered with a different phone
            if (!empty($voter->phone) && $voter->phone !== $cleanPhone) {
                return back()->withInput()->with('error', 'اس شناختی کارڈ کے ساتھ پہلے سے موبائل نمبر رجسٹرڈ ہے۔ ایک بار درج ہونے کے بعد نمبر تبدیل نہیں کیا جا سکتا۔ براہ کرم اپنا رجسٹرڈ موبائل نمبر درج کریں۔ / A mobile number is already registered for this CNIC and cannot be updated. Please enter your registered mobile number.');
            }

            // Attach phone ONLY once if currently empty
            if (empty($voter->phone)) {
                $voter->phone = $cleanPhone;
                $voter->save();
            }

            // Cache voter ID for future instant sub-2ms lookups
            ParchiQueueService::setCachedVoterId($normalizedCnic, $voter->id);

            // Cached candidate retrieval for this voter's constituency/UC
            $candidates = ParchiQueueService::getCachedCandidatesForUc($voter->uc_id, $voter->uc?->tehsil_id);
            $shareData = $this->buildWhatsAppShare($voter, $candidates);

            return view('public_parchi.slip', array_merge([
                'voter' => $voter,
                'candidates' => $candidates,
            ], $shareData));
        } finally {
            // Guarantee concurrency slot is released even on exception
            ParchiQueueService::releaseSlot();
        }
    }

    /**
     * AJAX endpoint: Check status of a ticket in the virtual waiting room.
     */
    public function queueStatus(Request $request)
    {
        $ticketId = $request->input('ticket_id');
        if (empty($ticketId)) {
            return response()->json(['status' => 'error', 'message' => 'Ticket ID missing.'], 400);
        }

        $status = ParchiQueueService::checkTicketStatus($ticketId);
        return response()->json($status);
    }

    /**
     * Display the rendered voter slip for a ticket that has finished waiting in the queue.
     */
    public function showTicketSlip(Request $request, string $ticketId)
    {
        $ticket = Cache::get(ParchiQueueService::TICKET_PREFIX . $ticketId);

        if (!$ticket || empty($ticket['voter_id'])) {
            return redirect()->route('public.parchi')->with('error', 'ٹکٹ ایکسپائر ہو چکا ہے۔ براہ کرم دوبارہ تلاش کریں۔');
        }

        $voter = Voter::with(['uc.tehsil.district', 'blockCode', 'pollingStation'])->find($ticket['voter_id']);
        if (!$voter) {
            return redirect()->route('public.parchi')->with('error', 'ووٹر کا ریکارڈ نہیں مل سکا۔');
        }

        $candidates = ParchiQueueService::getCachedCandidatesForUc($voter->uc_id, $voter->uc?->tehsil_id);
        $shareData = $this->buildWhatsAppShare($voter, $candidates);

        return view('public_parchi.slip', array_merge([
            'voter' => $voter,
            'candidates' => $candidates,
        ], $shareData));
    }

    /**
     * Show clean printable version of the voter slip.
     */
    public function showPrint(Voter $voter)
    {
        $voter->load(['uc.tehsil.district', 'blockCode', 'pollingStation']);
        $candidates = ParchiQueueService::getCachedCandidatesForUc($voter->uc_id, $voter->uc?->tehsil_id);

        return view('public_parchi.print', compact('voter', 'candidates'));
    }

    /**
     * Helper: Format WhatsApp share text & destination link containing candidate's phone number.
     */
    protected function buildWhatsAppShare(Voter $voter, $candidates): array
    {
        $pollingStationName = $voter->pollingStation ? $voter->pollingStation->name : 'N/A';
        $blockCodeStr = $voter->blockCode ? $voter->blockCode->code : 'N/A';
        $ucName = $voter->uc ? $voter->uc->name : 'N/A';

        $candidateLines = [];
        foreach ($candidates as $idx => $c) {
            $num = ((int) $idx) + 1;
            $party = $c->party_name ? " ({$c->party_name})" : '';
            $symbol = $c->candidate_symbol ? " - نشان: {$c->candidate_symbol}" : '';
            $candidateLines[] = "{$num}️⃣ *{$c->name}*{$party}{$symbol}";
        }
        $candidatesText = !empty($candidateLines) ? implode("\n", $candidateLines) : "ہمارا متفقہ انتخابی پینل";

        $whatsappMessage = "🇵🇰 *ووٹر پرچی و انتخابی رہنمائی* 🇵🇰\n\n"
            . "👤 نام: *{$voter->name}*\n"
            . "👨‍👧 والد/شوہر: {$voter->father_name}\n"
            . "🆔 شناختی کارڈ: " . Voter::formatCnic($voter->cnic) . "\n"
            . "🔢 سلسلہ نمبر: *{$voter->silsala_no}*\n"
            . "🏠 گھرانہ نمبر: {$voter->gharana_no}\n"
            . "🏢 پولنگ اسٹیشن: *{$pollingStationName}*\n"
            . "📍 بلاک کوڈ: {$blockCodeStr}\n"
            . "🏛️ یونین کونسل: {$ucName}\n\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "🗳️ *ہمارا انتخابی پینل:*\n"
            . $candidatesText . "\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "اپنے قیمتی ووٹ کا درست استعمال کریں اور ہمارے پینل کو کامیاب بنائیں۔ شکریہ!";

        // Target phone: Candidate's phone number
        $targetPhone = null;
        foreach ($candidates as $cand) {
            if (!empty($cand->phone)) {
                $digits = preg_replace('/[^\d]/', '', $cand->phone);
                if (str_starts_with($digits, '0092')) {
                    $digits = '92' . substr($digits, 4);
                } elseif (str_starts_with($digits, '0')) {
                    $digits = '92' . substr($digits, 1);
                } elseif (!str_starts_with($digits, '92') && strlen($digits) === 10) {
                    $digits = '92' . $digits;
                }
                if (strlen($digits) >= 10) {
                    $targetPhone = $digits;
                    break;
                }
            }
        }

        $whatsappUrl = $targetPhone 
            ? 'https://api.whatsapp.com/send?phone=' . $targetPhone . '&text=' . urlencode($whatsappMessage)
            : 'https://api.whatsapp.com/send?text=' . urlencode($whatsappMessage);

        return [
            'whatsappMessage' => $whatsappMessage,
            'whatsappUrl' => $whatsappUrl,
            'targetPhone' => $targetPhone,
        ];
    }
}
