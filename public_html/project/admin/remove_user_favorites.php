<?php
session_start();
require(__DIR__ . "/../../../lib/functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission", "warning");
    header("Location: " . get_url("landing.php"));
    exit();
}

$username = isset($_GET["username"]) ? trim($_GET["username"]) : "";

if (empty($username)) {
    flash("Invalid username", "danger");
    header("Location: " . get_url("admin/list_all_favorites.php"));
    exit();
}

$db = getDB();
try {
    $stmt = $db->prepare("DELETE uf FROM `IT202-S26-UserFavorites` uf 
                          INNER JOIN Users u ON u.id = uf.user_id 
                          WHERE u.username LIKE :username");
    $stmt->execute([":username" => "%" . $username . "%"]);
    flash("Removed " . $stmt->rowCount() . " association(s) for users matching '" . htmlspecialchars($username) . "'", "success");
} catch (PDOException $e) {
    error_log("Error removing user favorites: " . var_export($e, true));
    flash("An error occurred", "danger");
}

header("Location: " . get_url("admin/list_all_favorites.php") . "?search=" . urlencode($username));
exit();
