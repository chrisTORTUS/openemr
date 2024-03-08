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
        <div id="chat-header">Tortus EHR</div>
        <div id="chat-box"></div>
        <div id="chat-input">
            <input type="text" id="message" placeholder="Type a message..." />
            <button onclick="submitMessage()">Send</button>
        </div>
    </div>

    <script>
        // Initialize the messages array
        var messages = [{
            "role": "system",
            "content": "JSON"
        }];

        var patient_list_info = "";
        // make API request to get patients list
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "getNote.php", true);
        xhr.onreadystatechange = function () {
            //console.log(data);
            if (xhr.readyState == 4 && xhr.status == 200) {
                //console.log(xhr.responseText);
                var data = JSON.parse(xhr.responseText);
                // Display the API response in the chat interface
                //chatBox.innerHTML += "<p><strong>Reply:</strong> " + JSON.stringify(data, null, 2) + "</p>";
                // append patient_list_info to messages
                messages.push({
                    "role": "user",
                    "content": "This is a stringified JSON with all the patient info including uuid and name: \n" + xhr.responseText
                });
            }
        }
        
        xhr.send();

        var tools = [{
            "type": "function",
            "function": {
                "name": "get_uuid_from_name",
                "description": "Provided with the name of a patient and a JSON of many patient's information, return the uuid of the target patient",
                "parameters": {
                "type": "object",
                "properties": {
                    "uuid": {
                    "type": "string",
                    "description": "uuid of target patient"
                    }
                },
                "required": [
                    "uuid"
                ]
                }
            }
            }];

        document.getElementById("message").addEventListener("keypress", function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevents the default action
                submitMessage();
            }
        });

        function submitMessage() {
            var message = document.getElementById("message").value;
            var chatBox = document.getElementById("chat-box");
            chatBox.innerHTML += "<p><strong>You:</strong> " + message + "</p>";
            document.getElementById("message").value = "";

            // Add the new user message to the messages array
            messages.push({
                "role": "user",
                "content": message,
            });
            console.log(messages);
            // Make the API request
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "https://api.openai.com/v1/chat/completions", true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("Authorization", "Bearer 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");
            var data = {
                "messages": messages, // Send the entire messages array
                "model": "gpt-3.5-turbo-0125",
                "tools": tools,
                "tool_choice": {"type": "function", "function": {"name": "get_uuid_from_name"}}
            };
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var data = JSON.parse(xhr.responseText);
                    console.log(data);
                    // Display the API response in the chat interface
                    chatBox.innerHTML += "<p><strong>Reply:</strong> " + data.choices[0].message.content.trim() + "</p>";

                    // Add the assistant's response to the messages array
                    messages.push({
                        "role": "assistant",
                        "content": data.choices[0].message.content.trim()
                    });
                }
            }
            xhr.send(JSON.stringify(data));

            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>