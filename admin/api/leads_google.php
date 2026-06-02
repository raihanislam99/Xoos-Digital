<?php
require_once __DIR__ . '/../inc/functions.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$niche = trim($_POST['niche'] ?? '');
$city = trim($_POST['city'] ?? '');
$country = trim($_POST['country'] ?? '');
$keyword = trim($_POST['keyword'] ?? '');
$maxResults = min(20, max(1, (int)($_POST['max'] ?? 10)));

if (!$niche && !$keyword) {
    echo json_encode(['success' => false, 'error' => 'Niche or keyword required']);
    exit;
}

$apiKey = get_setting('google_places_api_key', '');
if (!$apiKey) {
    echo json_encode(['success' => false, 'error' => 'Google Places API key not configured. Add it in Settings > API & Integrations.']);
    exit;
}

$query = $niche;
if ($keyword) $query .= ' ' . $keyword;
$location = '';
if ($city) $location = $city;
if ($country) $location .= ($location ? ', ' : '') . $country;
if ($location) $query .= ' in ' . $location;

$results = [];
$nextPageToken = '';

do {
    $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . urlencode($query) . '&key=' . $apiKey;
    if ($nextPageToken) {
        $url .= '&pagetoken=' . urlencode($nextPageToken);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'error' => 'Google API request failed (HTTP ' . $httpCode . ')']);
        exit;
    }

    $data = json_decode($resp, true);
    if (!$data || ($data['status'] ?? '') !== 'OK') {
        if (empty($results)) {
            $error = $data['error_message'] ?? $data['status'] ?? 'Unknown API error';
            if (stripos($error, 'billing') !== false || $data['status'] === 'REQUEST_DENIED') {
                $error = 'Billing must be enabled. Go to https://console.cloud.google.com/billing — the $200/mo free credit covers Places API calls.';
            }
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
        break;
    }

    foreach ($data['results'] as $place) {
        $results[] = [
            'place_id' => $place['place_id'],
            'business_name' => $place['name'] ?? '',
            'address' => $place['formatted_address'] ?? '',
            'rating' => $place['rating'] ?? 0,
            'user_ratings_total' => $place['user_ratings_total'] ?? 0,
            'types' => $place['types'] ?? [],
            'website' => '',
            'phone' => '',
            'email' => '',
            'lead_score' => 0,
            'city' => '',
            'country' => '',
        ];
    }

    $nextPageToken = $data['next_page_token'] ?? '';
    if ($nextPageToken && count($results) < $maxResults) {
        sleep(1);
    }
} while ($nextPageToken && count($results) < $maxResults);

$results = array_slice($results, 0, $maxResults);

foreach ($results as &$r) {
    $detailUrl = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . $r['place_id']
        . '&fields=name,formatted_phone_number,website,formatted_address,rating,user_ratings_total,types&key=' . $apiKey;

    $ch = curl_init($detailUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $detailResp = curl_exec($ch);
    curl_close($ch);

    $detailData = json_decode($detailResp, true);
    if ($detailData && ($detailData['status'] ?? '') === 'OK' && isset($detailData['result'])) {
        $d = $detailData['result'];
        $r['website'] = $d['website'] ?? '';
        $r['phone'] = $d['formatted_phone_number'] ?? '';
        $r['address'] = $d['formatted_address'] ?? $r['address'];
        $r['rating'] = (float)($d['rating'] ?? $r['rating']);
        $r['user_ratings_total'] = (int)($d['user_ratings_total'] ?? $r['user_ratings_total']);
    }

    $score = 40;
    if ($r['website']) $score += 20;
    if ($r['phone']) $score += 10;
    if ($r['rating'] >= 4.5) $score += 15;
    elseif ($r['rating'] >= 4.0) $score += 10;
    elseif ($r['rating'] >= 3.0) $score += 5;
    if ($r['user_ratings_total'] > 100) $score += 15;
    elseif ($r['user_ratings_total'] > 20) $score += 10;
    $r['lead_score'] = max(0, min(100, $score));

    $addrParts = array_map('trim', explode(',', $r['address']));
    if (count($addrParts) >= 2) {
        $r['country'] = end($addrParts);
        $r['city'] = count($addrParts) >= 3 ? $addrParts[count($addrParts) - 3] : $addrParts[count($addrParts) - 2];
    }
}
unset($r);

echo json_encode(['success' => true, 'data' => $results]);
