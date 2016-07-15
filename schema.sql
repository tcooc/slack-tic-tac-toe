CREATE TABLE IF NOT EXISTS team (
	team_id char(9) primary key,
	access_token char(60)
);

CREATE TABLE IF NOT EXISTS game (
	channel_id char(9) primary key,
	player_1_id char(9) not null,
	player_2_id char(9) not null,
	game char(9) default '---------',
	turn boolean default 0
);
