<?php

/**
 * Generates html header
 * @param  string $title - For title display
 * @return String        - Finished html header
 */
function html_header($title="home"){ 
	$string = <<<END
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<link rel="icon" href="../../../../favicon.ico">

	<title>BattleFlood - $title</title>

	<!-- Outdated browser -->
	<script src="scripts/outdated.js" type="text/javascript"></script>

	<!-- Material Icons -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

	<!-- Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" 
        integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

	<!-- Bootstrap -->
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet">

	<!-- Less --> 
	<link rel="stylesheet/less" type="text/css" href="styles/styles.less" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/less.js/3.8.1/less.min.js" type="text/javascript"></script>
	

	<!-- Snackbar -->
	<link rel="stylesheet" type="text/css" href="styles/snackbar.min.css" />

	<!-- Roboto Font -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700" rel="stylesheet">

	<!-- Scripts -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/svg.js/2.6.6/svg.min.js" type="text/javascript"></script>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" type="text/javascript"></script>
</head>

<body>
END;
return $string;
}

/**
 * Puts content inside of main container
 * @param  String $content - Content to put inside of container
 * @return String          - Container with content
 */
function html_mainContainer($content) {
	$string = '<main role="main">';
	$string .= $content;
	$string .= '</main>';
	return $string;
}

/**
 * Generates html footer
 * @return string - Finished html footer
 */
function html_footer($includeGameScripts){
	$string;
	if ($includeGameScripts) {
		$string = <<<END
			<script src="scripts/snackbar.min.js" type="text/javascript"></script>
			<script src="scripts/game.js" type="text/javascript"></script>
			<script src="scripts/chat.js" type="text/javascript"></script>
			<script src="scripts/main.js" type="text/javascript"></script>
			<script src="scripts/login.js" type="text/javascript"></script>
		</body>
		</html>
END;
	} else {
		$string = <<<END
			<script src="scripts/snackbar.min.js" type="text/javascript"></script>
			<script src="scripts/login.js" type="text/javascript"></script>
		</body>
		</html>
END;
	}
	return $string;
}

// /**
//  * Creates html for login form
//  * @param  Boolean $failedLogin - Whether or not there was a previous failed login
//  * @return String               - html login form
//  */
// function html_createUserForm($errors) {
// 	$failedString = "";
// 	if ($errors != "") {
// 		$failedString = <<<END
// 		<script>
// 			$(document).ready(function() {
// 				Snackbar.show({
// 					pos: "bottom-center",
// 					text: "$errors",
// 					showAction: true,
// 					duration: 0
// 				});
// 			});
// 		</script>
// END;
// 	} 
// 	$string = <<<END
// 	<div id="create" style="display:none">
// 		<form method="post">
// 			<div><span>Username</span><input name="username"></div>
// 			<div><span>Password</span><input name="password" type="password"></div>
// 			<input type="submit" value="Create" name="create" class="button">
// 		</form>
// 	</div>

	
// 	$failedString
// END;
// 	return $string;
// }

/**
 * Creates html for login form
 * @param  Boolean $failedLogin - Whether or not there was a previous failed login
 * @return String              - html login form
 */
function html_loginForm() {
	$string = <<<END
	<div class="login-form">
		<div class="main-div">
			<div class="panel">
				<h2>BattleFlood Login</h2>
				<p>Please enter your username and password</p>
			</div>
			<form>
				<div class="form-group">
					<input class="form-control username" placeholder="Username">
				</div>
				<div class="form-group">
					<input type="password" class="form-control password" placeholder="Password">
				</div>
				<div class="create">
					<a href="javascript:{}" id="showCreate">Create Account</a>
				</div>
			<button type="button" id="loginButton" class="btn btn-primary">Login</button>

		</div>
	</div>

	<div class="create-form">
		<div class="main-div">
			<div class="panel">
				<h2>Create Account</h2>
				<p>Please enter your username and password</p>
			</div>
			<form>
				<div class="form-group">
					<input class="form-control username" placeholder="Username">
				</div>
				<div class="form-group">
					<input type="password" class="form-control password" placeholder="Password">
				</div>
				<div class="form-group">
					<input type="password" class="form-control confirm" placeholder="Confirm Password">
				</div>
				<div class="back">
					<a href="javascript:{}" id="hideCreate">Go Back</a>
				</div>
			<button type="button" id="createButton" class="btn btn-primary">Create Account</button>
		</div>
	</div>
END;
	return $string;
}

function html_game($username) {
	$string = <<<END
	<script>var username="$username"</script>
	<div class="left">
		<div id="game">
			<div class="currentTurn"></div>
			<div class="user1Info">
				<span class="name"></span>
				<span class="score"></span>
			</div>
			<div class="gameBlocks"></div>
			<div class="user2Info">
				<span class="name"></span>
				<span class="score"></span>
			</div>
			<div class="inputBlocks">
				<div class="loadingMove waiting">
					<i class="material-icons">sync</i>
				</div>
			</div>
		</div>
		<div id="lobbyGameScreen">
			<div>
				<h1>Welcome to BattleFlood</h2>
				<p>To start a game you can challenge a player by clicking on the user or challenge a bot by clicking yourself</p>
			</div>	
		</div>
		<div id="gameOver">
			<div class="info">
				<span class="result"></span>
				<span class="user1"></span>
				<span class="user2"></span>
				<button class="btn">Play Again</button>
			</div>
			<div class="background"></div>
		</div>
	</div>
	<div class="right">
		<div class ="top">
			<h2 id="currentLobby"></h2>
			<div id="menu">
				<div class="mainLobby">
					<button type="button" class="btn btn-secondary logout">Logout</button>
				</div>
				<div class="gameLobby btn-group" role="group">
					<button type="button" class="btn btn-secondary logout">Logout</button>
					<button type="button" class="btn btn-secondary exitToLobby">Exit to Lobby</button>
					<button type="button" class="btn btn-secondary newGame">New Game</button>
				</div>
			</div>
			<div id="menuIcons">
				<i class="material-icons chat">chat<span class="badge badge-pill badge-danger"></span></i>
				<div class="line"></div>
				<i class="material-icons users">people</i>
			</div>
		</div>
		<div class="section usersSection">
			<h3>
				Online Users
				<i id="helpIcon" class="material-icons">help_outline</i>
				<i id="closeUsersIcon" class="material-icons">close</i></h3>
			</h3>
			<div id="users">
			</div>
		</div>
		<div class="section chatSection">
			<h3>
				Chat 
				<i id="closeChatIcon" class="material-icons">close</i></h3>
			<div id="chat">
				<div class="messages"></div>
				<div class="input">
					<input placeholder="Send a message">
					<button type="button" class="btn btn-primary">Send</button>
				</div>
			</div>
		</div>
	</div>
	<div class="templates">
		<div class="message">
			<p class="messageContainer"><span class="username">Username: </span><span class="text">hello whats up this is a test message</span></p>
			<span class="timestamp">11:01 AM    |    June 9</span>
		</div>
		<div class="userButton btn btn-primary">
			<div class="info">
				<i class="material-icons">account_circle</i>
				<span class="name">Username</span>
			</div>
			<div class="challengeStatus"></div>
		</div>
		<div class="acceptDeclineOptions">
			<div class="accept">
				<i class="material-icons">check</i>
			</div>
			<div class="decline">
				<i class="material-icons">close</i>
			</div>
		</div>
		<div class="waiting">
			<i class="material-icons">sync</i>
		</div>
	</div>
END;
	return $string;
}
