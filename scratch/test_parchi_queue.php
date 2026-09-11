<?php

require 'C:/xampp/htdocs/voterapp/vendor/autoload.php';
$app = require_once 'C:/xampp/htdocs/voterapp/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\PublicParchiController;
use App\Models\Voter;
use App\Services\ParchiQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

echo "=================================================================\n";
echo "       TEST SUITE: PARCHI WAIT & HOLD CONCURRENCY QUEUE          \n";
echo "=================================================================\n\n";

$passes = 0;
$fails = 0;

function assertTest($condition, $name) {
    global $passes, $fails;
    if ($condition) {
        echo "  [PASS] $name\n";
        $passes++;
    } else {
        echo "  [FAIL] $name\n";
        $fails++;
    }
}

// Reset cache
Cache::forget(ParchiQueueService::CACHE_ACTIVE_KEY);
Cache::forget(ParchiQueueService::CACHE_QUEUE_LIST);

// -------------------------------------------------------------
// TEST 1: Normal Search Execution (Low Load)
// -------------------------------------------------------------
echo "--- TEST 1: Normal Search Under Safe Load ---\n";

$voter = Voter::where('cnic', '3520112345673')->first();
assertTest($voter !== null, "Voter Hamza Ali Khan found in database");

$request = Request::create('/parchi/search', 'POST', [
    'cnic' => '35201-1234567-3',
    'phone' => '03224028090',
]);

$controller = new PublicParchiController();
$response = $controller->search($request);

assertTest($response instanceof \Illuminate\View\View, "Response is a Blade View");
assertTest($response->name() === 'public_parchi.slip', "View rendered is public_parchi.slip");
assertTest(ParchiQueueService::getActiveCount() === 0, "Active concurrency slot correctly released to 0");

// -------------------------------------------------------------
// TEST 2: Fast-Path Cache Lookup
// -------------------------------------------------------------
echo "\n--- TEST 2: Fast-Path Sub-2ms Cache Lookup ---\n";

$cachedId = ParchiQueueService::getCachedVoterId('3520112345673');
assertTest($cachedId === $voter->id, "Voter ID correctly cached in memory/cache");

$start = microtime(true);
$cachedResponse = $controller->search($request);
$elapsedMs = (microtime(true) - $start) * 1000;

assertTest($cachedResponse->name() === 'public_parchi.slip', "Cached search returns slip view instantly");
echo "  [INFO] Cached search response time: " . round($elapsedMs, 2) . " ms\n";

// -------------------------------------------------------------
// TEST 3: Heavy Load Simulation & Virtual Wait & Hold Queue
// -------------------------------------------------------------
echo "\n--- TEST 3: Heavy Load Simulation & Virtual Wait & Hold ---\n";

// Clear voter cache so search has to proceed
Cache::forget(ParchiQueueService::RESULT_PREFIX . '3520112345673');

// Artificially simulate 35 active concurrent queries (exceeding limit of 30)
for ($i = 0; $i < 35; $i++) {
    ParchiQueueService::acquireSlot();
}

assertTest(ParchiQueueService::getActiveCount() === 35, "Active concurrency simulated at 35");
assertTest(ParchiQueueService::isUnderHeavyLoad() === true, "isUnderHeavyLoad detects heavy peak traffic");

// Now a new request comes in while server is under heavy load
$heavyLoadResponse = $controller->search($request);

assertTest($heavyLoadResponse instanceof \Illuminate\View\View, "Heavy load request handled gracefully");
assertTest($heavyLoadResponse->name() === 'public_parchi.waiting_room', "Request held in public_parchi.waiting_room");

$data = $heavyLoadResponse->getData();
assertTest(!empty($data['ticketId']), "Ticket token generated: " . ($data['ticketId'] ?? 'none'));
assertTest(isset($data['position']) && $data['position'] >= 1, "Queue position assigned: #" . ($data['position'] ?? 0));

$ticketId = $data['ticketId'];

// -------------------------------------------------------------
// TEST 4: AJAX Queue Status Endpoint
// -------------------------------------------------------------
echo "\n--- TEST 4: AJAX Polling Status Endpoint ---\n";

$statusRequest = Request::create('/parchi/queue-status', 'POST', [
    'ticket_id' => $ticketId,
]);

$statusResponse = $controller->queueStatus($statusRequest);
$statusData = json_decode($statusResponse->getContent(), true);

assertTest($statusData['status'] === 'waiting', "Ticket status is waiting while heavy load persists");
assertTest(isset($statusData['position']), "Status reports current queue position");

// Now simulate slots freeing up as previous searches complete
for ($i = 0; $i < 35; $i++) {
    ParchiQueueService::releaseSlot();
}

assertTest(ParchiQueueService::getActiveCount() === 0, "Slots successfully freed up to 0");
assertTest(ParchiQueueService::isUnderHeavyLoad() === false, "isUnderHeavyLoad returns false when slots are available");

// Poll again now that slots are free
$statusResponseAfterFree = $controller->queueStatus($statusRequest);
$statusDataAfterFree = json_decode($statusResponseAfterFree->getContent(), true);

assertTest($statusDataAfterFree['status'] === 'ready', "Ticket automatically processed to ready state when slot opened");
assertTest(!empty($statusDataAfterFree['redirect_url']), "Redirect URL provided for seamless transition to slip");

// -------------------------------------------------------------
// TEST 5: Render Processed Slip for Queued Ticket
// -------------------------------------------------------------
echo "\n--- TEST 5: Show Slip from Queued Ticket ---\n";

$ticketSlipView = $controller->showTicketSlip(Request::create('/parchi/ticket/' . $ticketId, 'GET'), $ticketId);

assertTest($ticketSlipView instanceof \Illuminate\View\View, "showTicketSlip returns View");
assertTest($ticketSlipView->name() === 'public_parchi.slip', "Ticket slip view correctly rendered");
$slipData = $ticketSlipView->getData();
assertTest($slipData['voter']->name === 'Hamza Ali Khan', "Voter data matches Hamza Ali Khan");
assertTest(!empty($slipData['whatsappUrl']), "WhatsApp URL correctly generated with candidate phone");

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n=================================================================\n";
echo "   TEST SUMMARY: $passes PASSED, $fails FAILED\n";
echo "=================================================================\n";
