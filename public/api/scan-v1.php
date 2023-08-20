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
  nmap -p 22,80,135,139,443,445,5800,5900,3000,3001,3002,3306,1433,9443 --top-ports 20 172.24.230.20-254 -nPR --open -oX -
END;

$output = shell_exec($command);
$xml = simplexml_load_string($output);
$xml = json_decode(json_encode($xml),true);


foreach ($xml['host'] as $h) {
    if (array_key_exists('@attributes', $h['address'])) {
        $o['addr'][$h['address']['@attributes']['addrtype'] ?? 'unknown'] = $h['address']['@attributes']['addr'] ?? 'unknown';
    }

    if (array_key_exists(0, $h['address'])) {
        if (array_key_exists('@attributes', $h['address'][0])) {
            foreach ($h['address'] as $a) {
                $o['addr'][$a['@attributes']['addrtype'] ?? 'unknown'] = $a['@attributes']['addr'] ?? 'unknown';
            }
        }
    }
    $o['host'] = $h['hostnames'] ?? [];
    foreach ($h['ports']['port'] as $p) {
        $pp[$p['@attributes']['portid'] ?? 'unknown'] = $p['service']['@attributes']['name'] ?? 'unknown';
    }
    $o['port'] = $pp ?? [];
    $o['port']['open'] = count($pp ?? []);
    $o['status'] = $h['status']['@attributes']['state'] ?? 'unknown';
    $data[] = $o;
}

if (is_null($data)) { throw new \Exception('Network scan unsuccessful', 200); }

// MYSQL
$link = mysqli_connect($_ENV['MYSQL_HOSTNAME'], $_ENV['MYSQL_USERNAME'], $_ENV['MYSQL_PASSWORD'], $_ENV['MYSQL_DATABASE']);
if (!$link) { $n = "Unable to connect to database."; }

foreach ($data as $d) {
    $d = json_encode($d);
    mysqli_execute_query(
        mysql: $link,
        //query: "INSERT INTO `t_adm_network_scan` (`c_data`, `c_scan`) VALUES(?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `c_data` = ?, `c_scan` = CURRENT_TIMESTAMP",
        query: "INSERT INTO `t_adm_network_scan` (`c_data`, `c_scan`) VALUES(?, CURRENT_TIMESTAMP)",
        params: [$d]
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

