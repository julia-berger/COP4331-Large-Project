<?php
	// Assumes the input is a JSON file in the format of {"studentID":"", "classID":""}
	
	$inData = getRequestInfo();
	
	$studentID = trimAndSanitize($inData["studentID"]);
	$classID = trimAndSanitize($inData["classID"]);
	
	// Server info for connection
	$servername = "localhost";
	$dbUName = "Group7User";
	$dbPwd = "Group7Pass";
	$dbName = "queueNA";
	
	$error_occurred = false;
	$in_use = false;
	
	// Connect to database
	$conn = new mysqli($servername, $dbUName, $dbPwd, $dbName);
	if ($conn->connect_error){
		$error_occured = true;
		returnWithError($conn->connect_error);
	}
	else{
		$stmt = $conn->stmt_init();
		if(!$stmt->prepare("SELECT studentID FROM Student WHERE studentID = ?")){
			$error_occurred = true;
			returnWithError($conn->errno());
		}
		else{
			$stmt->bind_param("i", $studentID);
			$stmt->execute();
			
			$stmt->bind_result($student);
			while($stmt->fetch()){
				$in_use = true;
			}
			if (!$in_use){
				$error_occurred = true;
				returnWithError("Invalid student id");
			}
			$stmt->close();
		}
		if(!$error_occurred){
			$stmt = $conn->stmt_init();
			if(!$stmt->prepare("SELECT ClassID FROM Class WHERE ClassID = ?")){
				$error_occurred = true;
				returnWithError($conn->errno());
			}
			else{
				$stmt->bind_param("i", $classID);
				$stmt->execute();
				
				$stmt->bind_result($class);
				while($stmt->fetch()){
					$in_use = true;
				}
				if (!$in_use){
					$error_occurred = true;
					returnWithError("Invalid class id");
				}
				$stmt->close();
			}
		}
		if (!$error_occurred){
			$stmt = $conn->stmt_init();
			if(!$stmt->prepare("INSERT INTO Registration (StudentID, ClassID) VALUES (?, ?)")){
				$error_occurred = true;
				returnWithError($conn->errno());
			}
			$stmt->bind_param("ii", $studentID, $classID);
			if (!$stmt->execute()){
				$error_occurred = true;
				returnWithError("Failed to register for class.");
			}
		}
		$conn->close();
	}
	
	if (!$error_occurred){
		returnWithError("");
	}
	
	// Removes whitespace at the front and back, and removes single quotes and semi-colons
	function trimAndSanitize($str){
		$str = trim($str);
		$str = str_replace("'", "", $str );
		$str = str_replace(";", "", $str);
		return $str;
	}
	
	function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}
	
	function sendAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	function returnWithError( $err )
	{
		$retValue = '{"error":"' . $err . '"}';
		sendAsJson( $retValue );
	}
?>