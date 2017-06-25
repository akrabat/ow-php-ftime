<?php
include "common/common.php";

function main(array $args) : array
{
    $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

    $response = [
        "timezones" => $tzlist,
    ];

    return jsonResponse($response);
}
