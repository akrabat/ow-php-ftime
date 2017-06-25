<?php
include 'common/common.php';
require 'common/Whisk/src/OpenWhisk.php';

const TIME_FORMAT = 'g:ia';

function main(array $args) : array
{
    error_log("Action {$_ENV['__OW_ACTION_NAME']} started");
    $name = $args["text"] ?? "";

    // invoke FTime/findFriendTime action
    $params = ['name' => $name];
    $whisk = new Akrabat\OpenWhisk();
    $result = $whisk->invoke("{$_ENV['__OW_NAMESPACE']}/FTime/findFriendTime", $params);
    $response = $result['response'];
    if (!$response['success']) {
        // no friend called that, maybe it's a timezone?
        // invoke FTime/findTimeByTimezone action
        $params = ['timezone' => $name];
        $whisk = new Akrabat\OpenWhisk();
        $result = $whisk->invoke("{$_ENV['__OW_NAMESPACE']}/FTime/findTimeByTimezone", $params);

        $response = $result['response'];
    }

    if (!$response['success']) {
        return slackResponse("Sorry. Could not find the time for $name");
    }
    $pattern = $response['result']['pattern'];
    $times = $response['result']['times'];

    $lines = [];
    if (count($times) == 0) {
        return slackResponse("Sorry. Could not find the time for $name");
    }
    
    if (count($times) == 1 && stripos($times[0]['timezone'], $name) === false) {
        // we found our friend's time
        $time = date(TIME_FORMAT, strtotime($times[0]['time']));
        return slackResponse("It's currently $time for $name.");
    }

    foreach ($times as $item) {
        $time = date(TIME_FORMAT, strtotime($item['time']));
        $lines[] = "The time in {$item['timezone']} is $time.";
    }
    return slackResponse(implode("\n", $lines));
}


function slackResponse($text)
{
    $response = [
        "response_type" => "in_channel",
        "text" => $text,
    ];

    return jsonResponse($response);
}
