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
			$uploadedFileCRC32 = hash_file('crc32b', $targetFilePath); // Calculate the CRC32 checksum of the uploaded file
			// Check if the calculated CRC32 matches the client's CRC32
			if ($uploadedFileCRC32 === $crc32) {
				$response = array('success' => true, 'crc32' => $uploadedFileCRC32, 'changedName' => $changedName, 'newFileName' => $newFileName);
			} else {
				// CRC32 check failed
				$response = array('success' => false, 'error' => 'CRC32 check failed');
				// Optionally, you can delete the uploaded file here to prevent storing an invalid file.
				unlink($targetFilePath);
			}
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
				margin: 0 auto;
			}

			#bar {
				width: 0;
				height: 100%;
				background-color: #4c88af;
			}

			.limits {
				position: fixed;
				top: 0;
				left: 0;
				font-size: 12px;
			}

			.bold {
				font-weight: bold;
				color: #FACFAC;
			}
		</style>
	</head>

	<body>
		<div class="limits">
			<?php
			$max_upload = ini_get('upload_max_filesize');
			$max_post = ini_get('post_max_size');
			$memory_limit = ini_get('memory_limit');
			echo "max_upload: <span class='bold'>{$max_upload}</span><br>max_post: <span class='bold'>{$max_post}</span><br>memory_limit: <span class='bold'>{$memory_limit}</span><br>";

			?>
		</div>
		<div class="container">
			<h2>Upload a file with CRC Control</h2>
			<form id="uploadForm" enctype="multipart/form-data" method="post">
				<input type="file" id="fileInput" name="file" required>
				<button type="submit" id="uploadButton" name="submit">Upload</button>
			</form>
			<div class="message"></div>
			<div id="progress">
				<div id="bar"></div>
				<div id="progressText">0%</div>
				<div id="progressCRC"></div>
			</div>
		</div>

		<script>
			const uploadForm = document.getElementById("uploadForm");
			const fileInput = document.getElementById("fileInput");
			const uploadButton = document.getElementById("uploadButton");
			const progressBar = document.getElementById("bar");
			const progressText = document.getElementById("progressText");

			uploadForm.addEventListener("submit", function (e) {
				e.preventDefault();
				const formData = new FormData(uploadForm);
				const xhr = new XMLHttpRequest();

				xhr.open("POST", "", true);

				xhr.upload.onprogress = function (e) {
					if (e.lengthComputable) {
						const percentComplete = (e.loaded / e.total) * 100;
						const currentSize = formatBytes(e.loaded);
						const totalSize = formatBytes(e.total);

						progressBar.style.width = percentComplete + "%";
						progressText.innerText = `${percentComplete.toFixed(2)}% (${currentSize} of ${totalSize})`;
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
			// Function to format bytes into a human-readable format (KB, MB, GB, etc.)
			function formatBytes(bytes, decimals = 2) {
				if (bytes === 0) return '0 B';

				const k = 1024;
				const dm = decimals < 0 ? 0 : decimals;
				const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
				const i = Math.floor(Math.log(bytes) / Math.log(k));

				return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
			}
		</script>
	</body>

	</html>
	<?php
}
?>
