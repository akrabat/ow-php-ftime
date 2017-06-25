<?php
include "common/common.php";
require 'common/CouchDb.php';
require 'common/Whisk/src/OpenWhisk.php';

use FTime\CouchDb;

function main(array $args) : array
{
    error_log("Action {$_ENV['__OW_ACTION_NAME']} started");
    $name = $args["name"] ?? "";
    if (empty($name)) {
        return jsonResponse([
            'error' => "No name provided",
        ]);
    }

    // Fetch timezone for this friend from the database
    $couch = new CouchDb(getSetting("couchdb_url") . '/ftime/');
    $friend = $couch->get($name);
    if (!$friend) {
        return jsonResponse(['error' => "Could not find $name"], 404);
    }
    $timezone = $friend['timezone'];
    error_log("Found $name's timezone: $timezone");

    $whisk = new Akrabat\OpenWhisk();
    $params = ['timezone' => $timezone];
    $result = $whisk->invoke("{$_ENV['__OW_NAMESPACE']}/FTime/findTimeByTimezone", $params);

    $response = $result['response'];
    if (!$response['success']) {
        return slackResponse("Sorry, Could not find the time for $input");
    }


    return jsonResponse($response['result']);


    $response = [
        "pattern" => $place,
        'times' => $times,
    ];

    $result = jsonResponse($response);
    return $result;
}
