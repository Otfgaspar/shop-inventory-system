<?php
require __DIR__ . '/../src/auth.php';
session_destroy();
header('Location: login.php');