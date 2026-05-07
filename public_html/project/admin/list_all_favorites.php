<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$db = getDB();

// limit - default 10, valid range 1-100
$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 10;
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// sort logic
$allowed_sort_columns = ["name", "striking_accuracy", "takedown_accuracy", "significant_strikes_landed", "significant_strikes_defense", "takedown_defense", "username"];
$sort = isset($_GET["sort"]) && in_array($_GET["sort"], $allowed_sort_columns) ? $_GET["sort"] : "name";
$order = isset($_GET["order"]) && $_GET["order"] === "desc" ? "DESC" : "ASC";

// filter logic
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

// total count query
$count_query = "SELECT COUNT(*) as total 
                FROM `IT202-S26-UserFavorites` uf
                INNER JOIN `IT202-S26-FighterStats` f ON f.id = uf.fighter_id
                INNER JOIN Users u ON u.id = uf.user_id";
$count_params = [];

if (!empty($search)) {
    $count_query .= " WHERE u.username LIKE :search";
    $count_params[":search"] = "%" . $search . "%";
}

$total = 0;
try {
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($count_params);
    $total = $count_stmt->fetch()["total"];
} catch (PDOException $e) {
    error_log("Error counting all favorites: " . var_export($e, true));
}

// total possible (no filter)
$total_possible = 0;
try {
    $tp_stmt = $db->prepare("SELECT COUNT(*) as total FROM `IT202-S26-UserFavorites`");
    $tp_stmt->execute();
    $total_possible = $tp_stmt->fetch()["total"];
} catch (PDOException $e) {
    error_log("Error counting total possible: " . var_export($e, true));
}

// main query
$query = "SELECT f.id, f.name, f.striking_accuracy, f.takedown_accuracy,
          f.significant_strikes_landed, f.significant_strikes_defense,
          f.takedown_defense, f.is_api, u.username, uf.id as favorite_id,
          (SELECT COUNT(*) FROM `IT202-S26-UserFavorites` uf2 WHERE uf2.fighter_id = f.id) as total_users
          FROM `IT202-S26-UserFavorites` uf
          INNER JOIN `IT202-S26-FighterStats` f ON f.id = uf.fighter_id
          INNER JOIN Users u ON u.id = uf.user_id";

$params = [];

if (!empty($search)) {
    $query .= " WHERE u.username LIKE :search";
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
    error_log("Error fetching all favorites: " . var_export($e, true));
    flash("Error loading favorites", "danger");
}
?>

<div class="container-fluid">
    <h3>All User Favorites</h3>

    <!-- Stats Section -->
    <div class="mb-3">
        <span class="badge bg-primary">Showing: <?php echo count($results); ?></span>
        <span class="badge bg-secondary">Filtered Results: <?php echo $total; ?></span>
        <span class="badge bg-dark">Total Associations: <?php echo $total_possible; ?></span>
    </div>

    <!-- Filter/Sort Form -->
    <form method="GET" class="row mb-3">
        <div class="col mb-3">
            <label class="form-label">Filter by Username</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username">
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
            <a href="<?php echo get_url('admin/list_all_favorites.php'); ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Remove All for filtered user -->
    <?php if (!empty($search)) : ?>
        <div class="mb-3">
            <a href="<?php echo get_url('admin/remove_user_favorites.php'); ?>?username=<?php echo urlencode($search); ?>"
                class="btn btn-danger"
                onclick="return confirm('Remove all favorites for users matching \'<?php echo htmlspecialchars($search); ?>\'?')">
                Remove All Favorites for Matching Users
            </a>
        </div>
    <?php endif; ?>

    <?php if (count($results) == 0) : ?>
        <p>No results available.</p>
    <?php else : ?>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Username</th>
                    <th>Fighter Name</th>
                    <th>Striking Accuracy</th>
                    <th>Takedown Accuracy</th>
                    <th>Sig. Strikes Landed</th>
                    <th>Sig. Strikes Defense</th>
                    <th>Takedown Defense</th>
                    <th>Source</th>
                    <th>Total Users Favorited</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $record) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_url('public_profile.php'); ?>?username=<?php echo urlencode($record["username"]); ?>">
                                <?php echo htmlspecialchars($record["username"]); ?>
                            </a>
                        </td>
                        <td><?php echo $record["name"] ?? "N/A"; ?></td>
                        <td><?php echo $record["striking_accuracy"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["takedown_accuracy"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["significant_strikes_landed"] ?? "N/A"; ?></td>
                        <td><?php echo $record["significant_strikes_defense"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["takedown_defense"] ?? "N/A"; ?>%</td>
                        <td><?php echo $record["is_api"] ? "API" : "Manual"; ?></td>
                        <td><?php echo $record["total_users"]; ?></td>
                        <td>
                            <a href="<?php echo get_url('admin/view_fighter.php'); ?>?id=<?php echo $record["id"]; ?>" class="btn btn-sm btn-info">View</a>
                            <a href="<?php echo get_url('admin/remove_association.php'); ?>?id=<?php echo $record["favorite_id"]; ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Remove this association?')">
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
require_once(__DIR__ . "/../../../partials/flash.php");
?>