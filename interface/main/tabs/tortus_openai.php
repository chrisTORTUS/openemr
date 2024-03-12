<?php
require __DIR__ . "/../../../vendor/autoload.php";

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../../");
    $dotenv->load();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
// echo 'hello :' .$_ENV['OPENAI_API_KEY'];
?>
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
        // var xhr = new XMLHttpRequest();
        // xhr.open("GET", "getNote.php", true);
        // xhr.onreadystatechange = function () {
        //     //console.log(data);
        //     if (xhr.readyState == 4 && xhr.status == 200) {
        //         //console.log(xhr.responseText);
        //         var data = JSON.parse(xhr.responseText);
        //         // Display the API response in the chat interface
        //         //chatBox.innerHTML += "<p><strong>Reply:</strong> " + JSON.stringify(data, null, 2) + "</p>";
        //         // append patient_list_info to messages
        //         messages.push({
        //             "role": "user",
        //             "content": "This is a stringified JSON with all the patient info including uuid and name: \n" + xhr.responseText
        //         });
        //     }
        // }
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "get_patients.php", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                console.log(xhr.responseText);
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

        // var tools = [{
        //     "type": "function",
        //     "function": {
        //         "name": "get_uuid_from_name",
        //         "description": "Provided with the name of a patient and a JSON of many patient's information, return the uuid of the target patient",
        //         "parameters": {
        //         "type": "object",
        //         "properties": {
        //             "uuid": {
        //             "type": "string",
        //             "description": "uuid of target patient"
        //             }
        //         },
        //         "required": [
        //             "uuid"
        //         ]
        //         }
        //     }
        //     }];

        var tools = [{
        "type": "function",
        "function": {
            "name": "get_uuid_and_pid_from_name",
            "description": "Provided with the name of a patient and a JSON of many patient's information, return the uuid and pid of the target patient",
            "parameters": {
            "type": "object",
            "properties": {
                "uuid": {
                "type": "string",
                "description": "uuid of target patient"
                },
                "pid": {
                "type": "string",
                "description": "pid of target patient"
                }
            },
            "required": [
                "uuid", "pid"
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

        function soaps_from_uuid(uuid, pid) {
            // make API request to get all the encounters for a patient
            var encounters = {};
            var_encounters_ls = [];
            var xhr_soap = new XMLHttpRequest();
            xhr_soap.open("GET", "get_encounters.php?uuid=" + uuid, false);
            xhr_soap.onreadystatechange = function () {
                if (xhr_soap.readyState == 4 && xhr_soap.status == 200) {
                    console.log(xhr_soap.responseText);
                    encounters = JSON.parse(xhr_soap.responseText);
                    encounters_ls = encounters.data
                    console.log("len encounters: " + encounters_ls.length);
                    console.log("encounters: " + encounters);
                    console.log("encounters eid: " + encounters.data[0]['eid']);
                }
            }
            xhr_soap.send();

            // make API request to get all the soap notes for a patient
            var soaps = [];
            console.log("before soap loop");
            for (var i = 0; i < 1; i++) {
                console.log("inside soap loop");
                var xhr_soap_note = new XMLHttpRequest();
                var uri = "get_soap.php?pid=" + pid + "&eid=" + encounters.data[i]['eid'];
                xhr_soap_note.open("GET", uri, true);
                xhr_soap_note.onreadystatechange = function () {
                    if (xhr_soap_note.readyState == 4 && xhr_soap_note.status == 200) {
                        console.log("this is a soap: " + xhr_soap_note.responseText);
                        var soap = JSON.parse(xhr_soap_note.responseText);
                        soaps.push(soap);
                    }
                }
                xhr_soap_note.send();
            }
            console.log("soaps: " + soaps);
        }

        function submitMessage() {
            var apiKey = "<?php echo $_ENV['OPENAI_API_KEY']; ?>";

            var message = document.getElementById("message").value;
            var chatBox = document.getElementById("chat-box");
            chatBox.innerHTML += "<p><strong>You:</strong> " + message + "</p>";
            document.getElementById("message").value = "";

            // Add the new user message to the messages array
            messages.push({
                "role": "user",
                "content": message,
            });

            // Make the API request
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "https://api.openai.com/v1/chat/completions", true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("Authorization", "Bearer " + apiKey);
            var data = {
                "messages": messages, // Send the entire messages array
                "model": "gpt-3.5-turbo-0125",
                "tools": tools,
                // "tool_choice": {"type": "function", "function": {"name": "get_uuid_from_name"}}
            };
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var data = JSON.parse(xhr.responseText);
                    console.log(data);
                    // if a function call was made
                    if (data.choices[0].message.tool_calls) {
                        tool_choice = data.choices[0].message.tool_calls[0].function.name;
                        console.log("tool choice: " + tool_choice);
                        if (tool_choice == "get_uuid_and_pid_from_name") {
                            // parse the uuid from the function call response
                            var uuid_serialised = data.choices[0].message.tool_calls[0].function['arguments'];
                            var uuid_json = JSON.parse(uuid_serialised);
                            var uuid = uuid_json.uuid;

                            var pid_json = JSON.parse(uuid_serialised);
                            var pid = pid_json.pid;
                            console.log("uuid:" + uuid);
                            console.log("pid:" + pid);
                            soaps_from_uuid(uuid, pid);
                        }
                        else {
                            console.log("tool choice not recognised");
                        }
                    }
                    // if no function call was made
                    else {
                        // console.log("message" + myFunction());
                        // Display the API response in the chat interface
                        chatBox.innerHTML += "<p><strong>Reply:</strong> " + data.choices[0].message.content.trim() + "</p>";

                        // Add the assistant's response to the messages array
                        messages.push({
                        "role": "assistant",
                        "content": data.choices[0].message.content.trim()
                    });
                    }
                }
            }
            xhr.send(JSON.stringify(data));

            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>