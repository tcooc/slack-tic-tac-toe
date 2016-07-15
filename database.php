<?php
function get_database() {
	$config = parse_ini_file(dirname(__FILE__).'/config.ini');
	$mysql = new mysqli($config['mysql_ip'], $config['mysql_user'], $config['mysql_password'], $config['mysql_database']);
	return $mysql;
}

function update_team_token($mysql, $team_id, $access_token) {
	$stmt = $mysql->prepare('INSERT INTO team VALUES (?, ?) ON DUPLICATE KEY UPDATE access_token=VALUES(access_token)');
	$stmt->bind_param('ss', $team_id, $access_token);
	return $stmt->execute();
}

function get_team_token($mysql, $team_id) {
	$result = [];
	$stmt = $mysql->prepare('SELECT access_token FROM team WHERE team_id=?');
	$stmt->bind_param('s', $team_id);
	$stmt->execute();
	$stmt->bind_result($token);
	$stmt->fetch();
	return $token;
}

function create_game($mysql, $channel_id, $player1, $player2) {
	$stmt = $mysql->prepare('INSERT INTO game(channel_id, player_1_id, player_2_id) VALUES (?, ?, ?)');
	$stmt->bind_param('sss', $channel_id, $player1, $player2);
	return $stmt->execute();
}

function get_game($mysql, $channel_id) {
	$result = [];
	$stmt = $mysql->prepare('SELECT channel_id, player_1_id, player_2_id, game, turn FROM game WHERE channel_id=?');
	$stmt->bind_param('s', $channel_id);
	$stmt->execute();
	$stmt->bind_result($result['channel_id'], $result['player_1_id'], $result['player_2_id'], $result['game'], $result['turn']);
	$stmt->fetch();
	if(is_null($result['channel_id'])) {
		return NULL;
	}
	return $result;
}

function update_game($mysql, $channel_id, $game, $turn) {
	$stmt = $mysql->prepare('UPDATE game SET game=?, turn=? WHERE channel_id=?');
	$stmt->bind_param('sds', $game, $turn, $channel_id);
	return $stmt->execute();
}

function complete_game($mysql, $channel_id) {
	$stmt = $mysql->prepare('DELETE FROM game WHERE channel_id=?');
	$stmt->bind_param('s', $channel_id);
	return $stmt->execute();
}

?>
