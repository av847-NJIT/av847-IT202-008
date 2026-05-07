<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// attempt to apply
if (isset($_POST["users"], $_POST["roles"])) {
    $user_ids = $_POST["users"];
    $role_ids = $_POST["roles"];
    if (empty($user_ids) || empty($role_ids)) {
        flash("Both users and roles need to be selected", "warning");
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO UserRoles (user_id, role_id, is_active) VALUES (:uid, :rid, 1) 
        ON DUPLICATE KEY UPDATE is_active = !is_active");

        foreach ($user_ids as $uid) {
            foreach ($role_ids as $rid) {
                try {
                    $stmt->execute([":uid" => $uid, ":rid" => $rid]);
                    if ($stmt->rowCount() > 0) {
                        flash("Toggled role for user $uid and role $rid", "success");
                    } else {
                        flash("No changes made for user $uid and role $rid", "warning");
                    }
                } catch (PDOException $e) {
                    flash("There was an error toggling the role, please try again later", "danger");
                    error_log("Error toggling role for user $uid and role $rid: " . var_export($e->errorInfo, true));
                }
            }
        }
    }
}

// search for user by username
$users = [];
$active_roles = [];
$username = "";

if (isset($_POST["username"])) {
    $username = trim(se($_POST, "username", "", false));
    if (!empty($username)) {
        $active_roles = [];
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, description FROM Roles WHERE is_active = 1 LIMIT 10");
        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $active_roles = $results;
            }
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }

        $stmt = $db->prepare("SELECT Users.id, username, 
        (SELECT GROUP_CONCAT(name, ' (' , IF(ur.is_active = 1,'active','inactive') , ')') from 
        UserRoles ur 
        JOIN Roles on ur.role_id = Roles.id 
        WHERE ur.user_id = Users.id) as roles
        from Users WHERE username like :username");
        try {
            $stmt->execute([":username" => "%$username%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $users = $results;
            }
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
    } else {
        flash("Username must not be empty", "warning");
    }
}
?>

<h3>Assign Roles</h3>

<!-- Search Form -->
<form method="POST">
    <?php render_input(["type" => "text", "name" => "username", "id" => "username", "label" => "Username search", "rules" => ["required" => true]]); ?>
    <input type="hidden" name="action" value="fetch">
    <?php render_button(["text" => "Search", "type" => "submit"]); ?>
</form>

<!-- Toggle Form - hidden username field is inside the form tags -->
<form id="toggleForm" method="POST">
    <?php if (!empty($username)) : ?>
        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>" />
    <?php endif; ?>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Users</th>
            <th>Roles to Assign</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <!-- nested table for users -->
                <table class="table">
                    <?php if (empty($users) && !empty($username)) : ?>
                        <tr><td>No results available.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td>
                                <input form="toggleForm" id="user_<?php se($user, 'id'); ?>" type="checkbox" name="users[]" value="<?php se($user, 'id'); ?>" />
                                <label for="user_<?php se($user, 'id'); ?>"><?php se($user, "username"); ?></label>
                            </td>
                            <td><?php se($user, "roles", "No Roles"); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
            <td>
                <!-- nested data for roles -->
                <?php if (empty($active_roles) && !empty($username)) : ?>
                    <p>No roles available.</p>
                <?php endif; ?>
                <?php foreach ($active_roles as $role) : ?>
                    <div>
                        <input form="toggleForm" id="role_<?php se($role, 'id'); ?>" type="checkbox" name="roles[]" value="<?php se($role, 'id'); ?>" />
                        <label for="role_<?php se($role, 'id'); ?>"><?php se($role, "name"); ?></label>
                    </div>
                <?php endforeach; ?>
            </td>
        </tr>
    </tbody>
</table>

<!-- correctly associated with toggleForm -->
<button type="submit" form="toggleForm" class="btn btn-primary">Toggle Roles</button>

<?php
require_once(__DIR__ . "/../../../partials/flash.php");
?>