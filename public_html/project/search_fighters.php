<?php
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You must be logged in to search fighters", "warning");
    die(header("Location: " . get_url("login.php")));
}

$user_id = get_user_id();
$db = getDB();
$results = [];
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if (!empty($search)) {
    try {
        // fetch fighters matching search and check if already favorited by this user
        $stmt = $db->prepare("
            SELECT f.id, f.name, f.striking_accuracy, f.takedown_accuracy,
                   f.significant_strikes_landed, f.significant_strikes_defense,
                   f.takedown_defense, f.is_api,
                   CASE WHEN uf.id IS NOT NULL THEN 1 ELSE 0 END AS is_favorited
            FROM `IT202-S26-FighterStats` f
            LEFT JOIN `IT202-S26-UserFavorites` uf 
                ON f.id = uf.fighter_id AND uf.user_id = :user_id
            WHERE f.name LIKE :search
            ORDER BY f.name ASC
        ");
        $stmt->execute([
            ":user_id"  => $user_id,
            ":search"   => "%" . $search . "%"
        ]);
        $r = $stmt->fetchAll();
        if ($r) {
            $results = $r;
        }
    } catch (PDOException $e) {
        error_log("Error searching fighters: " . var_export($e, true));
        flash("Error searching fighters", "danger");
    }
}
?>

<div class="container-fluid">
    <h3>Search Fighters</h3>

    <!-- Search Form -->
    <form method="GET" class="row mb-3">
        <div class="col mb-3">
            <label class="form-label">Fighter Name</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name..." required>
        </div>
        <div class="col mb-3 align-self-end">
            <input type="submit" value="Search" class="btn btn-primary">
        </div>
    </form>

    <?php if (!empty($search) && count($results) == 0) : ?>
        <p>No fighters found matching "<?php echo htmlspecialchars($search); ?>"</p>
    <?php elseif (count($results) > 0) : ?>
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
                            <?php if ($record["is_favorited"]) : ?>
                                <span class="btn btn-sm btn-secondary disabled">Already Favorited</span>
                                <a href="<?php echo get_url('remove_favorite.php'); ?>?fighter_id=<?php echo $record["id"]; ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Remove <?php echo htmlspecialchars($record['name']); ?> from favorites?')">
                                    Remove
                                </a>
                            <?php else : ?>
                                <a href="<?php echo get_url('add_favorite.php'); ?>?fighter_id=<?php echo $record["id"]; ?>"
                                    class="btn btn-sm btn-success">
                                    Add to Favorites
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="<?php echo get_url('list_favorites.php'); ?>" class="btn btn-secondary mt-2">My Favorites</a>
</div>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>