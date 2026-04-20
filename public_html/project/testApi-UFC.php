<?php
require(__DIR__ . "/../../partials/nav.php");

$result = [];
if (isset($_GET["symbol"])) {
    //function=GLOBAL_QUOTE&symbol=MSFT&datatype=json
    $data = ["symbol" => $_GET["symbol"], "datatype" => "json"];

    //getFighterStats
    // UCID: av847
    // Date: 04/20/26
    
    $slug = $_GET["symbol"];
    $endpoint = "https://ufc-api5.p.rapidapi.com/api/v1/fighters/$slug/stats";
    $isRapidAPI = true;
    $rapidAPIHost = "ufc-api5.p.rapidapi.com";
    $result = get($endpoint, "UFC_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    //example of cached data to save the quotas, don't forget to comment out the get() if using the cached data for testing
    /* $result = ["status" => 200, "response" => '{
        array (
    'name' => 'Michael Morales',
    'slug' => 'michael-morales',
    'striking_accuracy_pct' => 49,
    'takedown_accuracy_pct' => 42,
    'sig_strikes_landed' => 409,
    'sig_strikes_attempted' => 834,
    'takedowns_landed' => NULL,
    'takedowns_attempted' => 12,
    'sig_str_landed_per_min' => 5.68,
    'sig_str_absorbed_per_min' => 3.26,
    'takedown_avg_per_15min' => 1.04,
    'submission_avg_per_15min' => NULL,
    'sig_str_defense_pct' => 54,
    'takedown_defense_pct' => 90,
    'knockdown_avg' => 1.46,
    'avg_fight_time' => '09:00',
    'sig_strikes_by_position' => 
    array (
        'standing' => NULL,
        'clinch' => NULL,
        'ground' => NULL,
    ),
    'sig_strikes_by_target' => 
    array (
        'head' => NULL,
        'body' => NULL,
        'leg' => NULL,
    ),
    'win_by_method' => 
    array (
        'ko_tko' => 14,
        'decision' => 4,
        'submission' => 1,
    ),
    )                
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
    <h1>Fighter Stats</h1>
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
