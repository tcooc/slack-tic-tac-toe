<?php
require '../vendor/autoload.php';
$config = parse_ini_file('../config.ini');

use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://slack.com/api/']);

$response = $client->request('GET', 'oauth.access', [
	'query' => ['client_id' => $config['client_id'], 'client_secret' => $config['client_secret'], 'code' => $_GET['code']],
]);


$data = json_decode($response->getBody());

print($response->getBody());

if($data->{'ok'}) {
	print($data->{'access_token'});
	print($data->{'user_id'});
	print($data->{'team_name'});
	print($data->{'team_id'});
} else {
	print($data->{'error'});
}

?>
