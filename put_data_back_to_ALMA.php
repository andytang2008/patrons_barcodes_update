<?php

//Andy This is the final code extracting each patron's xml file (which already added barcode part) and write them back to ALMA by using curl put API.
//10-2025

$resultsFile = 'processed_results.xml'; // XML file with patrons
$almaApiKey = 'Put your institution API key here'; // replace with your Alma API key
$almaBaseUrl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users'; // base URL

// Load results.xml
$xmlData = file_get_contents($resultsFile);

// Split into individual <user> elements using XML declaration as delimiter
$users = preg_split('/<\?xml.*?\?>/', $xmlData, -1, PREG_SPLIT_NO_EMPTY);

foreach ($users as $userXml) {
    $userXml = trim($userXml);
    if (empty($userXml)) continue;

    // Make sure XML declaration is present
    $userXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . $userXml;

    // Load XML to get primary_id
    $dom = new DOMDocument();
    $dom->loadXML($userXml);
    $primaryIdNodes = $dom->getElementsByTagName('primary_id');
    if ($primaryIdNodes->length == 0) {
        echo "❌ Could not find primary_id\n";
        continue;
    }
    $primaryId = $primaryIdNodes->item(0)->nodeValue;

    // Prepare cURL PUT
    $url = $almaBaseUrl . '/' . $primaryId . '?user_id_type=all_unique&send_pin_number_letter=false&recalculate_roles=false&registration_rules=false&apikey=' . $almaApiKey;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/xml",
        "Accept: application/xml"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $userXml);

    // For debugging HTTP 0
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // temporary for debugging SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $error = curl_error($ch);
        echo "❌ Failed to update user: $primaryId | cURL Error: $error\n";
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ Successfully updated user: $primaryId | HTTP $httpCode\n";
    } else {
        echo "❌ Failed to update user: $primaryId | HTTP $httpCode\n";
        echo "Response: $response\n";
    }

    curl_close($ch);
}

echo "All users processed.\n";
?>
