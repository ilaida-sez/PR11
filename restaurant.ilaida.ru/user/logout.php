<?php
session_start();

// Удаляем все данные сессии пользователя
unset($_SESSION['user_logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_phone']);

// Перенаправляем на главную страницу
header('Location: ../index.php');
exit;
?>