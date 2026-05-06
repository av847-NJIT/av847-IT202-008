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

// View fighter from DB
$db = getDB();
$fighter = [];
try {
    $stmt = $db->prepare("SELECT * FROM `IT202-S26-FighterStats` WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $r = $stmt->fetch();
    if ($r) {
        $fighter = $r;
    } else {
        flash("Fighter not found", "danger");
        die(header("Location: " . get_url("admin/list_fighters.php")));
    }
} catch (PDOException $e) {
    error_log("Error fetching fighter: " . var_export($e, true));
    flash("Error loading fighter", "danger");
    die(header("Location: " . get_url("admin/list_fighters.php")));
}
?>

<div class="container-fluid">
    <h3>Fighter Details</h3>
    <table class="table table-bordered w-50">
        <tbody>
            <tr>
                <th>Name</th>
                <td><?php echo $fighter["name"] ?? "N/A"; ?></td>
            </tr>
            <tr>
                <th>Striking Accuracy</th>
                <td><?php echo $fighter["striking_accuracy"] ?? "N/A"; ?>%</td>
            </tr>
            <tr>
                <th>Takedown Accuracy</th>
                <td><?php echo $fighter["takedown_accuracy"] ?? "N/A"; ?>%</td>
            </tr>
            <tr>
                <th>Sig. Strikes Landed</th>
                <td><?php echo $fighter["significant_strikes_landed"] ?? "N/A"; ?></td>
            </tr>
            <tr>
                <th>Sig. Strikes Defense</th>
                <td><?php echo $fighter["significant_strikes_defense"] ?? "N/A"; ?>%</td>
            </tr>
            <tr>
                <th>Takedown Defense</th>
                <td><?php echo $fighter["takedown_defense"] ?? "N/A"; ?>%</td>
            </tr>
            <tr>
                <th>Source</th>
                <td><?php echo $fighter["is_api"] ? "API" : "Manual"; ?></td>
            </tr>
            <tr>
                <th>API ID</th>
                <td><?php echo $fighter["api_id"] ?? "N/A"; ?></td>
            </tr>
            <tr>
                <th>Created</th>
                <td><?php echo $fighter["created"] ?? "N/A"; ?></td>
            </tr>
            <tr>
                <th>Modified</th>
                <td><?php echo $fighter["modified"] ?? "N/A"; ?></td>
            </tr>
        </tbody>
    </table>
    <a href="<?php echo get_url('admin/edit_fighter.php'); ?>?id=<?php echo $fighter["id"]; ?>" class="btn btn-primary">Edit</a>
    <a href="<?php echo get_url('admin/list_fighters.php'); ?>" class="btn btn-secondary">Back to List</a>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>