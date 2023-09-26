<?php
$directoryName = "uploads";

if (!is_dir($directoryName)) {
    if (mkdir($directoryName)) {
		//created_successfully
        //echo "Directory '$directoryName' created successfully.";
    } else {
		//failed_to_create
        echo "Failed to create directory '$directoryName'.";
    }
} else {
    //already_exists;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define the target directory where the uploaded file will be stored
    $uploadDir = 'uploads/';

    // Get the uploaded file's information
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $fileError = $_FILES['file']['error'];

    // Check if the file was uploaded without errors
    if ($fileError === UPLOAD_ERR_OK) {
    // Generate a CRC32 checksum for the uploaded file
    $crc32 = hash_file('crc32b', $fileTmpName);
	$fileParts = pathinfo($fileName);

    // Check if the file with the same name already exists
	$newFileName = $fileParts['filename'] . '_' . $crc32 . '.' . $fileParts['extension'];
    $changedName = false;
    while (file_exists($uploadDir . $newFileName)) {
		// If a file with the same name exists, append a dateTime to the filename
		$newFileName = $fileParts['filename'] . '_' . date("Ymd_His") . '_' . $crc32 . '.' . $fileParts['extension'];
		$changedName = true;
    }


    // Move the uploaded file to the target directory with the new name
    $targetFilePath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpName, $targetFilePath)) {
            $response = array('success' => true, 'crc32' => $crc32,  'changedName' =>  $changedName, 'newFileName' => $newFileName);
        } else {
            $response = array('success' => false);
        }
    } else {
        $response = array('success' => false, 'error' => $fileError);
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Display the HTML form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Upload with CRC Control</title>
        <style>
            body {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #000;
                color: #FFF;
                font-size: 22px;
            }

            .container {
                text-align: center;
            }

            h2 {
                font-size: 30px;
            }

            .message {
                margin-top: 20px;
            }

			#progress {
				width: 100%;
				max-width: 400px;
				background-color: #ddd;
				height: 30px;
				position: relative;
				margin: 0 auto; /* Add this line to center the progress bar horizontally */
			}

            #bar {
                width: 0;
                height: 100%;
                background-color: #4c88af;
            }
        </style>
    </head>
    <body>
    <div class="container">
        <h2>Upload a file with CRC Control</h2>
        <form id="uploadForm" enctype="multipart/form-data" method="post">
            <input type="file" id="fileInput" name="file" required>
            <button type="submit" id="uploadButton" name="submit">Upload</button>
        </form>
        <div class="message"></div>
        <div id="progress">
            <div id="bar"></div>
        </div>
    </div>

    <script>
        const uploadForm = document.getElementById("uploadForm");
        const fileInput = document.getElementById("fileInput");
        const uploadButton = document.getElementById("uploadButton");
        const progressBar = document.getElementById("bar");

        uploadForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(uploadForm);
            const xhr = new XMLHttpRequest();

            xhr.open("POST", "", true);

            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + "%";
                }
            };

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const messageDiv = document.querySelector(".message");
                        messageDiv.innerHTML = `File uploaded successfully. CRC32: ${response.crc32}`;             
                    } else {
                        alert("Error uploading file.");
                    }
                }
            };

            xhr.send(formData);
        });
    </script>
    </body>
    </html>
    <?php
}
?>
