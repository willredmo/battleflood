
CREATE TABLE lobby (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	CONSTRAINT lobby_pk PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Create main lobby
INSERT INTO lobby (id) VALUES (0);
UPDATE lobby SET id = 0;

CREATE TABLE gameUser (
	username VARCHAR(30) NOT NULL,
	password VARCHAR(56) NOT NULL,
	lobbyId INT UNSIGNED NOT NULL DEFAULT 0,
	lastOnline DATETIME NOT NULL DEFAULT 0,
	isOnline BOOLEAN NOT NULL DEFAULT FALSE,
	CONSTRAINT gameUser_pk PRIMARY KEY (username),
	CONSTRAINT gameUsers_lobby_fk FOREIGN KEY (lobbyId) REFERENCES lobby(id)
) ENGINE=InnoDB;

CREATE TABLE message (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	lobbyId INT UNSIGNED NOT NULL,
	gameUserId VARCHAR(30) NOT NULL,
	text VARCHAR(150) NOT NULL,
	timestamp DATETIME NOT NULL,
	CONSTRAINT message_pk PRIMARY KEY (id),
	CONSTRAINT message_lobby_fk FOREIGN KEY (lobbyId) REFERENCES lobby(id)
		ON DELETE CASCADE
        ON UPDATE CASCADE,
	CONSTRAINT message_gameUser_fk FOREIGN KEY (gameUserId) REFERENCES gameUser(username)
		ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE challenge (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	gameUser1Id VARCHAR(30) NOT NULL,
	gameUser2Id VARCHAR(30) NOT NULL,
	gameUser1Accepted BOOLEAN,
	gameUser2Accepted BOOLEAN,
	timestamp DATETIME NOT NULL,
	CONSTRAINT message_pk PRIMARY KEY (id),
	CONSTRAINT challenge_gameUser1Id_fk FOREIGN KEY (gameUser1Id) REFERENCES gameUser(username)
		ON DELETE CASCADE
        ON UPDATE CASCADE,
	CONSTRAINT challenge_gameUser2Id_fk FOREIGN KEY (gameUser2Id) REFERENCES gameUser(username)
		ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE game (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	lobbyId INT UNSIGNED NOT NULL,
	width INT UNSIGNED NOT NULL,
	height INT UNSIGNED NOT NULL,
	gameUserWon VARCHAR(30) DEFAULT NULL,
	gameUser1 VARCHAR(30) NOT NULL,
	gameUser2 VARCHAR(30) NOT NULL,
	gameUserTurn VARCHAR(30) NOT NULL,
	botGame BOOLEAN DEFAULT FALSE NOT NULL,
	CONSTRAINT game_pk PRIMARY KEY (id),
	CONSTRAINT game_lobby_fk FOREIGN KEY (lobbyId) REFERENCES lobby(id)
		ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE color (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	colorValue VARCHAR(10) NOT NULL,
	CONSTRAINT color_pk PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Create colors
INSERT INTO color (colorValue) VALUES ("#F6511D");
INSERT INTO color (colorValue) VALUES ("#FFB400");
INSERT INTO color (colorValue) VALUES ("#00A6ED");
INSERT INTO color (colorValue) VALUES ("#7FB800");
INSERT INTO color (colorValue) VALUES ("#0D2C54");

CREATE TABLE gameBlock (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	x INT NOT NULL,
	y INT NOT NULL,
	colorId INT UNSIGNED NOT NULL,
	gameId INT UNSIGNED NOT NULL,
	gameUserId VARCHAR(30) DEFAULT NULL,
	CONSTRAINT gameBlock_pk PRIMARY KEY (id),
	CONSTRAINT gameBlock_colorId_fk FOREIGN KEY (colorId) REFERENCES color(id),
	CONSTRAINT gameBlock_gameId_fk FOREIGN KEY (gameId) REFERENCES game(id)
		ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- For debugging
-- DROP TABLE gameBlock;
-- DROP TABLE color;
-- DROP TABLE game;
-- DROP TABLE challenge;
-- DROP TABLE message;
-- DROP TABLE gameUser;
-- DROP TABLE lobby;