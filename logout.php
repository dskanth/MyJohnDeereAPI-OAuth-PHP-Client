<?php
session_start();
$_SESSION['account_name'] = '';

header("Location: index.php");