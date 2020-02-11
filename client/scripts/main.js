var mainLobby = 0;

// Class that handles lobby and user stuff
class Main {
    constructor(username) {
        this.username = username;
        this.onlineRefresh = 30000;
        this.lobbyRefresh = 1000;
        this.serverUsersRefresh = 1000;
        this.challengeRefresh = 1000;

        this.loggedOut = false;
        this.currentUsers = new Map();
        this.currentChallenges = new Map();
        this.lobbyId = null;

        this.requestChallenges = true;

        this.chat = new Chat(this);
        this.game = new Game(this);

        this.userOnline();
        this.getCurrentChallenges();
        this.getCurrentLobbyId();
        this.addMenuControls();
        this.getServerUsers();
    }

    // Resets lobby data and ui
    resetLobby() {
        // Reset lobby
        this.currentUsers = new Map();
        this.currentChallenges = new Map();
        $("#users").empty();
        
        // Reset chat
        this.chat.reset();

        // Reset game
        this.game.reset();
    }

    // Let server know you are online
    userOnline() {
        if (this.loggedOut) {
            return;
        }
        this.MyXHR('get', { method: "userOnline", a: "user", data: "" }, this).done((json) => {
            setTimeout(() => this.userOnline(), this.onlineRefresh);
        });
    }

    // Check if lobby change
    getCurrentLobbyId() {
        this.MyXHR('get', { method: "getCurrentLobby", a: "user", data: "" }, this).done((json) => {
            this.updateLobbyId(json);
            setTimeout(() => this.getCurrentLobbyId(), this.lobbyRefresh);
        });
    }

    // Checks if lobby needs to change
    updateLobbyId(lobbyId) {
        var lobbyChange = false;
        if (this.lobbyId == null) {
            this.lobbyId = lobbyId;
            lobbyChange = true;
        } else if (this.lobbyId != lobbyId) {
            this.lobbyId = lobbyId;
            lobbyChange = true;
            this.resetLobby();
        }

        if (lobbyChange) {
            if (this.lobbyId == mainLobby) {
                this.getCurrentChallenges();
                this.requestChallenges = true;
                $("#currentLobby").html("Main Lobby");
                $("#menu .mainLobby").show();
                $("#menu .gameLobby").hide();
            } else {
                this.requestChallenges = true;
                $("#currentLobby").html("Lobby: " + this.lobbyId);
                $("#menu .mainLobby").hide();
                $("#menu .gameLobby").show();
            }

            this.game.newLobby(this.lobbyId);
        }
    }

    // Applies action listeners to menu buttons
    addMenuControls() {
        $("#menu .mainLobby .logout").click(() => {
            this.logout();
        });
        $("#menu .gameLobby .logout").click(() => {
            this.logout();
        });
        $("#menu .gameLobby .exitToLobby").click(() => {
            this.exitToLobby();
        });
        $("#menu .gameLobby .newGame").click(() => {
            this.game.newGame();
        });

        $("#helpIcon").popover({
            content: "To start a game you can challenge a player by selecting the user or challenge a bot by selecting yourself",
            placement: "bottom",
            trigger: "click"
        });

        // Hide/show chat
        $("#menuIcons .chat").click(() => {
            $(".chatSection, #menuIcons").addClass("showChat");
            this.chat.clearUnread();
        });
        $("#closeChatIcon").click(() => {
            $(".chatSection, #menuIcons").removeClass("showChat");
        });

        // Hide/show users
        $("#menuIcons .users").click(() => {
            $(".usersSection, #menuIcons").addClass("showUsers");
        });
        $("#closeUsersIcon").click(() => {
            $(".usersSection, #menuIcons").removeClass("showUsers");
        });
    }

    // Requests to logout
    logout() {
        this.loggedOut = true;
        this.MyXHR('get', { method: "logout", a: "user", data: "" }, this).done((json) => {
            location.reload();
        });
    }

    // Request to go to main lobby
    exitToLobby() {
        this.MyXHR('get', { method: "exitToMainLobby", a: "user", data: "" }, this).done((json) => {
        });
    }

    // Current users in lobby
    getServerUsers() {
        this.MyXHR('get', { method: "getLobbyUsers", a: "user", data: "" }, this).done((json) => {
            this.displayLobby(json);
            setTimeout(() => this.getServerUsers(), this.serverUsersRefresh);
        });
    }

    // Displays lobby ui and updates data
    displayLobby(users) {
        if (this.currentUsers.size == 0) {
            // Add to data
            for (var i = 0; i < users.length; i++) {
                user = users[i];
                this.currentUsers.set(user, user);
                this.displayUser(user);
            }
        } else {
            // Add new users
            var onlineUsers = new Map();
            for (var i = 0; i < users.length; i++) {
                var user = users[i];
                onlineUsers.set(user, "online");
                if (!this.currentUsers.has(user)) {
                    this.currentUsers.set(user, user);
                    this.displayUser(user);
                } 
            }

            // Remove offline users
            this.currentUsers.forEach((value, key) => {
                if (!onlineUsers.has(key)) {
                    $("#onlineUser_" + key).remove();
                    this.currentUsers.delete(key);
                }
            });
        }
    }

    // Displays single user
    displayUser(username) {
        var htmlUserButton = $(".templates .userButton").clone();
        if (this.username == username) {
            $(htmlUserButton).removeClass("btn-primary");
            $(htmlUserButton).addClass("btn-success");
        }
        $(htmlUserButton).find(".name").html(username);
        $(htmlUserButton).attr('id', 'onlineUser_' + username).click(() => {
            this.challengeUser(username);
        });
        $("#users").append(htmlUserButton);
    }

    // Challenges user
    challengeUser(username) {
        var data = {
            "username": username
        }
        this.MyXHR('get', { method: "challengeUser", a: "user", data: JSON.stringify(data) }, this).done((json) => {
            if (json != "") {
                Snackbar.show({
                    pos: "bottom-center",
                    text: json,
                    showAction: true,
                    duration: 5000
                });
            }
        });
    }
    
    // Gets current challenges
    getCurrentChallenges() {
        this.MyXHR('get', { method: "getChallenges", a: "user", data: "" }, this).done((json) => {
            if (this.currentUsers.length != 0) {
                this.displayCurrentChallenges(json);
            }
            if (this.requestChallenges) {
                setTimeout(() => this.getCurrentChallenges(), this.challengeRefresh);
            }
        });
    }

    // Displays current challenges
    displayCurrentChallenges(challenges) {
        if (this.currentChallenges.size == 0) {
            for (var i = 0; i < challenges.length; i++) {
                var challenge = challenges[i];
                // Don't add challenge unless user is in lobby already
                if (this.currentUsers.has(challenge.challengedUser)) {
                    this.currentChallenges.set(challenge.challengedUser, challenge);
                    this.displayChallenge(challenge);
                }
            }
        } else {
            // Add new challenges
            var serverChallenges = new Map();
            for (var i = 0; i < challenges.length; i++) {
                var challenge = challenges[i];
                serverChallenges.set(challenge.challengedUser, "challenge");
                if (!this.currentChallenges.has(challenge.challengedUser) && this.currentUsers.has(challenge.challengedUser)) {
                    this.currentChallenges.set(challenge.challengedUser, challenge);
                    this.displayChallenge(challenge);
                } 
            }

            // Remove old challenges
            this.currentChallenges.forEach((value, key) => {
                if (!serverChallenges.has(key)) {
                    $("#onlineUser_" + value.challengedUser + " .challengeStatus").empty();
                    $("#onlineUser_" + value.challengedUser).click(() => {
                        this.challengeUser(value.challengedUser);
                    });
                    this.currentChallenges.delete(key);
                }
            });
        }
    }

    // Displays single challenges
    displayChallenge(challenge) {
        var userHtml = $("#onlineUser_" + challenge.challengedUser);
        $(userHtml).unbind("click"); 
        if (challenge.otherAccepted) {
            var acceptDeclineOptions = $(".templates .acceptDeclineOptions").clone();
            var eventMain = this;
            $(acceptDeclineOptions).find(".accept").click(() => {
                eventMain.sendChallengeResponse("accept", challenge);
            });
            $(acceptDeclineOptions).find(".decline").click(() => {
                eventMain.sendChallengeResponse("decline", challenge);
            });
            $(userHtml).find(".challengeStatus").append(acceptDeclineOptions);
        } else if (challenge.youAccepted) {
            var waitingIcon = $(".templates .waiting").clone();
            $(userHtml).find(".challengeStatus").append(waitingIcon);
        }
    }

    // Sends challenges response 
    sendChallengeResponse(acceptDecline, challenge) {
        var data = {
            "challengedUser": challenge.challengedUser,
            "acceptDecline": acceptDecline
        }
        this.MyXHR('get', { method: "respondToChallenge", a: "user", data: JSON.stringify(data) }, this).done((json) => {
            Snackbar.show({
                pos: "bottom-center",
                text: json,
                showAction: true,
                duration: 5000
            });
        });
    }

    // For game and chat to get current username
    getUsername() {
        return this.username;
    }

    // For send requests to server
    MyXHR(getPost,d,context){
        //ajax shortcut
        return $.ajax({
            type: getPost,
            async: true,
            cache: false,
            url:'../server/mid.php',
            data:d,
            dataType:'json',
            context: context
        }).fail(function(err){
            console.log(d);
            console.log("AJAX error");
            console.log(err.responseText);
        });
    }
}

// Starts up game
$(document).ready(function() {
    // username should be loaded in from php
    var main = new Main(username);
});