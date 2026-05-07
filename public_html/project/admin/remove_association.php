<?php
session_start();
require(__DIR__ . "/../../../lib/functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission", "warning");
    header("Location: " . get_url("landing.php"));
    exit();
}

$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;

if ($id <= 0) {
    flash("Invalid association ID", "danger");
    header("Location: " . get_url("admin/list_all_favorites.php"));
    exit();
}

$db = getDB();
try {
    $stmt = $db->prepare("DELETE FROM `IT202-S26-UserFavorites` WHERE id = :id");
    $stmt->execute([":id" => $id]);
    if ($stmt->rowCount() > 0) {
        flash("Association removed successfully", "success");
    } else {
        flash("Association not found", "warning");
    }
} catch (PDOException $e) {
    error_log("Error removing association: " . var_export($e, true));
    flash("An error occurred", "danger");
}

// preserve filter/sort from referer
$redirect = get_url("admin/list_all_favorites.php");
if (isset($_SERVER["HTTP_REFERER"])) {
    $query_string = parse_url($_SERVER["HTTP_REFERER"], PHP_URL_QUERY);
    if ($query_string) {
        parse_str($query_string, $params);
        unset($params["id"]);
        $filtered = http_build_query($params);
        if ($filtered) {
            $redirect = get_url("admin/list_all_favorites.php") . "?" . $filtered;
        }
    }
}

header("Location: " . $redirect);
exit();
