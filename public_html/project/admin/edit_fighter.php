<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}
?>

<?php
$id = se($_GET, "id", -1, false);

if (isset($_POST["name"])) {
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ["name", "striking_accuracy", "takedown_accuracy", "significant_strikes_landed", "significant_strikes_defense", "takedown_defense"])) {
            unset($_POST[$k]);
        }
        $fighter = $_POST;
        error_log("Cleaned up POST: " . var_export($fighter, true));
    }

    $fighter["id"] = $id;
    try {
        $r = update("IT202-S26-FighterStats", $fighter);
        if ($r["rowCount"]) {
            flash("Updated " . $r["rowCount"] . " record(s)", "success");
        } else {
            flash("Error updating record(this can occur if no properties changed)", "warning");
        }
    } catch (PDOException $e) {
        error_log("Something broke with the query: " . var_export($e, true));
        flash("An error occurred", "danger");
    }
    catch(Exception $e){
        error_log("Something broke with the query" . var_export("$e, true"));
        flash("An error occurred: " . $e->getMessage(), "danger");
    }
}

$fighter = [];
if ($id > -1) {
    $db = getDB();
    $query = "SELECT name, striking_accuracy, takedown_accuracy, significant_strikes_landed, significant_strikes_defense, takedown_defense FROM `IT202-F26-FighterStats` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
        if ($r) {
            $fighter = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location:" . get_url("admin/list_fighters.php")));
}
?>

<div class="container-fluid">
    <h3>Edit Fighter</h3>
    <form method="POST">
        <div class="mb-3">
            <label for="name">Fighter Name</label>
            <input type="text" name="name" id="name" placeholder="Fighter Name" required value="<?php se($fighter, "name"); ?>">
        </div>
        <div class="mb-3">
            <label for="striking_accuracy">Striking Accuracy (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="striking_accuracy" id="striking_accuracy" placeholder="Striking Accuracy" required value="<?php se($fighter, "striking_accuracy"); ?>">
        </div>
        <div class="mb-3">
            <label for="takedown_accuracy">Takedown Accuracy (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="takedown_accuracy" id="takedown_accuracy" placeholder="Takedown Accuracy" required value="<?php se($fighter, "takedown_accuracy"); ?>">
        </div>
        <div class="mb-3">
            <label for="significant_strikes_landed">Significant Strikes Landed</label>
            <input type="number" min="0" name="significant_strikes_landed" id="significant_strikes_landed" placeholder="Significant Strikes Landed" required value="<?php se($fighter, "significant_strikes_landed"); ?>">
        </div>
        <div class="mb-3">
            <label for="significant_strikes_defense">Significant Strikes Defense (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="significant_strikes_defense" id="significant_strikes_defense" placeholder="Significant Strikes Defense" required value="<?php se($fighter, "significant_strikes_defense"); ?>">
        </div>
        <div class="mb-3">
            <label for="takedown_defense">Takedown Defense (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="takedown_defense" id="takedown_defense" placeholder="Takedown Defense" required value="<?php se($fighter, "takedown_defense"); ?>">
        </div>
        <input type="submit" value="Update" class="btn btn-primary">
    </form>
</div>


<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>