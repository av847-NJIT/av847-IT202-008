<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$db = getDB();
$users = [];
$fighters = [];

$user_search = isset($_POST["user_search"]) ? trim($_POST["user_search"]) : (isset($_GET["user_search"]) ? trim($_GET["user_search"]) : "");
$fighter_search = isset($_POST["fighter_search"]) ? trim($_POST["fighter_search"]) : (isset($_GET["fighter_search"]) ? trim($_GET["fighter_search"]) : "");

// handle checkbox association toggle
if (isset($_POST["assign"])) {
    $selected_users = isset($_POST["users"]) ? $_POST["users"] : [];
    $selected_fighters = isset($_POST["fighters"]) ? $_POST["fighters"] : [];

    if (empty($selected_users) || empty($selected_fighters)) {
        flash("Please select at least one user and one fighter", "warning");
    } else {
        $added = 0;
        $removed = 0;

        foreach ($selected_users as $user_id) {
            $user_id = intval($user_id);
            foreach ($selected_fighters as $fighter_id) {
                $fighter_id = intval($fighter_id);
                try {
                    // check if association exists
                    $check = $db->prepare("SELECT id FROM `IT202-S26-UserFavorites` 
                                          WHERE user_id = :user_id AND fighter_id = :fighter_id");
                    $check->execute([":user_id" => $user_id, ":fighter_id" => $fighter_id]);

                    if ($check->rowCount() > 0) {
                        // exists - remove it
                        $delete = $db->prepare("DELETE FROM `IT202-S26-UserFavorites` 
                                               WHERE user_id = :user_id AND fighter_id = :fighter_id");
                        $delete->execute([":user_id" => $user_id, ":fighter_id" => $fighter_id]);
                        $removed++;
                    } else {
                        // doesn't exist - add it
                        $insert = $db->prepare("INSERT INTO `IT202-S26-UserFavorites` 
                                               (user_id, fighter_id) VALUES (:user_id, :fighter_id)");
                        $insert->execute([":user_id" => $user_id, ":fighter_id" => $fighter_id]);
                        $added++;
                    }
                } catch (PDOException $e) {
                    error_log("Error toggling association: " . var_export($e, true));
                    flash("An error occurred processing associations", "danger");
                }
            }
        }
        flash("Added $added and removed $removed association(s)", "success");
    }
}

// fetch matching users
if (!empty($user_search)) {
    try {
        $stmt = $db->prepare("SELECT id, username, email FROM Users 
                              WHERE username LIKE :search 
                              LIMIT 25");
        $stmt->execute([":search" => "%" . $user_search . "%"]);
        $r = $stmt->fetchAll();
        if ($r) $users = $r;
    } catch (PDOException $e) {
        error_log("Error fetching users: " . var_export($e, true));
        flash("Error loading users", "danger");
    }
}

// fetch matching fighters
if (!empty($fighter_search)) {
    try {
        $stmt = $db->prepare("SELECT id, name, striking_accuracy, takedown_accuracy FROM `IT202-S26-FighterStats` 
                              WHERE name LIKE :search 
                              LIMIT 25");
        $stmt->execute([":search" => "%" . $fighter_search . "%"]);
        $r = $stmt->fetchAll();
        if ($r) $fighters = $r;
    } catch (PDOException $e) {
        error_log("Error fetching fighters: " . var_export($e, true));
        flash("Error loading fighters", "danger");
    }
}

// fetch existing associations for display
$existing = [];
if (!empty($users) && !empty($fighters)) {
    $user_ids = array_map(fn($u) => $u["id"], $users);
    $fighter_ids = array_map(fn($f) => $f["id"], $fighters);

    $placeholders_u = implode(",", array_fill(0, count($user_ids), "?"));
    $placeholders_f = implode(",", array_fill(0, count($fighter_ids), "?"));

    try {
        $stmt = $db->prepare("SELECT user_id, fighter_id FROM `IT202-S26-UserFavorites` 
                              WHERE user_id IN ($placeholders_u) 
                              AND fighter_id IN ($placeholders_f)");
        $stmt->execute(array_merge($user_ids, $fighter_ids));
        $r = $stmt->fetchAll();
        foreach ($r as $row) {
            $existing[$row["user_id"] . "_" . $row["fighter_id"]] = true;
        }
    } catch (PDOException $e) {
        error_log("Error fetching existing associations: " . var_export($e, true));
    }
}
?>

<div class="container-fluid">
    <h3>Assign Fighter Favorites</h3>

    <!-- Search Form -->
    <form method="POST">
        <div class="row mb-3">
            <div class="col mb-3">
                <label class="form-label">Search Username</label>
                <input type="text" name="user_search" class="form-control"
                    value="<?php echo htmlspecialchars($user_search); ?>"
                    placeholder="Partial username match">
            </div>
            <div class="col mb-3">
                <label class="form-label">Search Fighter</label>
                <input type="text" name="fighter_search" class="form-control"
                    value="<?php echo htmlspecialchars($fighter_search); ?>"
                    placeholder="Partial fighter name match">
            </div>
        </div>
        <input type="submit" name="search" value="Search" class="btn btn-primary mb-3">

        <!-- Results -->
        <?php if (!empty($user_search) || !empty($fighter_search)) : ?>
            <div class="row">
                <!-- Users Column -->
                <div class="col">
                    <h5>Users</h5>
                    <?php if (empty($users)) : ?>
                        <p>No results available.</p>
                    <?php else : ?>
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Select</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user) : ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="users[]" value="<?php echo $user["id"]; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($user["username"]); ?></td>
                                        <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Fighters Column -->
                <div class="col">
                    <h5>Fighters</h5>
                    <?php if (empty($fighters)) : ?>
                        <p>No results available.</p>
                    <?php else : ?>
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Select</th>
                                    <th>Name</th>
                                    <th>Striking Accuracy</th>
                                    <th>Takedown Accuracy</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fighters as $fighter) : ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="fighters[]" value="<?php echo $fighter["id"]; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($fighter["name"]); ?></td>
                                        <td><?php echo $fighter["striking_accuracy"] ?? "N/A"; ?>%</td>
                                        <td><?php echo $fighter["takedown_accuracy"] ?? "N/A"; ?>%</td>
                                        <td>
                                            <?php
                                            // show existing associations for context
                                            $associated_users = [];
                                            foreach ($users as $u) {
                                                $key = $u["id"] . "_" . $fighter["id"];
                                                if (isset($existing[$key])) {
                                                    $associated_users[] = htmlspecialchars($u["username"]);
                                                }
                                            }
                                            if (!empty($associated_users)) {
                                                echo '<span class="badge bg-success">Favorited by: ' . implode(", ", $associated_users) . '</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Not Favorited</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- preserve search terms -->
            <input type="hidden" name="user_search" value="<?php echo htmlspecialchars($user_search); ?>">
            <input type="hidden" name="fighter_search" value="<?php echo htmlspecialchars($fighter_search); ?>">

            <?php if (!empty($users) && !empty($fighters)) : ?>
                <div class="mt-3">
                    <p class="text-muted">Checking a selected user + fighter combination that already exists will <strong>remove</strong> it. Combinations that don't exist will be <strong>added</strong>.</p>
                    <input type="submit" name="assign" value="Apply Associations" class="btn btn-success">
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </form>
</div>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>