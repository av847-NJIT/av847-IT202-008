<?php
//include functions here so we can have it on every page that uses the nav bar
//that way we don't need to include so many other files on each page
//nav will pull in functions and functions will pull in db

// checking to see if domain has a port number attached (localhost)
$domain = $_SERVER["HTTP_HOST"];
if (strpos($domain, ":")) {
    // strip the port number if present
    $domain = explode(":", $domain)[0];
}
// used for public hosting like heroku
if ($domain != "localhost") {
    session_set_cookie_params([
        "lifetime" => 60 * 60, // this is cookie lifetime, not session lifetime
        "path" => "/project", // path to restrict cookie to; match your project folder (case sensitive)
        "domain" => $domain, // domain to restrict cookie to
        "secure" => true, // https only
        "httponly" => true, // javascript can't access
        "samesite" => "lax" // helps prevent CSRF, but allows normal navigation
    ]);
}
session_start();
require(__DIR__ . "/../lib/functions.php");
?>
<!-- include css and js files -->
<!-- Include Bootstrap CSS and JS before custom content so it can be reused or overriden -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="<?php get_url('styles.css', true); ?>">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="<?php get_url('helpers.js', true); ?>"></script>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <!-- Replace with your ucid -->
        <a class="navbar-brand text-uppercase" href="#">av847</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (is_logged_in()) : ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('landing.php', true); ?>">Landing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('profile.php', true); ?>">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('compare_fighters.php', true); ?>">Compare Fighters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('search_fighters.php', true); ?>">Search Fighters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('list_favorites.php', true); ?>">My Favorites</a>
                    </li>
                <?php endif; ?>
                <?php if (!is_logged_in()) : ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('login.php', true); ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('register.php', true); ?>">Register</a>
                    </li>
                <?php endif; ?>
                <?php if (has_role("Admin")) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Roles
                        </a>
                        <ul class="dropdown-menu">

                            <li><a class="dropdown-item" aria-current="page" href="<?php get_url('admin/create_role.php', true); ?>">Create Role</a>
                            </li>
                            <li><a class="dropdown-item" aria-current="page" href="<?php get_url('admin/list_roles.php', true); ?>">List Roles</a>
                            </li>
                            <li><a class="dropdown-item" aria-current="page" href="<?php get_url('admin/assign_roles.php', true); ?>">Assign Roles</a>
                            </li>

                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (has_role("Admin")) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Fighters
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" aria-current="page" href="<?php get_url('admin/create_fighter.php', true); ?>">Create Fighter</a>
                            </li>
                            <li><a class="dropdown-item" aria-current="page" href="<?php get_url('admin/list_fighters.php', true); ?>">List Fighter(s)</a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
                <!--
                <?php if (has_role("Admin")) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Companies
                        </a>
                        <ul class="dropdown-menu">
                            <li class="nav-item">
                                <a class="nav-link" aria-current="page" href="<?php //get_url('admin/create_company.php', true); 
                                                                                ?>">Create Company</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" aria-current="page" href="<?php //get_url('admin/list_companies.php', true); 
                                                                                ?>">List Companies</a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
                -->
                <?php if (is_logged_in()) : ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php get_url('logout.php', true); ?>">Logout</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>