var mainLobby = 0;

// Class that handles game stuff
class Game {
    constructor(main) {
        this.username = main.getUsername();
        this.MyXHR = main.MyXHR;
        this.boardRefresh = 300;

        this.svgGameBlocks = SVG($("#game .gameBlocks")[0]);
        this.svgInputBlocks = SVG($("#game .inputBlocks")[0]);
        this.gameWidth = $("#game").width();
        this.blocks = [];
        this.colors = [];
        this.blockSide = null;

        this.gameId = null;
        this.width = null;
        this.height = null;
        this.userTurn = null;
        this.user1 = null;
        this.user2 = null;
        this.user1Score = null;
        this.user2Score = null;
        this.user1Color = null;
        this.user2Color = null;
        this.userWon = null;

        this.getColors();
        this.stopRequesting = true;
    }

    // Resets data and updates ui
    newLobby(lobbyId) {
        this.reset();
        $("#gameOver").hide();
        if (lobbyId != mainLobby) {
            this.stopRequesting = false;
            this.getBoard();
            $("#game").show();
            $("#lobbyGameScreen").hide();
            $("main").addClass("inGame");
        } else {
            this.stopRequesting = true;
            $("#lobbyGameScreen").show();
            $("#game").hide();
            $("main").removeClass("inGame");
        }
    }

    // Resets data
    reset() {
        this.svgGameBlocks.clear();
        this.blocks = [];
        this.width = null;
        this.height = null;
        this.user1 = null;
        this.user2 = null;
        this.blockSide = null; 
        this.userTurn = null;
        this.user1Score = null;
        this.user2Score = null;
    }

    // Sends request for colors for input
    getColors() {
        this.MyXHR('get', { method: "getCurrentColors", a: "game", data: "" }, this).done((json) => {
            this.colors = json;
            this.displayInputBlocks();
        });
    }

    // Displays input blocks
    displayInputBlocks() {
        // Issue on mobile where width is 0
        if ($("#game .inputBlocks").width() == 0) {
            setTimeout(() => {
                this.displayInputBlocks();
            });
            return;
        }
        var totalColors = this.colors.length;
        var divWidth = $("#game .inputBlocks").width();
        var divHeight = $("#game .inputBlocks").height();
        
        var blockSide = divWidth / totalColors;
        if (blockSide > divHeight) {
            blockSide = divHeight;
        }
        // Center horizontal
        var leftPadding = (divWidth - (blockSide * totalColors)) / 2;
        var topPadding = ((divHeight - blockSide) / 2);
        var padding = 10;
        blockSide -= padding;

        var i = 0;
        this.colors.forEach(color => {
            this.svgInputBlocks.rect(blockSide, blockSide).attr({ fill: color.colorValue, id: "inputBlock_" + color.id }).x(leftPadding + (padding/2 + i*blockSide + padding*i)).y(topPadding + (padding/2))
                .click((e) => { 
                    this.sendMove(color.id);
                } 
            );
            i++;
        });
    }

    // Checks if your turn
    isYourTurn() {
        return this.userTurn == this.username;
    }

    // Updates input blocks according to turn
    updateInputBlocks() {
        if (!this.isYourTurn()) {
            $("#game .inputBlocks rect").addClass("disabled");
        } else {
            $("#game .inputBlocks rect").removeClass("disabled");
            $("#inputBlock_" + this.user1Color + ", #inputBlock_" + this.user2Color ).addClass("disabled");
        }
    }

    // Updates turn ui
    updateTurn() {
        if (this.isYourTurn()) {
            $("#game .currentTurn").html("Your Turn");
        } else {
            $("#game .currentTurn").html(this.userTurn + "'s Turn");
        }
    }

    // Change background of current turn
    changeTurnBackground() {
        var backgroundColor;
        if (this.userTurn == this.user1) {
            backgroundColor = this.getColorValue(this.user1Color);
        } else {
            backgroundColor = this.getColorValue(this.user2Color);
        }
        $("#game .currentTurn").css("background-color", backgroundColor);
    }

    // Sends request for board
    getBoard() {
        this.MyXHR('get', { method: "getBoard", a: "game", data: "" }, this).done((json) => {
            if (json.blocks != null) {
                if (json.blocks.length != json.width * json.height) {
                    // Board not loaded yet on server
                    setTimeout(() => this.getBoard(), 100);
                    return;
                } else {
                    if (this.gameId != json.id) {
                        this.reset();
                        this.gameId = json.id;
                    }
                    this.displayBoard(json);
                    if (this.userWon != null && json.gameUserWon == null) {
                        this.userWon = null;
                        $("#gameOver").hide();
                    } else if (json.gameUserWon != null) {
                        this.displayWinner(json);
                    }
                }
            } else {
                if (json != "") {
                    Snackbar.show({
                        pos: "bottom-center",
                        text: json,
                        showAction: true,
                        duration: 5000
                    });
                }
            }
            
            if (!this.stopRequesting) {
                setTimeout(() => this.getBoard(), this.boardRefresh);
            }
        });
    }

    // Displays board and sets data
    displayBoard(data) {
        if (this.blocks.length == 0) {
            // Game data
            this.user1 = data.gameUser1;
            $("#game .user1Info .name").html(this.user1);
            this.user1Score = data.gameUser1Score;
            $("#game .user1Info .score").html("Score: " + this.user1Score);

            this.user2 = data.gameUser2;
            $("#game .user2Info .name").html(this.user2);
            this.user2Score = data.gameUser2Score;
            $("#game .user2Info .score").html("Score: " + this.user2Score);
            
            // User turn
            this.userTurn = data.gameUserTurn;
            this.updateTurn();

            // Input blocks
            this.user1Color = data.gameUser1Color;
            this.user2Color = data.gameUser2Color;
            this.updateInputBlocks();

            // Turn background
            this.changeTurnBackground();
            
            // Block stuff
            this.height = data.height;
            this.width = data.width;
            for (var y = 0; y < this.height; y++) {
                this.blocks[y] = [];
            }

            for (var i = 0; i < data.blocks.length; i++) {
                var block = data.blocks[i];
                this.blocks[block.y][block.x] = block;
            }
            this.displayBlocks();
            this.displayBlockSides();
        } else {
            // Game data
            if (this.user1Score != data.gameUser1Score) {
                this.user1Score = data.gameUser1Score;
                $("#game .user1Info .score").html("Score: " + this.user1Score);
            }
            if (this.user2Score != data.gameUser2Score) {
                this.user2Score = data.gameUser2Score;
                $("#game .user2Info .score").html("Score: " + this.user2Score);
            }

            var inputChange = false;
            // Game user
            if (this.userTurn != data.gameUserTurn) {
                this.userTurn = data.gameUserTurn;
                this.updateTurn();
                inputChange = true;
            }

            if (this.user1Color != data.gameUser1Color) {
                this.user1Color = data.gameUser1Color;
                inputChange = true;
            }

            if (this.user2Color != data.gameUser2Color) {
                this.user2Color = data.gameUser2Color;
                inputChange = true;
            }

            // Input blocks
            if (inputChange) {
                this.updateInputBlocks();
                this.changeTurnBackground();
            }

            // Block stuff
            var changeInBlockOwner = false;
            for (var i = 0; i < data.blocks.length; i++) {
                var block = data.blocks[i];
                var localBlock = this.blocks[block.y][block.x];
                if (block.colorValue != localBlock.colorValue) {
                    this.blocks[block.y][block.x].colorValue = block.colorValue;
                    SVG.get("#block_"+ block.id).fill(block.colorValue);
                }
                if (block.gameUser != localBlock.gameUser) {
                    this.blocks[block.y][block.x].gameUser = block.gameUser;
                    changeInBlockOwner = true;
                }
            }
            if (changeInBlockOwner) {
                this.displayBlockSides();
            }

            
            $("#game .user1Info").css("background-color", this.getColorValue(this.user1Color));
            $("#game .user2Info").css("background-color", this.getColorValue(this.user2Color));
        }


        
    }

    // Displays all blocks
    displayBlocks() {
        var divHeight = $("#game .gameBlocks").height();
        var divWidth = $("#game .gameBlocks").width();
        var leftPadding = 0;
        var topPadding = 0;
        // Determine length of side of block
        var blockSide =  divWidth/this.width;
        if (divHeight/this.height < blockSide) {
            blockSide = divHeight/this.height;
            // centering horizontally
            leftPadding = (divWidth - (this.width * blockSide)) / 2;
        } else {
            // centering vertically
            topPadding = (divHeight - (this.height * blockSide)) / 2;
        }
        this.blockSide = blockSide;
        
        for (var y = 0; y < this.height; y++) {
            for (var x = 0; x < this.width; x++) {
                var block = this.blocks[y][x];
                this.svgGameBlocks.rect(blockSide, blockSide).attr({ fill: block.colorValue, id: "block_" + block.id })
                    .x(leftPadding + block.x*blockSide).y(topPadding + block.y*blockSide);
            }
        }
    }

    // Displays block borders
    displayBlockSides() {
        // Make old borders invisible
        $("#game .gameBlocks rect").css({ "stroke-opacity": "0" });

        for (var y = 0; y < this.height; y++) {
            for (var x = 0; x < this.width; x++) {
                var block = this.blocks[y][x];
                if (block.gameUser != "") {
                    var borderString = "";
                    // Top
                    if (this.outOfBounds(x, y-1) || this.blocks[y-1][x].gameUser != block.gameUser) {
                        borderString += "t";
                    } 
                    // Right
                    if (this.outOfBounds(x+1, y) || this.blocks[y][x+1].gameUser != block.gameUser) {
                        borderString += "r";
                    } 
                    // Bottom
                    if (this.outOfBounds(x, y+1) || this.blocks[y+1][x].gameUser != block.gameUser) {
                        borderString += "b";
                    }
                    // Left
                    if (this.outOfBounds(x-1, y) || this.blocks[y][x-1].gameUser != block.gameUser) {
                        borderString += "l";
                    } 
                    var isCurrentUser = false;
                    if (block.gameUser == this.username) {
                        isCurrentUser = true;
                    } 
                    if (borderString != "") {
                        this.drawBorder(borderString, block.id, isCurrentUser);
                    }
                }
            }
        }
    }


    // Draws border on block
    drawBorder(string, id, isCurrentUser) {
        // t: top
        // r: right
        // b: bottom
        // l: left
        var css;
        var s = this.blockSide;
        switch (string) {
            case "trbl":
                css = "";
                break;
            case "trl":
                css = s*2+","+s+","+s;
                break;
            case "tl":
                css = s+","+s*2+","+s;
                break;
            case "tr":
                css = s*2+","+s*2;
                break;
            case "t":
                css = s+","+s*3;
                break;
            case "tbl":
                css = s+","+s+","+s*2;
                break;
            case "bl":
                css = "0,"+s*2+","+s*2;
                break;
            case "l":
                css = "0,"+s*3+","+s;
                break;
            case "rbl":
                css = "0,"+s+","+s*3;
                break;
            case "rb":
                css = "0,"+s+","+s*2+","+s;
                break;
            case "b":
                css = "0,"+s*2+","+s+","+s;
                break;
            case "trb":
                css = s*3+","+s;
                break;
            case "r":
                css = "0,"+s+","+s+","+s*2;
                break;
            case "tb":
                css = s+","+s+","+s+","+s;
                break;
            case "rl":
                css = "0,"+s+","+s+","+s+","+s;
                break;
            default:
                break;
        }
        var strokeColor = "#e3e4e6";
        $("#block_" + id).css({
            "stroke-opacity": "0.85",
            "stroke-dasharray": css,
            "stroke": strokeColor,
            "stroke-width": "1.8"
        });
        SVG.get("#block_" + id).front();
        
    }

    // Check if out of bounds
    outOfBounds(x, y) {
        if (x < 0 || x >= this.width) {
            return true;
        }   
        if (y < 0 || y >= this.height) {
            return true;
        }
        return false;
    }
    
    // Sends move to server
    sendMove(colorId) {
        $(".loadingMove").css("display", "flex");
        this.MyXHR('get', { method: "makeMove", a: "game", data: colorId }, this).done((json) => {
            $(".loadingMove").css("display", "none");
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

    // Displays winner ui and sets data
    displayWinner(data) {
        this.userWon = data.gameUserWon;
        $("#gameOver").show();
        if (this.userWon == this.username) {
            $("#gameOver .result").html("You Won");
        } else if (this.userWon == "tie") {
            $("#gameOver .result").html("Tied");
        } else {
            $("#gameOver .result").html("You Lost");
        }
        if (this.username == this.user1) {
            $("#gameOver .user1").html("Your final score: " + this.user1Score);
            $("#gameOver .user2").html(this.user2 + "'s final score: " + this.user2Score);
        } else {
            $("#gameOver .user1").html("Your final score: " + this.user2Score);
            $("#gameOver .user2").html(this.user1 + "'s final score: " + this.user1Score);
        }
        $("#gameOver button").unbind("click").click(() => {
            this.newGame();
        });
    }
    
    // Sends request for new game
    newGame() {
        this.MyXHR('get', { method: "newGame", a: "game", data: "" }, this).done((json) => {
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

    // Get color from id
    getColorValue(id) {
        for (var i = 0; i < this.colors.length; i++) {
           var color = this.colors[i];
           if (color.id == id) {
               return color.colorValue;
           }
        }
        return null;
    }

    // Resize svg elements
    resize() {
        // Input blocks
        this.svgInputBlocks.clear();
        this.displayInputBlocks();
        this.updateInputBlocks();

        // Game blocks
        this.svgGameBlocks.clear();
        this.displayBlocks();
        this.displayBlockSides();
    }
}