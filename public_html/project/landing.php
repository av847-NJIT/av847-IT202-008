<?php
require(__DIR__ . "/../../partials/nav.php");
error_log("Session: " . var_export($_SESSION, true));
?>
<div class="container mt-5 text-center">
    <h1 class="display-4 mb-3">Home Page</h1>

    <p class="lead text-muted w-75 mx-auto">
        Explore your favorite UFC fighter's offensive and defensive stats and compare them with others!
    </p>

    <?php if (is_logged_in(true)): ?>
        <p class="fw-semibold">
            Welcome, <?php echo get_username() ?>!
        </p>
    <?php endif;?>
</div>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>