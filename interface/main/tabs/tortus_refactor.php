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
                    encounters = JSON.parse(xhr_soap.responseText);
                    encounters_ls = encounters.data
                }
            }
            xhr_soap.send();

            // make API request to get all the soap notes for a patient
            all_soaps = "";
            for (var i = 0; i < encounters_ls.length; i++) {
                var xhr_soap_note = new XMLHttpRequest();
                var uri = "get_soap.php?pid=" + pid + "&eid=" + encounters.data[i]['eid'];
                xhr_soap_note.open("GET", uri, false);
                xhr_soap_note.onreadystatechange = function () {
                    if (xhr_soap_note.readyState == 4 && xhr_soap_note.status == 200) {
                        console.log(+ xhr_soap_note.responseText);
                        // var soap = JSON.parse(xhr_soap_note.responseText);
                        all_soaps += xhr_soap_note.responseText;
                    }
                }
                xhr_soap_note.send();
            }
            console.log("soaps: " + all_soaps);
            return all_soaps;
        }

        function call_gpt(messages) {
            var apiKey = "<?php echo $_ENV['OPENAI_API_KEY']; ?>";
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "https://api.openai.com/v1/chat/completions", false);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("Authorization", "Bearer " + apiKey);
            var data = {
                "messages": messages, // Send the entire messages array
                "model": "gpt-3.5-turbo-0125",
                "tools": tools,
                // "tool_choice": {"type": "function", "function": {"name": "get_uuid_from_name"}}
            };
            var response = "";
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    response = JSON.parse(xhr.responseText);
                }
            }
            xhr.send(JSON.stringify(data));
            return response;
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

            // Call GPT API
            gpt_response = call_gpt(messages);

            // if a function call was made
            if (gpt_response.choices[0].message.tool_calls) {
                tool_choice = gpt_response.choices[0].message.tool_calls[0].function.name;
                if (tool_choice == "get_uuid_and_pid_from_name") {
                    // parse the uuid from the function call response
                    var uuid_serialised = gpt_response.choices[0].message.tool_calls[0].function['arguments'];
                    var uuid_json = JSON.parse(uuid_serialised);
                    var uuid = uuid_json.uuid;

                    var pid_json = JSON.parse(uuid_serialised);
                    var pid = pid_json.pid;
                    soaps = soaps_from_uuid(uuid, pid);
                }
                else {
                    console.log("tool choice not recognised");
                }

                // append results to messages
                console.log("global soaps: " + soaps);
                messages.push({
                    "role": "assistant",
                    "content": soaps
                })
                console.log(messages)

                // make gpt call again with updated conversation history
                gpt_response = call_gpt(messages);
                console.log("final response: " + JSON.stringify(gpt_response));

                // Display the API response in the chat interface
                chatBox.innerHTML += "<p><strong>Reply:</strong> " + gpt_response.choices[0].message.content.trim() + "</p>";
            }
            // if no function call was made
            else {
                // console.log("message" + myFunction());
                // Display the API response in the chat interface
                chatBox.innerHTML += "<p><strong>Reply:</strong> " + gpt_response.choices[0].message.content.trim() + "</p>";

                // Add the assistant's response to the messages array
                messages.push({
                "role": "assistant",
                "content": gpt_response.choices[0].message.content.trim()
            });
            }
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>