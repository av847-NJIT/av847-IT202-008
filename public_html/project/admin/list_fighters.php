<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// Record limit - default 10, valid range 1-100
$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// Sort logic
$allowed_sort_columns = ["name", "striking_accuracy", "takedown_accuracy", "significant_strikes_landed", "significant_strikes_defense", "takedown_defense"];
$sort = isset($_GET["sort"]) && in_array($_GET["sort"], $allowed_sort_columns) ? $_GET["sort"] : "name";
$order = isset($_GET["order"]) && $_GET["order"] === "desc" ? "DESC" : "ASC";

// Filter logic
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

$query = "SELECT id, name, striking_accuracy, takedown_accuracy, significant_strikes_landed, significant_strikes_defense, takedown_defense, api_id, is_api FROM `IT202-S26-FighterStats`";

$params = [];
if (!empty($search)) {
    $query .= " WHERE name LIKE :search";
    $params[":search"] = "%" . $search . "%";
}

$query .= " ORDER BY `$sort` $order LIMIT :limit";

$db = getDB();
$results = [];
try {
    $stmt = $db->prepare($query);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching fighters " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>

<div class="container-fluid">
    <h3>List Fighters</h3>

    <!-- Filter/Sort/Limit Form -->
    <form method="GET" class="row mb-3">
        <div class="col mb-3">
            <label class="form-label">Search by Name</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Fighter name">
        </div>
        <div class="col mb-3">
            <label class="form-label">Sort By</label>
            <select name="sort" class="form-control">
                <?php foreach ($allowed_sort_columns as $col) : ?>
                    <option value="<?php echo $col; ?>" <?php echo $sort === $col ? "selected" : ""; ?>>
                        <?php echo ucwords(str_replace("_", " ", $col)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col mb-3">
            <label class="form-label">Order</label>
            <select name="order" class="form-control">
                <option value="asc" <?php echo $order === "ASC" ? "selected" : ""; ?>>Ascending</option>
                <option value="desc" <?php echo $order === "DESC" ? "selected" : ""; ?>>Descending</option>
            </select>
        </div>
        <div class="col mb-3">
            <label class="form-label">Results (1-100)</label>
            <input type="number" name="limit" class="form-control" min="1" max="100" value="<?php echo $limit; ?>">
        </div>
        <div class="col mb-3 align-self-end">
            <input type="submit" value="Apply" class="btn btn-primary">
        </div>
    </form>

    <?php if (count($results) == 0) : ?>
        <p>No results found for your search.</p>
    <?php else : ?>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Striking Accuracy</th>
                    <th>Takedown Accuracy</th>
                    <th>Sig. Strikes Landed</th>
                    <th>Sig. Strikes Defense</th>
                    <th>Takedown Defense</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $record) : ?>
                    <tr>
                        <td><?php echo $record["name"] ?? "N/A"; ?></td>
                        <td><?php echo $record["striking_accuracy"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["takedown_accuracy"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["significant_strikes_landed"] ?? "N/A"; ?></td>
                        <td><?php echo $record["significant_strikes_defense"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["takedown_defense"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["is_api"] ? "API" : "Manual"; ?></td>
                        <td>
                            <a href="<?php echo get_url('admin/view_fighter.php'); ?>?id=<?php echo $record["id"]; ?>" class="btn btn-sm btn-info">View</a>
                            <a href="<?php echo get_url('admin/edit_fighter.php'); ?>?id=<?php echo $record["id"]; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="<?php echo get_url('admin/delete_fighter.php'); ?>?id=<?php echo $record["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($record['name']); ?>?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>