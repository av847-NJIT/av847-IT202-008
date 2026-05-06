<?php
session_start();
require(__DIR__ . "/../../../lib/functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    header("Location: " . get_url("landing.php"));
    exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;

if ($id <= 0) {
    flash("Invalid fighter ID", "danger");
    header("Location: " . get_url("admin/list_fighters.php"));
    exit();
}

// capture referring query string to preserve filter/sort criteria
$redirect = get_url("admin/list_fighters.php");
if (isset($_SERVER["HTTP_REFERER"])) {
    $referer = $_SERVER["HTTP_REFERER"];
    $query_string = parse_url($referer, PHP_URL_QUERY);
    if ($query_string) {
        parse_str($query_string, $params);
        unset($params["id"]);
        $filtered = http_build_query($params);
        if ($filtered) {
            $redirect = get_url("admin/list_fighters.php") . "?" . $filtered;
        }
    }
}

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

header("Location: " . $redirect);
exit();
