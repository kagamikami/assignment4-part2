<?php   
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
    $dbhost = 'oniddb.cws.oregonstate.edu';
	$user = 'wangyizh-db';
	$pass = 'Y2gbJk1gAcV3AWNF';
	$db = 'wangyizh-db';
	$table = 'video';
	$ncategory = 'all movies';

	$mysqli = new mysqli($dbhost, $user, $pass,$db) or die ("连接失败");
	if ($mysqli->connect_errno) {
    	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}

	if (!$mysqli->query("DROP TABLE IF EXISTS test") || !$mysqli->query("CREATE TABLE test(id INT)")) {
   		echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
	}
	//Delete all videos
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteAll'])) {
    	if (mysqli_num_rows($mysqli->query("SELECT name FROM $table"))) {
        	$mysqli->query("TRUNCATE TABLE $table");
        	$mysqli->query("ALTER TABLE $table AUTO_INCREMENT = 1"); 
    	}
	}
	//filter videos
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Filtervideos'])) {
    	$cate = $_POST['menu'];
    	global $ncategory; 
    	$ncategory = $cate;
    	//displayTable($mysqli, $table, $cate);
	}

	//Delete video
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Delete'])) {
		$video = $_POST['Delete'];
		$mysqli->query("DELETE FROM $table WHERE name = '$video'");
	}

	//Check in/out video
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Check'])) {
		$video = $_POST['Check'];
    
    if (mysqli_num_rows($mysqli->query("SELECT name FROM $table WHERE name = '$video' AND rented = 0"))){ // available => not checked out
        $mysqli->query("UPDATE $table SET rented = 1 WHERE name = '$video'");
    }else{ // not available => checked out
        $mysqli->query("UPDATE $table SET rented = 0 WHERE name = '$video'");
    }
	}
	//add video
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    	$pass = true;
    	if ($_POST['video'] == '' || $_POST['video'] == null){
    		echo '<p> Name can not be empty</p>';
    		$pass = false;
    	}                          // name test
    	$video = $_POST['video'];
    	if ($_POST['length'] != ''){  // length test
    	if (!(is_numeric($_POST['length']) && ($_POST['length'] == (int)$_POST['length']))){
    		echo 'Length should be a interger';
    		$pass = false;
    	}else if ((int)$_POST['length'] < 0){
    		echo 'Length should be positive';
    		$pass = false;
    	}}
    	$length = $_POST['length'];
    	$category = $_POST['category'];
    	if ($pass == true){
    		if ($category == ''){    // category empty
    			if ($length == ''){   // and length empty
    				$sql = "INSERT INTO video (name)
					VALUES ('$video')";
				if ($mysqli->query($sql) === TRUE) {
    				echo "New record created successfully";
				} else {
    				echo "Error: " . $sql . "<br>" . $mysqli->error;
    			}}else{         //only category empty
    				$sql = "INSERT INTO video (name, length)
					VALUES ('$video',  '$length')";
					if ($mysqli->query($sql) === TRUE) {
    					echo "New record created successfully";
					}else {
    					echo "Error: " . $sql . "<br>" . $mysqli->error;
    				}
    		}}else if ($length == ''){   // only length empty
    			$sql = "INSERT INTO video (category, name)
					VALUES ('$category', '$video')";
				if ($mysqli->query($sql) === TRUE) {
    				echo "New record created successfully";
				}else {
    				echo "Error: " . $sql . "<br>" . $mysqli->error;
    			} 
    		}else{                          // nothing empty
				$sql = "INSERT INTO video (category, name, length)
					VALUES ('$category','$video','$length')";
				if ($mysqli->query($sql) === TRUE) {
    				echo "New record created successfully";
				} else {
    				echo "Error: " . $sql . "<br>" . $mysqli->error;
				}	
			}
		}
		//displayTable($mysqli, $table, $ncategory);
	}
	function displayTable(&$mysqli, &$table, $filterCate) {
    if (!mysqli_num_rows($mysqli->query("SELECT id FROM $table"))) {
        echo '<p>No videos to display...</p>';
        return;
    }
    
    $stmt = NULL;
    if ($filterCate == NULL || $filterCate == 'all movies')
        $stmt = $mysqli->prepare("SELECT name, category, length, rented FROM $table ORDER BY category, name");
    else { // filter by category
        if (!mysqli_num_rows($mysqli->query("SELECT id FROM $table WHERE category = '$filterCate'"))) {
            echo "<p>'$filterCate' Videos of the selected categories do not exist. No videos to display...</p>";
            return;
        }
        $stmt = $mysqli->prepare("SELECT name, category, length, rented FROM $table WHERE category = '$filterCate' ORDER BY category, name");
    }
        
    if (!$stmt->execute()) {
        echo 'Query failed: (' . $mysqli->errno . ') ' . $mysqli->error . '<br>';
        return;
    }
    
    $video = NULL;
    $category = NULL;
    $length = NULL;
    $rented = NULL;
    
    if (!$stmt->bind_result($video, $category, $length, $rented)) {
        echo 'Binding output parameters failed: (' . $stmt->errno . ') ' . $stmt->error . '<br>';
        return;
    }
    
    echo '<table border="2" <tr><td bgcolor="Grey"><font color="Black"><b>Name</b></font>
            </td><td bgcolor="Grey"><b>Category</b></td>
            <td bgcolor="Grey"><b>Length</b></td><td bgcolor="Grey"><b>Rented</b></td>
            <td bgcolor="Grey"><b>Delete</b></td><td bgcolor="Grey"><b>Status</b></td></tr>';
    
    	while ($stmt->fetch()) {
        	echo "<tr><td>$video</td><td>$category</td>
           		 <td>$length</td>";
        	if ($rented)
            	echo "<td>checked out</td>";
        	else
            	echo "<td>available</td>";
           
        	//$video = str_replace(' ', '_', $video);
        	echo "<td><form action='index.php' method='post'>
                    <button name='Delete' value='$video'>Delete</button>
                	</form></td>
                	<td><form action='index.php' method='post'>
                    <button name='Check' value='$video'>Check In/Out</button>
                	</form></td>
            		</tr>";
    	}
    	echo '</table><br>';
	}
	function displayMenu(&$mysqli, &$table) {
    	if (!mysqli_num_rows($mysqli->query("SELECT name FROM $table"))) {
        return;
    	}
    
    	$stmt = $mysqli->prepare("SELECT category FROM $table GROUP BY category ORDER BY category");
    	if (!$stmt->execute()) {
        	echo 'Query failed: (' . $mysqli->errno . ') ' . $mysqli->error . '<br>';
        	return;
    	}
    
    	$category = NULL;

    	if (!$stmt->bind_result($category)) {
        	echo 'Binding output parameters failed: (' . $stmt->errno . ') ' . $stmt->error . '<br>';
        	return;
   		 }
    
    	echo '&nbsp&nbsp<select name="menu">';
    	echo "<option value='all movies'>All Movies</option>";
    	while ($stmt->fetch()){
    		if ($category != ''){
        	echo "<option value='$category'>$category</option>";
        }
        }
    	echo '</select>';
   		echo " <button name='Filtervideos' value='filtervideos'>Filter videos</button>";
   		    	global $ncategory;
    	displayTable($mysqli, $table, $ncategory);
	}
		
		

		echo '<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="utf-8" />
			<title>index</title>
		</head>
		<body>';
		
		
		//echo '<div>'
		echo '<form method = "post" action = "index.php"> ';
		echo '<p>video name  ';
		echo '<input type="text" name = "video" ></p>';
		echo '<p>category  ';
		echo '<input type="text" name = "category" ></p>';
		echo '<p>length       ';
		echo '<input type = "text" name = "length" ></p>';
		echo '<input  name = "submit"  type = "submit" value= "submit"></input>';
		echo '<button name= "deleteAll" value = "delete all videos" >Delete all videos</button>';

		
		displayMenu($mysqli,$table);
		echo '</form>';
		echo '</body>
			  </html>';
		
		//echo '</div>'

	?>