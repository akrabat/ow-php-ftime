<?php
include 'common/common.php';
require 'common/CouchDb.php';

use FTime\CouchDb;

function main(array $args) : array
{
    error_log("Action {$_ENV['__OW_ACTION_NAME']} started");
    $couch = new CouchDb(getSetting("couchdb_url") . '/ftime/');
    $text = $args["text"] ?? "";

    $result = preg_match('/([\w]+)[\W]+([\w]+)/', $text, $matches);
    if (!$result) {
        return slackResponse("Usage: /settimezone {friend's name} {timezone}");
    }
    $name = $matches[1];
    $timezone = $matches[2];

    if (strtolower($timezone) == 'none') {
        $user = $couch->get($name);
        if (!$user) {
            return slackResponse("Nothing to do as $name does not have an associated time zone.");
        }
        
        $result = $couch->delete($name, $user['_rev']);
        if (!$result) {
            return slackResponse("Failed to remove time zone for $name");
        }
        return slackResponse("$name no longer has a time zone set");
    }

    // find action timezone name from what we've been given
    $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $result = substring_array_search($timezone, $tzlist);

    // error if we don't find an actual timezone, or we find more than one.
    if (count($result) == 0) {
        return slackResponse("Could not find a time zone for $timezone");
    } elseif (count($result) > 1) {
        $timezones = implode(', ', $result);
        return slackResponse("Found these time zones for $timezone: $timezones."
            . 'Please try again with a more specific time zone.');
    }

    // insert into database
    $actualTimezone = array_shift($result);
    $data = ['timezone' => $actualTimezone, 'type' => 'friend'];
    $result = $couch->update($name, $data);

    if (!$result) {
        return slackResponse("Failed to set time zone for $name");
    }

    return slackResponse("$name now has a time zone of $actualTimezone");
}


function slackResponse($text)
{
    $response = [
        "response_type" => "in_channel",
        "text" => $text,
    ];

    return jsonResponse($response);
}
