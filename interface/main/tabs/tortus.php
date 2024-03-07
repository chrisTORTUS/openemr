<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        #chat-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        #chat-header {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: center;
        }
        #chat-box {
            flex-grow: 1;
            padding: 10px;
            overflow-y: auto;
            border: 1px solid #ccc;
        }
        #chat-input {
            display: flex;
            border-top: 1px solid #ccc;
        }
        #chat-input input {
            flex-grow: 1;
            border: none;
            padding: 10px;
        }
        #chat-input button {
            background-color: #333;
            color: white;
            border: none;
            padding: 10px;
        }
        #chat-box p {
            margin: 0 0 10px;
        }
    </style>
</head>
<body>
    <div id="chat-container">
        <div id="chat-header">Chatino</div>
        <div id="chat-box"></div>
        <div id="chat-input">
            <input type="text" id="message" placeholder="Type a message..." />
            <button onclick="submitMessage()">Send</button>
        </div>
    </div>

    <script>
        function submitMessage() {
            console.log("submitMessage");
            var message = document.getElementById("message").value;
            var chatBox = document.getElementById("chat-box");
            chatBox.innerHTML += "<p><strong>You:</strong> " + message + "</p>";
            document.getElementById("message").value = "";

            // Make the API request
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "getNote.php", true);
            xhr.onreadystatechange = function () {
                console.log(data);
                if (xhr.readyState == 4 && xhr.status == 200) {
                    console.log(xhr.responseText);
                    var data = JSON.parse(xhr.responseText);
                    console.log(data);
                    // Display the API response in the chat interface
                    chatBox.innerHTML += "<p><strong>Reply:</strong> " + JSON.stringify(data, null, 2) + "</p>";
                }
            }
            xhr.send();

            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>