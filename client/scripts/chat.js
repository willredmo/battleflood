const monthNames = ["January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"
];

const enterKeyCode = 0;

// Class that handles chat stuff
class Chat {
    constructor(main) {
        this.username = main.getUsername();
        this.MyXHR = main.MyXHR;
        this.chatRefresh = 1000;
        this.messages = new Map();
        this.totalUnread = 0;

        this.getChat();

        var chatInput = $("#chat input");

        var eventChat = this;
        // Add action listeners
        $("#chat input").keypress((e) => {
            if (e.key == "Enter") { // use key code instead
                eventChat.sendMessage(chatInput.val());
                chatInput.val("");
            }
        });

        $("#chat button").click((e) => {
            eventChat.sendMessage(chatInput.val());
            chatInput.val("");
        });  
    }

    // Resets chat data and ui
    reset() {
        this.messages = new Map();
        $('#chat .messages').empty();
        this.clearUnread();
    }   

    // Gets chat data
    getChat() {
        this.MyXHR('get', { method: "getChat", a: "chat", data: "" }, this).done((json) => {
            this.displayChat(json);
            setTimeout(() => this.getChat(), this.chatRefresh);
        });
    }

    // Sends message
    sendMessage(message) {
        var data = {
            "message": message
        }
        this.MyXHR('get', { method: "sendMessage", a: "chat", data: JSON.stringify(data) }, this).done((json) => {
            if (json != "") {
                Snackbar.show({
                    pos: "bottom-center",
                    text: json,
                    showAction: true,
                    duration: 5000
                });
            } 
            var htmlMessages = $('#chat .messages');
            htmlMessages.scrollTop(htmlMessages.prop('scrollHeight') - htmlMessages.prop('clientHeight'));
        });
    } 

    // Displays chat data 
    displayChat(data) {
        if (this.messages.size == 0) {
            for (var i = 0; i < data.length; i++) {
                var messageData = data[i];
                var message = {
                    username: messageData.username,
                    text: messageData.text,
                    timestamp: new Date(messageData.timestamp.replace(" ", "T"))
                }
                this.messages.set(messageData.id, message);

                // Display
                this.displayMessage(message);
            }
     
            var htmlMessages = $('#chat .messages');
            htmlMessages.scrollTop(htmlMessages.prop('scrollHeight') - htmlMessages.prop('clientHeight'));
        } else {
            // Check if near bottom
            var htmlMessages = $('#chat .messages');
            var moveBottom = false;
            if (htmlMessages.scrollTop() + 10 >= htmlMessages.prop('scrollHeight') - htmlMessages.prop('clientHeight')) {
                moveBottom = true;
            }

            for (var i = 0; i < data.length; i++) {
                var messageData = data[i];
                if (!this.messages.has(messageData.id)) {
                    var message = {
                        username: messageData.username,
                        text: messageData.text,
                        timestamp: new Date(messageData.timestamp.replace(" ", "T"))
                    };
                    this.messages.set(messageData.id, message);

                    // Display
                    this.displayMessage(message);
                    if (moveBottom) {
                        htmlMessages.scrollTop(htmlMessages.prop('scrollHeight') - htmlMessages.prop('clientHeight'));
                    }
                }
            }  
        }
    }

    // Displays single message
    displayMessage(message) {
        var htmlMessage = $(".templates .message").clone();
        if (this.username != "" && this.username == message.username) {
            $(htmlMessage).addClass("self");
        }
        $(htmlMessage).find(".username").html(message.username + ": ");
        $(htmlMessage).find(".text").html(message.text);
        var date = message.timestamp;
        var hours = date.getHours();
        var am_pm;
        if (hours == 0) {
            hours = 12;
        }
        if (hours > 12) {
            am_pm = "PM";
            hours -= 12;
        } else {
            am_pm = "AM";
        }
        var dateString = hours +":"+ ((date.getMinutes() < 10)?"0":"") + date.getMinutes() +" "+ am_pm +"    |    "+ monthNames[date.getMonth()] +" "+ date.getDate();
        $(htmlMessage).find(".timestamp").html(dateString);
        $("#chat .messages").append(htmlMessage);

        // Check if chat open
        if (!$(".chatSection").hasClass("showChat")) {
            this.totalUnread++;
            $("#menuIcons .chat .badge").text(this.totalUnread);
        }
    }

    clearUnread() {
        this.totalUnread = 0;
        $("#menuIcons .chat .badge").text("");
    }
}

