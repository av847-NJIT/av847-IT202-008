<?php
require_once(__DIR__ . "/../../../lib/db.php"); ?>

<?php
// don't edit - this
$expected_fields = ["task", "due", "assigned"];
$diff = array_diff($expected_fields, array_keys($_GET));

if (empty($diff)) {

    // data variables, don't edit
    $task = $_GET["task"];
    $due = $_GET["due"]; //hint: must be a valid MySQL date format
    $assigned = $_GET["assigned"]; // Must be "self" or a valid format (not empty or equivalent)

    $is_valid = true;
    // TODO Validate the incoming data for correct format based on the SQL table definition.
    // When not valid, provide a user-friendly message of what specifically was wrong and set $is_valid to false.
    // Assigned should check for "self" if a valid format/value isn't provided.
    // Start validations
    // Step 1: If, if else statements to validate incoming data
    //       Provide user friendly message if message is not valid 
    // Task validations
    $user_message = [];
    if(empty(trim($task))){
        $user_message[] = "Task description is empty. Please fill out description.";
        $is_valid = false;
    }
    
    elseif(strlen(trim($task)) > 128){
        $user_message[]= "Task description must be less than 128 characters";
        $is_valid = false;
    }

    // due validation
    $date = DateTime::createFromFormat('Y-m-d', $due);
    if(!$date || $date->format('Y-m-d') != $due){
        $user_message[] = "The due date is not in a valid date format (YYYY-MM-DD";
        $is_valid = false;
    }

    // assigned validations
    if(empty(trim($assigned))){
        $assigned = "self";
    }
    if(strlen(trim($assigned)) > 60){
        $user_message[] = "The assigned value must be 60 characters or fewer";
        $is_valid = false;
    }
    // End validations

    if ($is_valid) {
        /*
        Design a query to insert the incoming data to the proper columns.
        Ensure valid and proper PDO named placeholders are used.
        https://phpdelusions.net/pdo
        */
        $query = "INSERT INTO M4_TODOS (task, due, assigned) VALUES(:task, :due, :assigned)";
        $params = [ ":task" =>trim($task), ":due" => $due,":assigned => trim($assigned)"]; // Apply the proper PDO placeholder to variable mapping here
        try {
            $db = getDB();
            $stmt = $db->prepare($query);
            $r = $stmt->execute($params);
            if ($r) {
                echo "Inserted new Todo with id " . $db->lastInsertId();
            } else {
                echo "Failed to insert";
            }
        } catch (PDOException $e) {
            // extra credit
            // check if the exception was related to a unique constraint
            // provide an appropriate user-friendly message for this scenario
            // Otherwise show the default message below
            echo "There was an error inserting the record; check the logs (terminal)";
            error_log("Insert Error: " . var_export($e, true)); // shows in the terminal
        }
    } else {
        error_log("Creation input wasn't valid");
    }
}
?>
<html>

<body>
    <?php require_once(__DIR__ . "/../nav.php"); ?>
    <section>
        <h2>Create ToDo </h2>
        <form method = "GET">
            <!-- design the form with proper labels and input fields with the correct types based on the SQL table.
             Wrap each label/input pair in a div tag.
             For "Assigned" ensure the default value is "self". -->
                <div>
                    <label for= "task"> Task </label>
                    <input type = "text" id="task" name="task"
                        maxlength = "128"
                        value = "<?= htmlspecialchars($_GET['task'] ?? '') ?>" />   
                </div>

                <div> 
                    <label for= "due"> Due Date </label>
                    <input type = "date" id= "due" name = "due"
                        value = "<?= htmlspecialchars($_GET['due'] ?? '') ?>" />
                </div>

                <div> 
                    <label for="assigned" > Assigned </label>
                    <input type = "text" id = "assigned" name = "assigned"
                        maxlength = "60"
                        value = "<?= htmlspecialchars($_GET['assigned'] ?? 'self') ?>" />
                </div>
            <div>

                <input type="submit" />
            </div>
        </form>
    </section>
</body>
</body>

</html>