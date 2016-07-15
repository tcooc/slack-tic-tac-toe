<?php
require '../vendor/autoload.php';
require '../database.php';
$config = parse_ini_file('../config.ini');
$mysql = get_database();

if($_POST['token'] != $config['verification_token']) {
	http_response_code(403);
}
$team_id = $_POST['team_id'];
$channel_id = $_POST['channel_id'];
$user_id = $_POST['user_id'];
$user_name = $_POST['user_name'];
$text = explode (' ', trim($_POST['text']));

$response= [];
$help_message = 'To start a game of tic-tac-toe, challenge another player using `/tic-tac-toe challenge @[user]` in the same channel.
During a game, the player taking the turn can enter `/tic-tac-toe [a number between 1 to 9]` to make a move.
Reset the game with `/tic-tac-toe reset`';

function fetch_user($team_id, $query, $key = 'name') {
	global $mysql;
	$token = get_team_token($mysql, $team_id);
	$response = (new GuzzleHttp\Client(['base_uri' => 'https://slack.com/api/']))->request('GET', 'users.list', [
		'query' => ['token' => $token],
	]);
	$data = json_decode($response->getBody());
	foreach($data->{'members'} as $member) {
		if($member->{$key} == $query) {
			return $member;
		}
	}
	return NULL;
}

function format_board($game) {
	global $mysql, $team_id;
	$user = fetch_user($team_id, $game['turn'] ? $game['player_1_id'] : $game['player_2_id'], 'id');
	$str = $user->{'name'} . "'s move:\n```";
	for($x = 0; $x < 3; $x++) {
		for($y = 0; $y < 3; $y++) {
			$i = 3 * $x + $y;
			if($game['game'][$i] == '-') {
				$str .= $i + 1;
			} else {
				$str .= $game['game'][$i];
			}
			$str .= ' ';
			if($y == 2) {
				$str .= "\n";
			}
		}
	}
	return $str . '```';
}

function make_move(&$game, $player, $move, &$response) {
	global $mysql;
	if($game['game'][$move] == '-') {
		$game['game'][$move] = $player == 0 ? 'X' : 'O';
		$game['turn'] = 1 - $player;
		$response['response_type'] = 'in_channel';
		$response['text'] = format_board($game);
		return TRUE;
	}
	$response['text'] = 'That position is already taken.';
	return FALSE;
}

// check 8 possible win conditions
function check_win_condition($board) {
	$c = $board[0];
	if($c != '-' and $board[4] == $c and $board[8] == $c) {
		return TRUE;
	}
	$c = $board[2];
	if($c != '-' and $board[4] == $c and $board[6] == $c) {
		return TRUE;
	}
	for($i = 0; $i < 3; $i++) {
		$c = $board[$i];
		if($c != '-' and $board[$i + 3] == $c and $board[$i + 6] == $c) {
			return TRUE;
		}
		$c = $board[3 * $i];
		if($c != '-' and $board[3 * $i + 1] == $c and $board[3 * $i + 2] == $c) {
			return TRUE;
		}
	}
	return FALSE;
}

function check_tie_condition($board) {
	for($i = 0; $i < 9; $i++) {
		if($board[$i] == '-') {
			return FALSE;
		}
	}
	return TRUE;
}

if($text[0] == 'help') {
	// help command
	$response['text'] = $help_message;
}
else if($text[0] == 'challenge' and sizeof($text) == 2) {
	// start game command
	if(is_null(get_game($mysql, $channel_id))) {
		$opponent_name = $text[1];
		if($opponent_name[0] == '@') {
			$opponent_name = substr($opponent_name, 1);
		}
		$opponent = fetch_user($team_id, $opponent_name);
		if(is_null($opponent)) {
			$response['text'] = $opponent_name.' doesn\'t appear to be a user in team channel.';
		} else {
			create_game($mysql, $channel_id, $user_id, $opponent->{'id'});
			$response['response_type'] = 'in_channel';
			$response['text'] = $user_name.' has just challenged '.$opponent_name." to a game of tic-tac-toe!\n".$user_name." goes first as X.\n";
			$response['text'] .= format_board(get_game($mysql, $channel_id));
		}
	} else {
		// if game is in progress, print message
		$response['text'] = 'There is currently an ongoing game in this channel.';
	}
}
else if($text[0] == 'reset') {
	complete_game($mysql, $channel_id);
	$response['text'] = 'Game reset.';
}
else if(preg_match('/^[1-9]$/', $text[0]) === 1) {
	// make move command
	$move = (int)$text[0] - 1;
	$game = get_game($mysql, $channel_id);
	if(is_null($game)) {
		$response['text'] = 'No game currently in progress.';
	} else {
		if(($game['turn'] == 0 and $user_id == $game['player_1_id']) or ($game['turn'] == 1 and $user_id == $game['player_2_id'])) {
			// if it's the user's turn
			if(make_move($game, $game['turn'], $move, $response)) {
				if(check_win_condition($game['game'])) {
					$response['text'] .= "\n".$user_name." is the winner!";
					complete_game($mysql, $channel_id);
				} else if(check_tie_condition($game['game'])) {
					$response['text'] .= "\nGame is a tie!";
					complete_game($mysql, $channel_id);
				} else {
					update_game($mysql, $channel_id, $game['game'], $game['turn']);
				}
			}
		} else if($user_id == $game['player_2_id'] or $user_id == $game['player_1_id']) {
			$response['text'] = 'Wait for your turn!';
		} else {
			$response['text'] = 'You are not a player in this game. Wait current game to be finish or try again in another channel.';
		}
	}
}
else {
	// default command
	$game = get_game($mysql, $channel_id);
	if(is_null($game)) {
		$response['text'] = $help_message;
	} else {
		$response['response_type'] = 'in_channel';
		$response['text'] = format_board($game);
	}
}

header('Content-Type: application/json');
print(json_encode($response));
?>
