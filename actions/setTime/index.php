<?php
include "common/common.php";

function main(array $args) : array
{
    return jsonResponse(["action" => "setTime"]);
}
