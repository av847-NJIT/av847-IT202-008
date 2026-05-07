<?php
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You must be logged in to view favorites", "warning");
    die(header("Location: " . get_url("login.php")));
}

$user_id = get_user_id();
$db = getDB();

// limit - default 10, valid range 1-100
$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// sort logic
$allowed_sort_columns = ["name", "striking_accuracy", "takedown_accuracy", "significant_strikes_landed", "significant_strikes_defense", "takedown_defense"];
$sort = isset($_GET["sort"]) && in_array($_GET["sort"], $allowed_sort_columns) ? $_GET["sort"] : "name";
$order = isset($_GET["order"]) && $_GET["order"] === "desc" ? "DESC" : "ASC";

// filter logic
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

// total count query
$count_query = "SELECT COUNT(*) as total FROM `IT202-S26-FighterStats` f 
                INNER JOIN `IT202-S26-UserFavorites` uf ON f.id = uf.fighter_id 
                WHERE uf.user_id = :user_id";
$count_params = [":user_id" => $user_id];

if (!empty($search)) {
    $count_query .= " AND f.name LIKE :search";
    $count_params[":search"] = "%" . $search . "%";
}

$total = 0;
try {
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($count_params);
    $total = $count_stmt->fetch()["total"];
} catch (PDOException $e) {
    error_log("Error counting favorites: " . var_export($e, true));
}

// total possible (no filter)
$total_possible = 0;
try {
    $tp_stmt = $db->prepare("SELECT COUNT(*) as total FROM `IT202-S26-UserFavorites` WHERE user_id = :user_id");
    $tp_stmt->execute([":user_id" => $user_id]);
    $total_possible = $tp_stmt->fetch()["total"];
} catch (PDOException $e) {
    error_log("Error counting total favorites: " . var_export($e, true));
}

// main query
$query = "SELECT f.id, f.name, f.striking_accuracy, f.takedown_accuracy,
          f.significant_strikes_landed, f.significant_strikes_defense,
          f.takedown_defense, f.is_api
          FROM `IT202-S26-FighterStats` f
          INNER JOIN `IT202-S26-UserFavorites` uf ON f.id = uf.fighter_id
          WHERE uf.user_id = :user_id";

$params = [":user_id" => $user_id];

if (!empty($search)) {
    $query .= " AND f.name LIKE :search";
    $params[":search"] = "%" . $search . "%";
}

$query .= " ORDER BY `$sort` $order LIMIT :limit";

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
    error_log("Error fetching favorites: " . var_export($e, true));
    flash("Error loading favorites", "danger");
}
?>

<div class="container-fluid">
    <h3>My Favorite Fighters</h3>

    <!-- Stats Section -->
    <div class="mb-3">
        <span class="badge bg-primary">Showing: <?php echo count($results); ?></span>
        <span class="badge bg-secondary">Filtered Results: <?php echo $total; ?></span>
        <span class="badge bg-dark">Total Favorites: <?php echo $total_possible; ?></span>
    </div>

    <!-- Filter/Sort Form -->
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
            <a href="<?php echo get_url('list_favorites.php'); ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Remove All Button -->
    <div class="mb-3">
        <a href="<?php echo get_url('remove_all_favorites.php'); ?>"
            class="btn btn-danger"
            onclick="return confirm('Are you sure you want to remove all your favorites?')">
            Remove All Favorites
        </a>
        <a href="<?php echo get_url('search_fighters.php'); ?>" class="btn btn-success">Add Fighters</a>
    </div>

    <?php if (count($results) == 0) : ?>
        <p>No results available.</p>
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
                            <a href="<?php echo get_url('remove_favorite.php'); ?>?fighter_id=<?php echo $record["id"]; ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Remove <?php echo htmlspecialchars($record['name']); ?> from favorites?')">
                                Remove
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>