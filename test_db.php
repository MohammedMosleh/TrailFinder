<?php
include "includes/db_connect.php";

if ($conn) {
    echo "<h1>Database connected successfully!</h1>";
} else {
    echo "<h1>Database connection failed.</h1>";
}
?>
