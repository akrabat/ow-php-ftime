<?php

/**
 * Retreive a setting from the `settings` parameter
 */
function getSetting(string $name, $default = null)
{
    $whiskInput = json_decode($_ENV['WHISK_INPUT'] ?? '{"settings: []"}', true);

    if (array_key_exists($name, $whiskInput['settings'])) {
        return $whiskInput['settings'][$name];
    }

    return $default;
}

/**
 * create a response for sending back to OpenWhisk in either standard invoke format
 * or web-action format.
 */
function jsonResponse(array $data, int $code = 200, array $headers = []) : array
{
    if (stripos($_ENV['WHISK_INPUT'], '__ow_headers') === false) {
        // not a web action as there's no headers
        return $data;
    }

    // some default headers
    $headers["Content-Type"] = "application/json";
    $headers["Access-Control-Allow-Origin"] = "*";

    $body = base64_encode(json_encode($data));

    return [
        "body" => $body,
        "statusCode" => $code,
        "headers" => $headers,
    ];
}

/**
 * A version of array_search() that does a sub string match on $needle
 *
 * @param  mixed   $needle    The searched value
 * @param  array   $haystack  The array to search in
 * @return boolean
 */
function substring_array_search($needle, array $haystack)
{
    $filtered = array_filter($haystack, function ($item) use ($needle) {
        return false !== stripos($item, $needle);
    });
 
    return $filtered;
}
