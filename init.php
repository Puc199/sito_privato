<?php
session_start();
require_once 'db.php';

function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>