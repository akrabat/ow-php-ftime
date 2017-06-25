<?php
include "common/common.php";

function main(array $args) : array
{
    error_log("Action {$_ENV['__OW_ACTION_NAME']} started");
    $timezone = $args["timezone"] ?? "";
    if (empty($timezone)) {
        return jsonResponse([
            'pattern' => $timezone,
            'times' => []
        ]);
    }

    $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $result = substring_array_search($timezone, $tzlist);

    $times = [];
    if (!empty($result)) {
        foreach ($result as $timezone) {
            $d = new DateTime();
            $d->setTimezone(new DateTimeZone($timezone));
            $time = $d->format('H:i:s');

            $times[] = [
                'timezone' => $timezone,
                'time' => $time,
            ];
        }
    } else {
        $time = false;
    }

    $response = [
        "pattern" => $timezone,
        'times' => $times,
    ];

    $result = jsonResponse($response);
    return $result;
}
