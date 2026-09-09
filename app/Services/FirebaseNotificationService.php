<?php

namespace App\Services;

use App\Models\CandidateDevice;
use App\Models\CampaignWorker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected string $projectId;
    protected string $projectNumber;
    protected ?string $serverKey;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID', 'voterapp-9ac02'));
        $this->projectNumber = config('services.firebase.project_number', env('FIREBASE_PROJECT_NUMBER', '145402386946'));
        $this->serverKey = config('services.firebase.server_key', env('FIREBASE_SERVER_KEY', null));
    }

    /**
     * Send push notification to a specific FCM device token.
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty(trim($fcmToken))) {
            return false;
        }

        // If server key is not configured in .env yet, log simulation and gracefully succeed
        if (empty($this->serverKey)) {
            Log::info("[FCM PUSH (Simulated)] Project: {$this->projectId} ({$this->projectNumber}) | Target Token: " . substr($fcmToken, 0, 16) . "... | Title: '{$title}' | Body: '{$body}'");
            return true;
        }

        try {
            $payload = [
                'to' => $fcmToken,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'title' => $title,
                    'body' => $body,
                    'project_id' => $this->projectId,
                    'timestamp' => now()->toIso8601String(),
                ]),
            ];

            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'key=' . $this->serverKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info("[FCM PUSH SUCCESS] Title: '{$title}' -> Sent to " . substr($fcmToken, 0, 16) . "...");
                return true;
            }

            Log::warning("[FCM PUSH HTTP ERROR] Status: {$response->status()} | Response: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("[FCM PUSH EXCEPTION] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to all active devices of a candidate.
     */
    public function sendToCandidate(int $candidateId, string $title, string $body, array $data = []): int
    {
        $devices = CandidateDevice::where('user_id', $candidateId)
            ->whereNotNull('fcm_token')
            ->where('is_revoked', false)
            ->get();

        $sentCount = 0;
        foreach ($devices as $device) {
            if (!empty($device->fcm_token)) {
                if ($this->sendToToken($device->fcm_token, $title, $body, $data)) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Broadcast push notification to all active devices of all candidates.
     */
    public function broadcastToCandidates(string $title, string $body, array $data = []): int
    {
        $devices = CandidateDevice::whereNotNull('fcm_token')
            ->where('is_revoked', false)
            ->get();

        $sentCount = 0;
        foreach ($devices as $device) {
            if (!empty($device->fcm_token)) {
                if ($this->sendToToken($device->fcm_token, $title, $body, $data)) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Send push notification to a specific field worker.
     */
    public function sendToWorker(int $workerId, string $title, string $body, array $data = []): bool
    {
        $worker = CampaignWorker::where('id', $workerId)
            ->whereNotNull('fcm_token')
            ->where('is_active', true)
            ->first();

        if (!$worker || empty($worker->fcm_token)) {
            return false;
        }

        return $this->sendToToken($worker->fcm_token, $title, $body, $data);
    }

    /**
     * Broadcast push notification to all active field workers.
     */
    public function broadcastToWorkers(string $title, string $body, array $data = []): int
    {
        $workers = CampaignWorker::whereNotNull('fcm_token')
            ->where('is_active', true)
            ->get();

        $sentCount = 0;
        foreach ($workers as $worker) {
            if (!empty($worker->fcm_token)) {
                if ($this->sendToToken($worker->fcm_token, $title, $body, $data)) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    public function isConfigured(): bool
    {
        return !empty($this->serverKey);
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getProjectNumber(): string
    {
        return $this->projectNumber;
    }
}
