<?php
require(__DIR__ . "/../../partials/nav.php");

$result = [];
if (isset($_GET["symbol"])) {
    //function=GLOBAL_QUOTE&symbol=MSFT&datatype=json
    $data = ["symbol" => $_GET["symbol"], "datatype" => "json"];

    //getFighterHistory
    // UCID: av847
    // Date: 04/20/26
    
    $slug = $_GET["symbol"];
    $endpoint = "https://ufc-api5.p.rapidapi.com/api/v1/fighters/" . $slug . "/history";
    $isRapidAPI = true;
    $rapidAPIHost = "ufc-api5.p.rapidapi.com";
    $result = get($endpoint, "UFC_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    //example of cached data to save the quotas, don't forget to comment out the get() if using the cached data for testing
    /* $result = ["status" => 200, "response" => '{
    array (
    'name' => 'Michael Morales',
    'slug' => 'michael-morales',
    'fights' => 
    array (
        0 => 
        array (
        'result' => 'Win',
        'opponent' => 'Brady',
        'opponent_slug' => 'sean-brady',
        'event' => '',
        'date' => 'Nov. 15, 2025',
        'method' => 'KO/TKO',
        'round' => '1',
        'time' => '3:27',
        ),
        1 => 
        array (
        'result' => 'Win',
        'opponent' => 'Burns',
        'opponent_slug' => 'gilbert-burns',
        'event' => '',
        'date' => 'May. 17, 2025',
        'method' => 'KO/TKO',
        'round' => '1',
        'time' => '3:39',
        ),
        2 => 
        array (
        'result' => 'Win',
        'opponent' => 'Magny',
        'opponent_slug' => 'neil-magny',
        'event' => '',
        'date' => 'Aug. 24, 2024',
        'method' => 'KO/TKO',
        'round' => '1',
        'time' => '4:39',
        ),
    ),
)                
}'];*/
    error_log("Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}
?>
<div class="container-fluid">
    <h1>Fighter History</h1>
    <p>Remember, we typically won't be frequently calling live data from our API, this is merely a quick sample. We'll want to cache data in our DB to save on API quota.</p>
    <form>
        <div>
            <label>Fighter</label>
            <input name="symbol" />
            <input type="submit" value="Fetch Info" />
        </div>
    </form>
    <div class="row ">
        <?php if (isset($result)) : ?>
            <?php foreach ($result as $stock) : ?>
                <pre>
                    <?php var_export($stock); ?>
                </pre>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
require(__DIR__ . "/../../partials/flash.php");
