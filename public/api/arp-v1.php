<?php

use Dotenv\Dotenv;

define("__ROOT__", realpath(__DIR__ . '/../..'));
require_once(__ROOT__ . '/vendor/autoload.php');

if (!isset($_ENV['GLUED_PROD'])) {
    $dotenv = Dotenv::createImmutable(__ROOT__);
    $dotenv->safeLoad();
}

// Run the nmap command and capture the output
$command = <<<END
  arp-scan -lxgr 2 | awk -F'\t' 'BEGIN { printf "[" } { printf "{\"ipv4\":\"%s\",\"mac\":\"%s\",\"vendor\":\"%s\"},", $1, $2, $3 } END { printf "{}]"}' | sed 's/},]/}]/'
END;

// MYSQL
$link = mysqli_connect($_ENV['MYSQL_HOSTNAME'], $_ENV['MYSQL_USERNAME'], $_ENV['MYSQL_PASSWORD'], $_ENV['MYSQL_DATABASE']);
if (!$link) { $n = "Unable to connect to database."; }

$data = json_decode(shell_exec($command), true);
$data = array_filter($data, function($obj) {
    return $obj !== [];
});

foreach ($data as $d) {
    $d = json_encode($d);
    mysqli_execute_query(
        mysql: $link,
        query: "INSERT INTO `t_adm_network_arp` (`c_data`, `c_lastseen`) VALUES(?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `c_data` = ?, `c_lastseen` = CURRENT_TIMESTAMP",
        params: [$d, $d]
    );
}
mysqli_close($link);

$params = $_GET;
$data = [
    'timestamp' => microtime(),
    'status' => 'OK',
    'params' => $params,
    'service' => basename(__ROOT__),
    'provided-for' => $_SERVER['X-GLUED-AUTH-UUID'] ?? 'anonymous',
    'data' => $data
];

header("Content-Type: application/json");
echo json_encode($data);

