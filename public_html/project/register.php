<?php
require(__DIR__ . "/../../partials/nav.php");

$form = [
    ["type" => "email", "id" => "email", "name" => "email", "label" => "Email", "rules" => ["required" => true]],
    [
        "type" => "text",
        "id" => "username",
        "name" => "username",
        "label" => "Username",
        "rules" => [
            "required" => true,
            "maxlength" => 30,
            "title" => "Lowercase, alphanumerical, and can only contain _ or -"
        ]
    ],
    ["type" => "password", "id" => "password", "name" => "password", "label" => "Password", "rules" => ["required" => true, "minlength" => 8]],
    ["type" => "password", "id" => "confirm", "name" => "confirm", "label" => "Confirm Password", "rules" => ["required" => true, "minlength" => 8]],
];
?>
<div class="container-fluid">
    <h3>Register</h3>
    <form onsubmit="return validate(this)" method="POST">
        <?php foreach ($form as $field): ?>
            <?php render_input($field); ?>
        <?php endforeach; ?>
        <?php render_button(["text" => "Register", "type" => "submit"]); ?>
    </form>
</div>

<script>
    function validate(form) {
        let email = form.email.value.trim();
        let username = form.username.value.trim();
        let pw = form.password.value;
        let con = form.confirm.value;

        if (!isValidEmail(email)) {
            flash("Invalid email address.", "danger");
            return false;
        }
        if (!isValidUsername(username)) {
            flash("Username must be lowercase, alphanumerical, and can only contain _ or -", "warning");
            return false;
        }
        if (!isValidPassword(pw)) {
            flash("Password must be at least 8 characters long.", "warning");
            return false;
        }
        if (!isValidConfirm(pw, con)) {
            flash("Passwords must match.", "warning");
            return false;
        }

        return true;
    }
</script>

<?php
if (isset($_POST["email"], $_POST["password"], $_POST["confirm"], $_POST["username"])) {

    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
    $confirm = se($_POST, "confirm", "", false);
    $username = se($_POST, "username", "", false);

    $hasError = false;

    if (empty($email)) {
        flash("Email must not be empty.", "danger");
        $hasError = true;
    }

    $email = sanitize_email($email);
    if (!is_valid_email($email)) {
        flash("Invalid email address.", "danger");
        $hasError = true;
    }
    if (!is_valid_username($username)) {
        flash("Username must be lowercase, alphanumerical, can only contain _ or -, and be between 3 to 30 characters", "danger");
        $hasError = true;
    }
    if (empty($password)) {
        flash("Password must not be empty.", "danger");
        $hasError = true;
    }
    if (empty($confirm)) {
        flash("Confirm password must not be empty.", "danger");
        $hasError = true;
    }
    if (!is_valid_password($password)) {
        flash("Password must be at least 8 characters long.", "danger");
        $hasError = true;
    }
    if (!is_valid_confirm($password, $confirm)) {
        flash("Passwords must match.", "danger");
        $hasError = true;
    }

    if (!$hasError) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Users (email, password, username) VALUES (:email, :password, :username)");
        try {
            $stmt->execute([':email' => $email, ':password' => $hashed_password, ':username' => $username]);
            flash("Successfully registered! You can now log in.", "success");
        } catch (PDOException $e) {
            users_check_duplicate($e);
        } catch (Exception $e) {
            flash("There was an error registering. Please try again.", "danger");
            error_log("Registration Error: " . var_export($e, true));
        }
    }
}
?>

<?php
require(__DIR__ . "/../../partials/flash.php");
reset_session();
?>