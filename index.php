<?php
require 'vendor/autoload.php';

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\TextInput;

header('Content-Type: application/json');

function detectIntentText($projectId, $text, $sessionId, $languageCode = 'en-US')
{
    $credentialsPath = __DIR__ . '/dialogflow-key.json';

    $sessionsClient = new SessionsClient([
        'credentials' => $credentialsPath
    ]);

    $session = $sessionsClient->sessionName($projectId, $sessionId);
    $textInput = new TextInput();
    $textInput->setText($text);
    $textInput->setLanguageCode($languageCode);

    $queryInput = new QueryInput();
    $queryInput->setText($textInput);

    $response = $sessionsClient->detectIntent($session, $queryInput);
    $queryResult = $response->getQueryResult();

    $queryResultJson = $queryResult->serializeToJsonString();

    header('Content-Type: application/json');
    echo $queryResultJson;

    $sessionsClient->close();

    return [
        'queryText' => $queryResult->getQueryText(),
        'fulfillmentText' => $queryResult->getFulfillmentText(),
        'intent' => $queryResult->getIntent()->getDisplayName()
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['projectId'], $input['text'], $input['sessionId'])) {
        $projectId = $input['projectId'];
        $text = $input['text'];
        $sessionId = $input['sessionId'];
        
        try {
            $response = detectIntentText($projectId, $text, $sessionId);
            echo json_encode(['status' => 'success', 'data' => $response]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
