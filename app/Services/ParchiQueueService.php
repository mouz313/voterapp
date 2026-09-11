<?php

namespace App\Services;

use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ParchiQueueService
{
    const CACHE_ACTIVE_KEY = 'parchi_active_searches';
    const CACHE_QUEUE_LIST = 'parchi_queue_list';
    const TICKET_PREFIX = 'parchi_ticket_';
    const RESULT_PREFIX = 'parchi_slip_result_';
    const UC_CANDIDATES_PREFIX = 'parchi_cand_uc_';

    /**
     * Get current number of active concurrent database searches.
     */
    public static function getActiveCount(): int
    {
        return (int) Cache::get(self::CACHE_ACTIVE_KEY, 0);
    }

    /**
     * Check if the system is currently under heavy load exceeding concurrency limits.
     */
    public static function isUnderHeavyLoad(): bool
    {
        if (!config('parchi.queue_enabled', true)) {
            return false;
        }

        $max = (int) config('parchi.max_concurrent_searches', 30);
        return self::getActiveCount() >= $max;
    }

    /**
     * Atomically acquire an active processing slot.
     */
    public static function acquireSlot(): int
    {
        $count = (int) Cache::increment(self::CACHE_ACTIVE_KEY);
        if ($count <= 1) {
            // Keep a 60-second TTL as a fail-safe so counter never permanently leaks
            Cache::put(self::CACHE_ACTIVE_KEY, 1, 60);
        }
        return $count;
    }

    /**
     * Atomically release an active processing slot.
     */
    public static function releaseSlot(): void
    {
        $count = (int) Cache::decrement(self::CACHE_ACTIVE_KEY);
        if ($count < 0) {
            Cache::put(self::CACHE_ACTIVE_KEY, 0, 60);
        }
    }

    /**
     * Enqueue a search request into the Virtual Wait & Hold Queue.
     *
     * @return array{ticket_id: string, position: int, estimated_wait: int}
     */
    public static function enqueueTicket(string $cnic, string $phone): array
    {
        $ticketId = 'tkt_' . bin2hex(random_bytes(8));
        $ttl = (int) config('parchi.ticket_ttl', 180);

        // Fetch current queue list and append new ticket
        $queue = Cache::get(self::CACHE_QUEUE_LIST, []);
        $queue[] = $ticketId;
        Cache::put(self::CACHE_QUEUE_LIST, $queue, $ttl);

        $position = count($queue);
        $ticketData = [
            'id' => $ticketId,
            'cnic' => $cnic,
            'phone' => $phone,
            'status' => 'waiting',
            'created_at' => now()->timestamp,
        ];

        Cache::put(self::TICKET_PREFIX . $ticketId, $ticketData, $ttl);

        return [
            'ticket_id' => $ticketId,
            'position' => $position,
            'estimated_wait' => max(2, $position * 2),
        ];
    }

    /**
     * Check status of a queued ticket and process if a slot has opened up.
     */
    public static function checkTicketStatus(string $ticketId): array
    {
        $ticket = Cache::get(self::TICKET_PREFIX . $ticketId);
        if (!$ticket) {
            return ['status' => 'expired', 'message' => 'ٹکٹ ایکسپائر ہو چکا ہے۔ دوبارہ تلاش کریں۔'];
        }

        if ($ticket['status'] === 'ready') {
            return [
                'status' => 'ready',
                'voter_id' => $ticket['voter_id'] ?? null,
                'redirect_url' => route('public.parchi.ticket.show', ['ticketId' => $ticketId]),
            ];
        }

        if ($ticket['status'] === 'error') {
            return [
                'status' => 'error',
                'message' => $ticket['error_message'] ?? 'تلاش میں خرابی پیش آگئی ہے۔',
            ];
        }

        // Calculate position in current queue
        $queue = Cache::get(self::CACHE_QUEUE_LIST, []);
        $index = array_search($ticketId, $queue);
        $position = ($index !== false) ? $index + 1 : 1;

        // If slots are available, process this ticket immediately
        if (!self::isUnderHeavyLoad()) {
            return self::processQueuedTicket($ticketId);
        }

        return [
            'status' => 'waiting',
            'position' => $position,
            'estimated_wait' => max(1, $position * 2),
        ];
    }

    /**
     * Process a queued ticket, acquire a slot, execute search, and cache result.
     */
    public static function processQueuedTicket(string $ticketId): array
    {
        $ticket = Cache::get(self::TICKET_PREFIX . $ticketId);
        if (!$ticket) {
            return ['status' => 'expired'];
        }

        self::acquireSlot();
        try {
            $cnic = $ticket['cnic'];
            $phone = $ticket['phone'];

            // Find voter
            $voter = Voter::with(['uc.tehsil.district', 'blockCode', 'pollingStation'])
                ->where('cnic', $cnic)
                ->first();

            if (!$voter) {
                $ticket['status'] = 'error';
                $ticket['error_message'] = 'معذرت! یہ شناختی کارڈ ہماری ووٹر لسٹ میں موجود نہیں ہے۔';
                Cache::put(self::TICKET_PREFIX . $ticketId, $ticket, 120);
                self::removeFromQueueList($ticketId);
                return ['status' => 'error', 'message' => $ticket['error_message']];
            }

            // Prevent updating if already registered with a different phone
            if (!empty($voter->phone) && $voter->phone !== $phone) {
                $ticket['status'] = 'error';
                $ticket['error_message'] = 'اس شناختی کارڈ کے ساتھ پہلے سے موبائل نمبر رجسٹرڈ ہے۔ ایک بار درج ہونے کے بعد نمبر تبدیل نہیں کیا جا سکتا۔ براہ کرم اپنا رجسٹرڈ موبائل نمبر درج کریں۔';
                Cache::put(self::TICKET_PREFIX . $ticketId, $ticket, 120);
                self::removeFromQueueList($ticketId);
                return ['status' => 'error', 'message' => $ticket['error_message']];
            }

            // Phone enrichment: Attach phone ONLY once if currently empty
            if (empty($voter->phone) && !empty($phone)) {
                $phoneUsed = Voter::where('phone', $phone)->where('id', '!=', $voter->id)->exists();
                if ($phoneUsed) {
                    $ticket['status'] = 'error';
                    $ticket['error_message'] = 'یہ موبائل نمبر پہلے سے کسی اور شناختی کارڈ کے ساتھ رجسٹرڈ ہے۔ ہر ووٹر کا موبائل نمبر منفرد ہونا لازمی ہے۔';
                    Cache::put(self::TICKET_PREFIX . $ticketId, $ticket, 120);
                    self::removeFromQueueList($ticketId);
                    return ['status' => 'error', 'message' => $ticket['error_message']];
                }
                $voter->phone = $phone;
                $voter->save();
            }

            // Mark ticket ready
            $ticket['status'] = 'ready';
            $ticket['voter_id'] = $voter->id;
            Cache::put(self::TICKET_PREFIX . $ticketId, $ticket, 300);

            self::removeFromQueueList($ticketId);

            return [
                'status' => 'ready',
                'voter_id' => $voter->id,
                'redirect_url' => route('public.parchi.ticket.show', ['ticketId' => $ticketId]),
            ];
        } catch (\Throwable $e) {
            Log::error('ParchiQueueService::processQueuedTicket error: ' . $e->getMessage());
            $ticket['status'] = 'error';
            $ticket['error_message'] = 'سرور پر لوڈ زیادہ ہے۔ براہ کرم کچھ دیر بعد کوشش کریں۔';
            Cache::put(self::TICKET_PREFIX . $ticketId, $ticket, 60);
            self::removeFromQueueList($ticketId);
            return ['status' => 'error', 'message' => $ticket['error_message']];
        } finally {
            self::releaseSlot();
        }
    }

    /**
     * Remove ticket from active queue list.
     */
    public static function removeFromQueueList(string $ticketId): void
    {
        $queue = Cache::get(self::CACHE_QUEUE_LIST, []);
        $newQueue = array_values(array_filter($queue, fn($id) => $id !== $ticketId));
        Cache::put(self::CACHE_QUEUE_LIST, $newQueue, 180);
    }

    /**
     * Retrieve active candidates for a given Union Council with cached IDs.
     */
    public static function getCachedCandidatesForUc(?int $ucId, ?int $tehsilId)
    {
        $cacheKey = self::UC_CANDIDATES_PREFIX . ($ucId ?: 'none');

        $candidateIds = Cache::remember($cacheKey, 3600, function () use ($ucId, $tehsilId) {
            $ids = User::where('role', 'candidate')
                ->where('status', 'active')
                ->where('uc_id', $ucId)
                ->pluck('id')
                ->toArray();

            if (empty($ids) && $tehsilId) {
                $tehsilUcIds = UC::where('tehsil_id', $tehsilId)->pluck('id');
                $ids = User::where('role', 'candidate')
                    ->where('status', 'active')
                    ->whereIn('uc_id', $tehsilUcIds)
                    ->pluck('id')
                    ->toArray();
            }

            if (empty($ids)) {
                $ids = User::where('role', 'candidate')
                    ->where('status', 'active')
                    ->take(3)
                    ->pluck('id')
                    ->toArray();
            }

            return $ids;
        });

        return !empty($candidateIds) ? User::whereIn('id', $candidateIds)->get() : collect();
    }

    /**
     * Cache voter id by CNIC for instant sub-2ms lookups.
     */
    public static function getCachedVoterId(string $normalizedCnic): ?int
    {
        return Cache::get(self::RESULT_PREFIX . $normalizedCnic);
    }

    public static function setCachedVoterId(string $normalizedCnic, int $voterId): void
    {
        Cache::put(self::RESULT_PREFIX . $normalizedCnic, $voterId, (int) config('parchi.cache_ttl', 3600));
    }
}
