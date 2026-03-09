<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->source) ||
    !isset($data->destination) ||
    !isset($data->mode)
) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data."]);
    exit;
}

// User provided API Key from memory
$apiKey = "AIzaSyCgS-5Yw32XXV1MTcBJeBFjiYp8u68S__A";
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$source = $data->source;
$destination = $data->destination;
$mode = $data->mode;
$cost_range = $data->cost_range ?? "unknown";
$duration = $data->duration ?? "unknown";
$reason_tag = $data->reason_tag ?? "None";

// Construct the prompt
$prompt = "Analyze the choice of traveling by $mode from $source to $destination. ";
if ($reason_tag && $reason_tag !== "None") {
    $prompt .= "It is highlighted as the $reason_tag option. ";
}
$prompt .= "The estimated cost is $cost_range and duration is $duration. ";
$prompt .= "Provide a structured analysis in JSON format with the following fields: ";
$prompt .= "1. 'recommendationReason': Why this specific mode is recommended (max 2 sentences). ";
$prompt .= "2. 'whyNotOthers': An array of 2-3 short strings explaining trade-offs vs other modes (e.g. 'Bus is cheaper but slower'). ";
$prompt .= "3. 'travelTips': An array of 2-3 practical, cautious travel tips for this route/mode (e.g. 'Book early'). ";
$prompt .= "4. 'summary': A plain-English summary of the journey (max 2 sentences). ";
$prompt .= "Do not use markdown formatting. Return raw JSON.";

$requestBody = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "response_mime_type" => "application/json"
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["message" => "Request Error: " . curl_error($ch)]);
    exit;
}

curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(["message" => "API Error", "details" => $response]);
    exit;
}

$responseData = json_decode($response, true);

if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'];
    
    // Attempt to clean markdown if present (e.g. ```json ... ```)
    $cleanJson = preg_replace('/^```json\s*|\s*```$/', '', trim($rawText));
    
    $insightData = json_decode($cleanJson, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode(["insight" => $insightData]);
    } else {
        // Fallback if JSON parse fails
        echo json_encode(["insight" => [
            "recommendationReason" => $rawText,
            "whyNotOthers" => [],
            "travelTips" => [],
            "summary" => ""
        ]]);
    }
} else {
    // If structured differently or empty
    http_response_code(500);
    echo json_encode(["message" => "No insight generated.", "raw" => $responseData]);
}
?>
