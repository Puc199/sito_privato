<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

// Funzione di escape per sicurezza (slide 51)
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>