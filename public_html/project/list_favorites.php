<?php
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You must be logged in to view favorites", "warning");
    die(header("Location: " . get_url("login.php")));
}

$user_id = get_user_id();
$db = getDB();
$results = [];

try {
    $stmt = $db->prepare("
        SELECT f.id, f.name, f.striking_accuracy, f.takedown_accuracy, 
        f.significant_strikes_landed, f.significant_strikes_defense, 
        f.takedown_defense, f.is_api
        FROM `IT202-S26-FighterStats` f
        INNER JOIN `IT202-S26-UserFavorites` uf ON f.id = uf.fighter_id
        WHERE uf.user_id = :user_id
        ORDER BY uf.created DESC
    ");
    $stmt->execute([":user_id" => $user_id]);
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

    <?php if (count($results) == 0) : ?>
        <p>You have no favorite fighters yet.</p>
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
                            <a href="<?php echo get_url('compare_fighters.php'); ?>" class="btn btn-sm btn-primary">Compare</a>
                            <a href="<?php echo get_url('remove_favorite.php'); ?>?fighter_id=<?php echo $record["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove <?php echo htmlspecialchars($record['name']); ?> from favorites?')">Remove</a>
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