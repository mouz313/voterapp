<?php

$loginUrl = "http://127.0.0.1:8000/api/v1/auth/login";
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'usman@gmail.com',
    'password' => 'password',
    'device_uid' => 'test-device-auth-check'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$loginRes = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Login Response: " . json_encode($loginRes) . "\n";
$token = $loginRes['token'] ?? null;

if ($token) {
    // Test GET /candidate/workers
    $ch2 = curl_init("http://127.0.0.1:8000/api/v1/candidate/workers");
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    $workersRes = json_decode(curl_exec($ch2), true);
    curl_close($ch2);
    echo "Workers GET: " . json_encode($workersRes) . "\n";

    // Test POST /candidate/workers
    $ch3 = curl_init("http://127.0.0.1:8000/api/v1/candidate/workers");
    curl_setopt($ch3, CURLOPT_POST, 1);
    curl_setopt($ch3, CURLOPT_POSTFIELDS, json_encode([
        'name' => 'Tariq Mehmood',
        'phone' => '03001234567',
        'assigned_block_code' => '185010401',
        'pin' => '5544'
    ]));
    curl_setopt($ch3, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    $createRes = json_decode(curl_exec($ch3), true);
    curl_close($ch3);
    echo "Worker Create POST: " . json_encode($createRes) . "\n";
}
