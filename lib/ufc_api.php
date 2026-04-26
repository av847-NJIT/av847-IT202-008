<?php

/**
 * This file is a wrapper for our API calls.
 * Here, each endpoint needed will be exposes as a function.
 * The function will take the parameters needed for the API call and return the result.
 * The function will also handle the API key and endpoint.
 * Requires the api_helper.php file and load_api_keys.php file.
 */

/**
 * Fetches the stock quote for a given symbol.
 */
function fetch_quote($symbol)
{
    $data = [];
    $slug = $symbol;
    $endpoint = "https://ufc-api5.p.rapidapi.com/api/v1/fighters/$slug/stats";
    $isRapidAPI = true;
    $rapidAPIHost = "ufc-api5.p.rapidapi.com";
    $result = get($endpoint, "UFC_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    //example of cached data to save the quotas, don't forget to comment out the get() if using the cached data for testing
    /* $result = ["status" => 200, "response" => '{
        "Global Quote": {
            "01. symbol": "MSFT",
            "02. open": "420.1100",
            "03. high": "422.3800",
            "04. low": "417.8400",
            "05. price": "421.4400",
            "06. volume": "17861855",
            "07. latest trading day": "2024-04-02",
            "08. previous close": "424.5700",
            "09. change": "-3.1300",
            "10. change percent": "-0.7372%"
        }
    }'];*/
    error_log("API Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
    $transformedResult = [];
    // transform data to match our DB structure
   if (isset($result["data"])) {
        $fighter = $result["data"];

        $transformedResult = [
            "name" => $fighter["name"],
            "striking_accuracy_pct" => $fighter["striking_accuracy_pct"],
            "takedown_accuracy_pct" => $fighter["takedown_accuracy_pct"],
            "sig_strikes_landed" => $fighter["sig_strikes_landed"],
            "sig_str_defense_pct" => $fighter["sig_str_defense_pct"],
            "takedown_defense_pct" => $fighter["takedown_defense_pct"],
            "slug" => $fighter["slug"]
        ];
    }
    return $transformedResult;
}
function search_fighters($search)
{
    $data = ["symbol" => $_GET["symbol"], "datatype" => "json"];
    $slug = $_GET["symbol"];
    $endpoint = "https://ufc-api5.p.rapidapi.com/api/v1/fighters/$slug/stats";
    $isRapidAPI = true;
    $rapidAPIHost = "ufc-api5.p.rapidapi.com";
    $result = get($endpoint, "UFC_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    //example of cached data to save the quotas, don't forget to comment out the get() if using the cached data for testing
    /* $result = ["status" => 200, "response" => {
        "bestMatches": [
            {
                "1. symbol": "TESTF", 
                "2. name": "Test Foreign Security",
                "3. type": "Equity",  
                "4. region": "United States",
                "5. marketOpen": "09:30",
                "6. marketClose": "16:00",
                "7. timezone": "UTC-04",
                "8. currency": "USD", 
                "9. matchScore": "0.8889"
            },
            {
                "1. symbol": "TESTJ", 
                "2. name": "Test Security J1",
                "3. type": "Equity",  
                "4. region": "United States",
                "5. marketOpen": "09:30",
                "6. marketClose": "16:00",
                "7. timezone": "UTC-04",
                "8. currency": "USD", 
                "9. matchScore": "0.8889"
            },
            {
                "1. symbol": "TESTM", 
                "2. name": "Demo Test Security",
                "3. type": "Equity",  
                "4. region": "United States",
                "5. marketOpen": "09:30",
                "6. marketClose": "16:00",
                "7. timezone": "UTC-04",
                "8. currency": "USD", 
                "9. matchScore": "0.8889"
            },
            {
                "1. symbol": "TESTT", 
                "2. name": "Test Security T",
                "3. type": "Equity",  
                "4. region": "United States",
                "5. marketOpen": "09:30",
                "6. marketClose": "16:00",
                "7. timezone": "UTC-04",
                "8. currency": "USD", 
                "9. matchScore": "0.8889"
            }
        ]
    }'];*/
    error_log("API Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
    // transform data
    if (isset($result["bestMatches"])) {
        $result = $result["bestMatches"];
        $transformedResult = [];
        foreach ($result as $r) {

            // fixed keys
            foreach ($r as $k => $v) {
                // "1. symbol"
                // ["1.", "symbol"]
                // "symbol"
                $nk = str_replace(" ", "_", explode(" ", $k, 2)[1]);
                $r[$nk] = $v;
                unset($r[$k]);
            }
            if (strlen($r["symbol"]) > 6) {
                continue;
            }
            // map/extract desired information
            $data = [
                "name" => $r["name"],
                "striking_accuracy_pct" => $r["striking_accuracy_pct"],
                "takedown_accuracy_pct" => $r["takedown_accuracy_pct"],
                "sig_str_landed" => $r["sig_str_landed"],
                "sig_str_defense_pct" => $r["sig_str_defense_pct"],
                "takedown_defense_pct" => $r["takedown_defense_pct"],
                "is_api" => 1
            ];
            array_push($transformedResult, $data);
        }
    }
    return $transformedResult;
}
