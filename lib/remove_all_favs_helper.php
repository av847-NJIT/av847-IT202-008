<?php
//session_start();
//require(__DIR__ . "/../lib/functions.php");

if (!is_logged_in()) {
    flash("You must be logged in", "warning");
    header("Location: " . get_url("login.php"));
    exit();
}

$user_id = get_user_id();
$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM `IT202-S26-UserFavorites` WHERE user_id = :user_id");
    $stmt->execute([":user_id" => $user_id]);
    flash("All favorites removed", "success");
} catch (PDOException $e) {
    error_log("Error removing all favorites: " . var_export($e, true));
    flash("An error occurred", "danger");
}

header("Location: " . get_url("list_favorites.php"));
exit();
