<?php
session_start();
require(__DIR__ . "/../../lib/functions.php");

if (!is_logged_in()) {
    flash("You must be logged in to add favorites", "warning");
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

// check if fighter exists
try {
    $check = $db->prepare("SELECT id FROM `IT202-S26-FighterStats` WHERE id = :fighter_id");
    $check->execute([":fighter_id" => $fighter_id]);
    if ($check->rowCount() == 0) {
        flash("Fighter not found", "danger");
        header("Location: " . get_url("list_favorites.php"));
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking fighter: " . var_export($e, true));
    flash("An error occurred", "danger");
    header("Location: " . get_url("list_favorites.php"));
    exit();
}

// check if fighter is already favorited
try {
    $check = $db->prepare("SELECT id FROM `IT202-S26-UserFavorites` WHERE user_id = :user_id AND fighter_id = :fighter_id");
    $check->execute([":user_id" => $user_id, ":fighter_id" => $fighter_id]);
    if ($check->rowCount() > 0) {
        flash("Fighter is already in your favorites", "warning");
        header("Location: " . get_url("list_favorites.php"));
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking favorite: " . var_export($e, true));
    flash("An error occurred", "danger");
    header("Location: " . get_url("list_favorites.php"));
    exit();
}

// insert favorite fighter
try {
    $stmt = $db->prepare("INSERT INTO `IT202-S26-UserFavorites` (user_id, fighter_id) VALUES (:user_id, :fighter_id)");
    $stmt->execute([":user_id" => $user_id, ":fighter_id" => $fighter_id]);
    flash("Fighter added to favorites", "success");
} catch (PDOException $e) {
    error_log("Error adding favorite: " . var_export($e, true));
    flash("An error occurred", "danger");
}

header("Location: " . get_url("list_favorites.php"));
exit();
