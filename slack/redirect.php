<?php
require '../vendor/autoload.php';
require '../database.php';
$config = parse_ini_file('../config.ini');

$response = (new GuzzleHttp\Client(['base_uri' => 'https://slack.com/api/']))->request('GET', 'oauth.access', [
	'query' => ['client_id' => $config['client_id'], 'client_secret' => $config['client_secret'], 'code' => $_GET['code']],
]);
$data = json_decode($response->getBody());

if($data->{'ok'}) {
	// add token to database
	if(!$update_team_token(get_database(), $data->{'team_id'}, $data->{'access_token'})) {
		echo 'Failed to add team. Please try again later.';
	} else {
		echo 'Added to team '.$data->{'team_name'}.'.';
	}
	$stmt->close();
} else {
	// redirect to add page to retry
	header("Location: /add.php", TRUE, 302);
}

?>
