<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;

if ($id <= 0) {
    flash("Invalid fighter ID", "danger");
    die(header("Location: " . get_url("admin/list_fighters.php")));
}

// Deletes fighter from DB
$db = getDB();
try {
    $stmt = $db->prepare("DELETE FROM `IT202-S26-FighterStats` WHERE id = :id");
    $stmt->execute([":id" => $id]);
    if ($stmt->rowCount() > 0) {
        flash("Fighter deleted successfully", "success");
    } else {
        flash("Fighter not found", "warning");
    }
} catch (PDOException $e) {
    error_log("Error deleting fighter: " . var_export($e, true));
    flash("Error deleting fighter", "danger");
}

die(header("Location: " . get_url("admin/list_fighters.php")));
