<?php
require_once(__DIR__ . "/../../partials/nav.php");
if (!is_logged_in()) {
    die(header("Location: login.php"));
}
?>
<?php
$user_id = get_user_id();
$email = get_user_email();
$username = get_username();

// handle email/username update
if (isset($_POST["email"], $_POST["username"])) {
    $new_email = se($_POST, "email", null, false);
    $new_username = se($_POST, "username", null, false);
    $hasError = false;

    if (empty($new_email)) {
        flash("Email must not be empty.", "danger");
        $hasError = true;
    }
    $new_email = sanitize_email($new_email);
    if (!is_valid_email($new_email)) {
        flash("Invalid email address.", "danger");
        $hasError = true;
    }
    if (!is_valid_username($new_username)) {
        flash("Username must be lowercase, alphanumerical, and can only contain _ or -", "danger");
        $hasError = true;
    }
    if (($username != $new_username || $email != $new_email) && !$hasError) {
        $saved = false;
        $params = [":email" => $new_email, ":username" => $new_username, ":id" => $user_id];
        $db = getDB();
        $stmt = $db->prepare("UPDATE Users set email = :email, username = :username where id = :id");
        try {
            $stmt->execute($params);
            $updated_rows = $stmt->rowCount();
            if ($updated_rows === 0) {
                flash("No changes made", "warning");
            } else if ($updated_rows == 1) {
                flash("Profile saved", "success");
                $saved = true;
            } else {
                error_log("Unexpected number of rows updated: " . $updated_rows);
            }
        } catch (PDOException $e) {
            users_check_duplicate($e);
        } catch (Exception $e) {
            flash("An unexpected error occurred, please try again", "danger");
            error_log("Unexpected Error updating user details: " . var_export($e, true));
        }
        if ($saved) {
            $stmt = $db->prepare("SELECT email, username from Users where id = :id LIMIT 1");
            try {
                $stmt->execute([":id" => $user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $_SESSION["user"]["email"] = $user["email"];
                    $_SESSION["user"]["username"] = $user["username"];
                    $username = $user["username"];
                    $email = $user["email"];
                } else {
                    flash("User doesn't exist", "danger");
                    error_log("User doesn't exist");
                }
            } catch (PDOException $e) {
                flash("An unexpected error occurred, please try again", "danger");
                error_log("DB Error fetching user details: " . var_export($e, true));
            } catch (Exception $e) {
                flash("An unexpected error occurred, please try again", "danger");
                error_log("Unexpected Error fetching user details: " . var_export($e, true));
            }
        }
    }
}

// handle password update
if (isset($_POST["currentPassword"], $_POST["newPassword"], $_POST["confirmPassword"])) {
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    $can_update = !empty($current_password) && !empty($new_password) && !empty($confirm_password);
    if ($can_update) {
        if (!is_valid_confirm($new_password, $confirm_password)) {
            flash("New passwords don't match", "warning");
        } else {
            $hasError = false;
            if (!is_valid_password($new_password)) {
                flash("Password must be at least 8 characters long.", "danger");
                $hasError = true;
            }
            if (!$hasError) {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("SELECT password from Users where id = :id");
                    $stmt->execute([":id" => get_user_id()]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (isset($result["password"])) {
                        if (!password_verify($current_password, $result["password"])) {
                            flash("Current password is invalid", "warning");
                        } else {
                            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                            $query = "UPDATE Users set password = :password where id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->execute([":id" => get_user_id(), ":password" => $new_hash]);
                            $updated_rows = $stmt->rowCount();
                            if ($updated_rows === 0) {
                                flash("No changes made to password", "warning");
                            } else if ($updated_rows == 1) {
                                flash("Password updated successfully", "success");
                            } else {
                                error_log("Unexpected number of rows updated for password change: " . $updated_rows);
                            }
                        }
                    } else {
                        error_log("No password field in result");
                    }
                } catch (Exception $e) {
                    flash("Error processing password change", "danger");
                    error_log("Error processing password change: " . var_export($e, true));
                }
            }
        }
    }
}

// represent form as data
$form = [
    [
        "type" => "email",
        "id" => "email",
        "name" => "email",
        "label" => "Email",
        "value" => se($email, null, "", false),
        "rules" => ["required" => true]
    ],
    [
        "type" => "text",
        "id" => "username",
        "name" => "username",
        "label" => "Username",
        "value" => se($username, null, "", false),
        "rules" => ["required" => true]
    ],
    [
        "type" => "password",
        "id" => "cp",
        "name" => "currentPassword",
        "label" => "Current Password",
        "rules" => ["minlength" => 8]
    ],
    [
        "type" => "password",
        "id" => "np",
        "name" => "newPassword",
        "label" => "New Password",
        "rules" => ["minlength" => 8]
    ],
    [
        "type" => "password",
        "id" => "conp",
        "name" => "confirmPassword",
        "label" => "Confirm Password",
        "rules" => ["minlength" => 8]
    ]
];
?>

<div class="container-fluid">
    <h3>Profile</h3>
    <form method="POST" onsubmit="return validate(this);">
        <?php foreach ($form as $field): ?>
            <div class="mb-3">
                <?php render_input($field); ?>
            </div>
        <?php endforeach; ?>
        <?php render_button(["text" => "Update Profile", "type" => "submit"]); ?>
    </form>
</div>

<script>
    function validate(form) {
        let pw = form.newPassword.value;
        let con = form.confirmPassword.value;
        let cp = form.currentPassword.value;
        let isValid = true;

        if (pw && con && cp) {
            if (!isValidPassword(pw)) {
                isValid = false;
                flash("New Password must be at least 8 characters long", "danger");
            }
            if (!isValidPassword(con)) {
                isValid = false;
                flash("Confirm Password must be at least 8 characters long", "danger");
            }
            if (!isValidPassword(cp)) {
                isValid = false;
                flash("Current Password must be at least 8 characters long", "danger");
            }
            if (pw !== con) {
                flash("Password and Confirm password must match", "warning");
                isValid = false;
            }
        }

        return isValid;
    }
</script>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>