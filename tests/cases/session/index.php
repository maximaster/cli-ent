<?php

if (!session_start()) {
    throw new Exception("Can't start a session");
}

if (empty($_SESSION['cnt'])) {
    $_SESSION['cnt'] = 0;
}

$_SESSION['cnt']++;

echo $_SESSION['cnt'];
