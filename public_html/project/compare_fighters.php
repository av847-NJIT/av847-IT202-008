<?php
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You must be logged in to compare fighters", "warning");
    die(header("Location: " . get_url("login.php")));
}

$db = getDB();
$fighters = [];
try {
    $stmt = $db->prepare("SELECT id, name FROM `IT202-S26-FighterStats` ORDER BY name ASC");
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $fighters = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching fighters: " . var_export($e, true));
    flash("Error loading fighters", "danger");
}

$fighter1 = [];
$fighter2 = [];
$fighter1_id = $_GET["fighter1_id"] ?? null;
$fighter2_id = $_GET["fighter2_id"] ?? null;

if ($fighter1_id && $fighter2_id) {
    if ($fighter1_id == $fighter2_id) {
        flash("Please select two different fighters", "warning");
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM `IT202-S26-FighterStats` WHERE id = :id");

            $stmt->execute([":id" => $fighter1_id]);
            $r = $stmt->fetch();
            if ($r) $fighter1 = $r;

            $stmt->execute([":id" => $fighter2_id]);
            $r = $stmt->fetch();
            if ($r) $fighter2 = $r;
        } catch (PDOException $e) {
            error_log("Error fetching fighters for comparison: " . var_export($e, true));
            flash("Error loading fighters", "danger");
        }
    }
}

$categories = [
    "Striking Accuracy (%)"      => "striking_accuracy",
    "Takedown Accuracy (%)"      => "takedown_accuracy",
    "Significant Strikes Landed" => "significant_strikes_landed",
    "Sig. Strikes Defense (%)"   => "significant_strikes_defense",
    "Takedown Defense (%)"       => "takedown_defense"
];
?>

<div class="container-fluid">
    <h3>Compare Fighters</h3>

    <!-- Selection Form - always visible -->
    <form method="GET">
        <div class="row">
            <div class="col mb-3">
                <label class="form-label" for="fighter1">Fighter 1</label>
                <select name="fighter1_id" id="fighter1" class="form-control" required>
                    <option value="">-- Select Fighter --</option>
                    <?php foreach ($fighters as $f) : ?>
                        <option value="<?php echo $f["id"]; ?>" <?php echo $fighter1_id == $f["id"] ? "selected" : ""; ?>>
                            <?php echo $f["name"]; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col mb-3">
                <label class="form-label" for="fighter2">Fighter 2</label>
                <select name="fighter2_id" id="fighter2" class="form-control" required>
                    <option value="">-- Select Fighter --</option>
                    <?php foreach ($fighters as $f) : ?>
                        <option value="<?php echo $f["id"]; ?>" <?php echo $fighter2_id == $f["id"] ? "selected" : ""; ?>>
                            <?php echo $f["name"]; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <input type="submit" value="Compare" class="btn btn-primary">
    </form>

    <!-- Results - only show if both fighters are loaded -->
    <?php if (!empty($fighter1) && !empty($fighter2)) : ?>
        <h3 class="mt-4">Head to Head Comparison</h3>
        <table class="table table-bordered text-center">
            <thead class="table-dark">
                <tr>
                    <th><?php echo $fighter1["name"] ?? "N/A"; ?></th>
                    <th>Category</th>
                    <th><?php echo $fighter2["name"] ?? "N/A"; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $label => $column) : ?>
                    <?php
                    $val1 = $fighter1[$column] ?? "N/A";
                    $val2 = $fighter2[$column] ?? "N/A";

                    if ($val1 > $val2) {
                        $class1 = "table-success";
                        $class2 = "table-danger";
                    } else if ($val2 > $val1) {
                        $class1 = "table-danger";
                        $class2 = "table-success";
                    } else {
                        $class1 = "table-warning";
                        $class2 = "table-warning";
                    }
                    ?>
                    <tr>
                        <td class="<?php echo $class1; ?>"><?php echo $val1; ?></td>
                        <td><strong><?php echo $label; ?></strong></td>
                        <td class="<?php echo $class2; ?>"><?php echo $val2; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php require_once(__DIR__ . "/../../partials/flash.php"); ?>