<?php

define("__ROOT__", realpath(__DIR__ . '/../..'));
$params = $_GET;
$data = [
    'timestamp' => microtime(),
    'status' => 'OK',
    'params' => $params,
    'service' => basename(__ROOT__),
    'provided-for' => $_SERVER['X-GLUED-AUTH-UUID'] ?? 'anon'
];

header("Content-Type: application/json");
echo json_encode($data);

