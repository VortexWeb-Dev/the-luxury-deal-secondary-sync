<?php

require_once __DIR__ . "/crest/crest.php";

// Formats the comments for the call
function formatComments(array $data): string
{
    if (in_array($data['eventType'], ['smsEvent', 'aiTranscriptionSummary'])) {
        return "No data available for " . ($data['eventType'] === 'smsEvent' ? 'SMS events' : 'AI transcription summary') . ".";
    }

    $output = [];

    $output[] = "=== Call Information ===";
    $output[] = "Call ID: " . $data['callId'];
    $output[] = "Call Type: " . $data['type'];
    $output[] = "Event Type: " . $data['eventType'];

    if (isset($data['recordName'])) {
        $output[] = "Call Recording URL: " . $data['recordName'];
    }
    $output[] = "";

    $output[] = "=== Client Details ===";
    $output[] = "Client Phone: " . $data['clientPhone'];
    $output[] = "Line Number: " . $data['lineNumber'];
    $output[] = "";

    $output[] = "=== Agent Details ===";
    $output[] = "Brightcall User ID: " . $data['userId'];

    if (isset($data['agentId'])) {
        $output[] = "Brightcall Agent ID: " . $data['agentId'];
    }

    if (isset($data['agentName'])) {
        $output[] = "Agent Name: " . $data['agentName'];
    }

    if (isset($data['agentEmail'])) {
        $output[] = "Agent Email: " . $data['agentEmail'];
    }
    $output[] = "";

    $output[] = "=== Call Timing ===";

    if ($data['eventType'] === 'callEnded') {
        $output[] = "Call Start Time: " . tsToHuman($data['startTimestampMs']);

        if (isset($data['answerTimestampMs'])) {
            $output[] = "Call Answer Time: " . tsToHuman($data['answerTimestampMs']);
            $output[] = "Call Duration: " . getCallDuration($data['startTimestampMs'], $data['endTimestampMs']) . " seconds";
        }

        $output[] = "Call End Time: " . tsToHuman($data['endTimestampMs']);
    } else {
        $output[] = "Call Start Time: " . tsToHuman($data['timestampMs']);
    }

    if ($data['eventType'] === 'webphoneSummary') {
        $output[] = "";
        $output[] = "=== Lead Details ===";
        $output[] = "Goal: " . $data['goal'];
        $output[] = "Goal Type: " . $data['goalType'];
    }

    return implode("\n", $output);
}

// Gets the responsible person ID from the agent email
function getResponsiblePersonId(string $agentEmail): ?int
{
    $responsiblePersonId = null;

    $response = CRest::call('user.get', [
        'filter' => [
            'EMAIL' => $agentEmail
        ]
    ]);

    if (isset($response['result'][0]['ID'])) {
        $responsiblePersonId = $response['result'][0]['ID'];
    }

    return $responsiblePersonId;
}

// Converts timestamp in milliseconds to ISO 8601 format
function tsToIso($tsMs, $tz = 'Asia/Dubai')
{
    return (new DateTime("@" . ($tsMs / 1000)))->setTimezone(new DateTimeZone($tz))->format('Y-m-d\TH:i:sP');
}

// Converts timestamp in milliseconds to human readable format
function tsToHuman($tsMs, $tz = 'Asia/Dubai')
{
    $date = (new DateTime("@" . ($tsMs / 1000)))->setTimezone(new DateTimeZone($tz));
    $now = new DateTime('now', new DateTimeZone($tz));
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');

    $dateFormatted = $date->format('Y-m-d');
    $timeFormatted = $date->format('h:i A');

    if ($dateFormatted === $now->format('Y-m-d')) {
        return "Today at $timeFormatted";
    } elseif ($dateFormatted === $yesterday) {
        return "Yesterday at $timeFormatted";
    } else {
        return $date->format('F j, Y \a\t h:i A');
    }
}

// Converts time in HH:MM:SS format to seconds
function timeToSec($time)
{
    $time = explode(':', $time);
    return $time[0] * 3600 + $time[1] * 60 + $time[2];
}

// Converts seconds to time in HH:MM:SS format
function getCallDuration($startTimestampMs, $endTimestampMs)
{
    return ($endTimestampMs - $startTimestampMs) / 1000;
}

// Registers a call in Bitrix
function registerCall($fields)
{
    $res = CRest::call('telephony.externalcall.register', $fields);
    return $res['result'];
}

// Finishes a call in Bitrix
function finishCall($fields)
{
    $res = CRest::call('telephony.externalcall.finish', $fields);
    return $res['result'];
}

// Attaches a record to a call
function attachRecord($fields)
{
    $res = CRest::call('telephony.externalcall.attachRecord', $fields);
    return $res['result'];
}
