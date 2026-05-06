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
    }
    $fighter = $_POST;
    error_log("Cleaned up POST: " . var_export($fighter, true));

    // PHP Validations
    $hasError = false;

    if (empty($fighter["name"])) {
        flash("PHP - Fighter name must not be empty", "danger");
        $hasError = true;
    }
    if (!is_numeric($fighter["striking_accuracy"]) || $fighter["striking_accuracy"] < 0 || $fighter["striking_accuracy"] > 100) {
        flash("PHP - Striking accuracy must be between 0 and 100", "danger");
        $hasError = true;
    }
    if (!is_numeric($fighter["takedown_accuracy"]) || $fighter["takedown_accuracy"] < 0 || $fighter["takedown_accuracy"] > 100) {
        flash("PHP - Takedown accuracy must be between 0 and 100", "danger");
        $hasError = true;
    }
    if (!is_numeric($fighter["significant_strikes_landed"]) || $fighter["significant_strikes_landed"] < 0) {
        flash("PHP - Significant strikes landed must be a positive number", "danger");
        $hasError = true;
    }
    if (!is_numeric($fighter["significant_strikes_defense"]) || $fighter["significant_strikes_defense"] < 0 || $fighter["significant_strikes_defense"] > 100) {
        flash("PHP - Significant strikes defense must be between 0 and 100", "danger");
        $hasError = true;
    }
    if (!is_numeric($fighter["takedown_defense"]) || $fighter["takedown_defense"] < 0 || $fighter["takedown_defense"] > 100) {
        flash("PHP - Takedown defense must be between 0 and 100", "danger");
        $hasError = true;
    }

    if (!$hasError) {
        $fighter["id"] = $id;
        try {
            $r = update("IT202-S26-FighterStats", $fighter);
            if ($r["rowCount"]) {
                flash("Updated " . $r["rowCount"] . " record(s)", "success");
            } else {
                flash("Error updating record (this can occur if no properties changed)", "warning");
            }
        } catch (PDOException $e) {
            error_log("Something broke with the query: " . var_export($e, true));
            flash("An error occurred", "danger");
        } catch (Exception $e) {
            error_log("Something broke with the query: " . var_export($e, true));
            flash("An error occurred: " . $e->getMessage(), "danger");
        }
    }
}

$fighter = [];
if ($id > -1) {
    $db = getDB();
    $query = "SELECT name, striking_accuracy, takedown_accuracy, significant_strikes_landed, significant_strikes_defense, takedown_defense FROM `IT202-S26-FighterStats` WHERE id = :id";
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
    <form method="POST" onsubmit="return validate(this)">
        <div class="mb-3">
            <label class="form-label" for="name">Fighter Name</label>
            <input class="form-control" type="text" name="name" id="name" placeholder="Fighter Name" required value="<?php se($fighter, "name"); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="striking_accuracy">Striking Accuracy (%)</label>
            <input class="form-control" type="number" step="0.01" min="0" max="100" name="striking_accuracy" id="striking_accuracy" placeholder="Striking Accuracy" style="width: 150px;" required value="<?php se($fighter, "striking_accuracy"); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="takedown_accuracy">Takedown Accuracy (%)</label>
            <input class="form-control" type="number" step="0.01" min="0" max="100" name="takedown_accuracy" id="takedown_accuracy" placeholder="Takedown Accuracy" style="width: 150px;" required value="<?php se($fighter, "takedown_accuracy"); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="significant_strikes_landed">Significant Strikes Landed</label>
            <input class="form-control" type="number" min="0" name="significant_strikes_landed" id="significant_strikes_landed" placeholder="Significant Strikes Landed" style="width: 200px;" required value="<?php se($fighter, "significant_strikes_landed"); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="significant_strikes_defense">Significant Strikes Defense (%)</label>
            <input class="form-control" type="number" step="0.01" min="0" max="100" name="significant_strikes_defense" id="significant_strikes_defense" placeholder="Significant Strikes Defense" style="width: 200px;" required value="<?php se($fighter, "significant_strikes_defense"); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="takedown_defense">Takedown Defense (%)</label>
            <input class="form-control" type="number" step="0.01" min="0" max="100" name="takedown_defense" id="takedown_defense" placeholder="Takedown Defense" style="width: 150px;" required value="<?php se($fighter, "takedown_defense"); ?>">
        </div>
        <input type="submit" value="Update" class="btn btn-primary">
        <a href="<?php echo get_url('admin/list_fighters.php'); ?>" class="btn btn-secondary">Back to List</a>
    </form>
</div>

<script>
    function validate(form) {
        let isValid = true;
        let striking = parseFloat(form.striking_accuracy.value);
        let takedown = parseFloat(form.takedown_accuracy.value);
        let strikesLanded = parseInt(form.significant_strikes_landed.value);
        let strikesDefense = parseFloat(form.significant_strikes_defense.value);
        let takedownDefense = parseFloat(form.takedown_defense.value);

        if (!form.name.value.trim()) {
            flash("JS - Fighter name must not be empty", "warning");
            isValid = false;
        }
        if (isNaN(striking) || striking < 0 || striking > 100) {
            flash("JS - Striking accuracy must be between 0 and 100", "warning");
            isValid = false;
        }
        if (isNaN(takedown) || takedown < 0 || takedown > 100) {
            flash("JS - Takedown accuracy must be between 0 and 100", "warning");
            isValid = false;
        }
        if (isNaN(strikesLanded) || strikesLanded < 0) {
            flash("JS - Significant strikes landed must be a positive number", "warning");
            isValid = false;
        }
        if (isNaN(strikesDefense) || strikesDefense < 0 || strikesDefense > 100) {
            flash("JS - Significant strikes defense must be between 0 and 100", "warning");
            isValid = false;
        }
        if (isNaN(takedownDefense) || takedownDefense < 0 || takedownDefense > 100) {
            flash("JS - Takedown defense must be between 0 and 100", "warning");
            isValid = false;
        }

        return isValid;
    }
</script>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>