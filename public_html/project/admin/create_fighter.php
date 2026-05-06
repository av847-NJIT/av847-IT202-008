<?php
// note: we go up one directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}
?>

<?php
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    $name = se($_POST, "name", "", false);
    $fighter = [];

    if ($name) {
        if ($action === "fetch") {
            $result = fetch_quote($name);

            error_log("Data from API: " . var_export($result, true));
            if ($result) {
                $fighter["name"] = $result["name"];
                $fighter["striking_accuracy"] = $result["striking_accuracy_pct"];
                $fighter["takedown_accuracy"] = $result["takedown_accuracy_pct"];
                $fighter["significant_strikes_landed"] = $result["sig_strikes_landed"];
                $fighter["significant_strikes_defense"] = $result["sig_str_defense_pct"];
                $fighter["takedown_defense"] = $result["takedown_defense_pct"];
                $fighter["api_id"] = $result["slug"];
                $fighter["is_api"] = 1;
            }
        } else if ($action === "create") {
            foreach ($_POST as $k => $v) {
                // only keep keys that match your table columns
                if (!in_array($k, ["name", "striking_accuracy", "takedown_accuracy", "significant_strikes_landed", "significant_strikes_defense", "takedown_defense"])) {
                    unset($_POST[$k]);
                }
            }
            $fighter = $_POST;

            $hasError = false;

            // PHP validations
            if (empty($fighter["name"])) {
                flash("PHP-Fighter name must not be empty", "danger");
                $hasError = true;
            }
            if (!is_numeric($fighter["striking_accuracy"]) || $fighter["striking_accuracy"] < 0 || $fighter["striking_accuracy"] > 100) {
                flash("PHP-Striking accuracy must be between 0 and 100", "danger");
                $hasError = true;
            }
            if (!is_numeric($fighter["takedown_accuracy"]) || $fighter["takedown_accuracy"] < 0 || $fighter["takedown_accuracy"] > 100) {
                flash("PHP-Takedown accuracy must be between 0 and 100", "danger");
                $hasError = true;
            }
            if (!is_numeric($fighter["significant_strikes_landed"]) || $fighter["significant_strikes_landed"] < 0) {
                flash("PHP-Significant strikes landed must be a positive number", "danger");
                $hasError = true;
            }
            if (!is_numeric($fighter["significant_strikes_defense"]) || $fighter["significant_strikes_defense"] < 0 || $fighter["significant_strikes_defense"] > 100) {
                flash("PHP-Significant strikes defense must be between 0 and 100", "danger");
                $hasError = true;
            }
            if (!is_numeric($fighter["takedown_defense"]) || $fighter["takedown_defense"] < 0 || $fighter["takedown_defense"] > 100) {
                flash("PHP-Takedown defense must be between 0 and 100", "danger");
                $hasError = true;
            }

            if (!$hasError) {
                $db = getDB();
                $nameCheck = $db->prepare("SELECT id FROM `IT202-S26-FighterStats` WHERE name = :name");
                $nameCheck->execute([":name" => $fighter["name"]]);
                if ($nameCheck->rowCount() > 0) {
                    flash("A fighter with this name already exists", "warning");
                    $hasError = true;
                }
            }

            if ($hasError) {
                $fighter = [];
            } else {
                $fighter["is_api"] = 0;
                error_log("Cleaned up POST: " . var_export($fighter, true));
            }
        }
    } else {
        flash("You must provide a fighter name", "warning");
    }

    // Insert data
    try {
        $r = insert("IT202-S26-FighterStats", $fighter, ["update_duplicate" => true]);
        if ($r["lastInsertId"]) {
            flash("Inserted record " . $r["lastInsertId"], "success");
        } else {
            flash("Error inserting record", "warning");
        }
    } catch (PDOException $e) {
        error_log("Something broke with the query" . var_export($e, true));
        flash("An error occured: " . $e->getMessage(), "danger");
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch Fighter Stats</h3>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create')">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch')">Create</a>
        </li>
    </ul>

    <div id="fetch" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="name">Fighter Name</label>
                <input type="search" name="name" id="name" placeholder="Fighter Name" required>
            </div>
            <input type="hidden" name="action" value="fetch">
            <input type="submit" value="Fetch" class="btn btn-primary">
        </form>
    </div>

    <div id="create" style="display: none;" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="name">Fighter Name</label>
                <input type="text" name="name" id="name" placeholder="Fighter Name" required>
            </div>
            <div class="mb-3">
                <label for="striking_accuracy">Striking Accuracy (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="striking_accuracy" id="striking_accuracy" placeholder="Striking Accuracy" style="width: 150px;" required>
            </div>
            <div class="mb-3">
                <label for="takedown_accuracy">Takedown Accuracy (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="takedown_accuracy" id="takedown_accuracy" placeholder="Takedown Accuracy" style="width: 150px;" required>
            </div>
            <div class="mb-3">
                <label for="significant_strikes_landed">Significant Strikes Landed</label>
                <input type="number" min="0" name="significant_strikes_landed" id="significant_strikes_landed" placeholder="Significant Strikes Landed" style="width: 200px;" required>
            </div>
            <div class="mb-3">
                <label for="significant_strikes_defense">Significant Strikes Defense (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="significant_strikes_defense" id="significant_strikes_defense" placeholder="Significant Strikes Defense" style="width: 200px;" required>
            </div>
            <div class="mb-3">
                <label for="takedown_defense">Takedown Defense (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="takedown_defense" id="takedown_defense" placeholder="Takedown Defense" style="width: 150px;" required>
            </div>
            <input type="hidden" name="action" value="create">
            <input type="submit" value="Create" class="btn btn-primary">
        </form>
    </div>
</div>

<script>
    // JS validations
    function validate(form) {
        // only validate the create form
        if (form.action.value !== "create") {
            return true;
        }

        let isValid = true;
        let striking = parseFloat(form.striking_accuracy.value);
        let takedown = parseFloat(form.takedown_accuracy.value);
        let strikesLanded = parseInt(form.significant_strikes_landed.value);
        let strikesDefense = parseFloat(form.significant_strikes_defense.value);
        let takedownDefense = parseFloat(form.takedown_defense.value);

        if (!form.name.value.trim()) {
            flash("JS-Fighter name must not be empty", "warning");
            isValid = false;
        }
        if (isNaN(striking) || striking < 0 || striking > 100) {
            flash("JS-Striking accuracy must be between 0 and 100", "warning");
            isValid = false;
        }
        if (isNaN(takedown) || takedown < 0 || takedown > 100) {
            flash("JS-Takedown accuracy must be between 0 and 100", "warning");
            isValid = false;
        }
        if (isNaN(strikesLanded) || strikesLanded < 0) {
            flash("JS-Significant strikes landed must be a positive number", "warning");
            isValid = false;
        }
        if (isNaN(strikesDefense) || strikesDefense < 0 || strikesDefense > 100) {
            flash("JS-Significant strikes defense must be between 0 and 100", "warning");
            isValid = false;
        }
        if (isNaN(takedownDefense) || takedownDefense < 0 || takedownDefense > 100) {
            flash("JS-Takedown defense must be between 0 and 100", "warning");
            isValid = false;
        }

        return isValid;
    }

    function switchTab(tab) {
        let target = document.getElementById(tab);
        if (target) {
            let eles = document.getElementsByClassName("tab-target");
            for (let ele of eles) {
                ele.style.display = (ele.id === tab) ? "none" : "block";
            }
        }
    }
</script>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>