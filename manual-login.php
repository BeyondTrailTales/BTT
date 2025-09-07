<?php
session_start();

// Set proper session variables
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION['username'] = 'admin';

echo 'Manual login successful\!<br>';
echo 'user_id: ' . $_SESSION['user_id'] . '<br>';
echo 'logged_in: ' . ($_SESSION['logged_in'] ? 'true' : 'false') . '<br>';
echo 'username: ' . $_SESSION['username'] . '<br>';
echo 'Session ID: ' . session_id() . '<br>';
echo '<br><strong>Now try the photo upload again\!</strong><br>';
echo '<a href="trips.php">Go to Trips Page</a><br>';
echo '<a href="test-photo-simple.html">Test Photo Upload</a>';
?>
EOF < /dev/null
