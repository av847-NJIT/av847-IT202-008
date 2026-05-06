<?php
session_start();
require(__DIR__ . "/../../lib/functions.php");

if (!is_logged_in()) {
    flash("You must be logged in to remove favorites", "warning");
    header("Location: " . get_url("login.php"));
    exit();
}

$fighter_id = isset($_GET["fighter_id"]) ? intval($_GET["fighter_id"]) : -1;

if ($fighter_id <= 0) {
    flash("Invalid fighter ID", "danger");
    header("Location: " . get_url("list_favorites.php"));
    exit();
}

$user_id = get_user_id();
$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM `IT202-S26-UserFavorites` WHERE user_id = :user_id AND fighter_id = :fighter_id");
    $stmt->execute([":user_id" => $user_id, ":fighter_id" => $fighter_id]);
    if ($stmt->rowCount() > 0) {
        flash("Fighter removed from favorites", "success");
    } else {
        flash("Fighter was not in your favorites", "warning");
    }
} catch (PDOException $e) {
    error_log("Error removing favorite: " . var_export($e, true));
    flash("An error occurred", "danger");
}

header("Location: " . get_url("list_favorites.php"));
exit();
?>