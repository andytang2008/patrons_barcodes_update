<?php

$outputFile = "all_patrons.xml";

// Open file for writing (use 'a' for append)
$fh = fopen($outputFile, 'a'); // 'a' = append mode
if (!$fh) {
    die("Cannot open file $outputFile for writing\n");
}

// Example usage:
$primaryIds = file('patronlist_no_barcode.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$primaryIds = array_slice($primaryIds, 1); // Skip header

foreach ($primaryIds as $userId) {
    $userId = trim($userId);
    if (!empty($userId)) {
        apicall_get($userId,$fh);
        usleep(500); // pause 0.5s to avoid API throttling
    }
}

fclose($fh);

echo "All contents appended successfully\n";



function apicall_get($userID,$fh) {
    $ch = curl_init();
    
    // Base URL template
    $baseUrl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}';
    $templateParamNames = array('{user_id}');
    $templateParamValues = array(rawurlencode($userID));
    $baseUrl = str_replace($templateParamNames, $templateParamValues, $baseUrl);
    
    // Query parameters
    $queryParams = array(
        'apikey' => 'Put your insitution API key here',  // <-- put your API key here
        'user_id_type' => 'all_unique',
        'view' => 'full',
        'expand' => 'none'
    );
    
    // Build URL
    $url = $baseUrl . '?' . http_build_query($queryParams);
    echo "GET URL: $url\n";
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
    
    // Optional: disable SSL verification if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Couldn\'t send request: ' . curl_error($ch));
    } else {
        $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resultStatus != 200) {
            die('Request failed: HTTP status code: ' . $resultStatus);
        }
    }
    
    curl_close($ch);

    // Save XML to current folder
   // $filePath = "user_" . $userID . ".xml";
   // file_put_contents($filePath, $response);
  //  echo "Saved user $userID XML\n";
	
	
	///foreach ($response as $content) {
	//			fwrite($fh, $content);
	//			// Optional: flush buffer to disk immediately
	//			fflush($fh);
//	}
	fwrite($fh, $response . "\n");
	fflush($fh);
	echo "Appended user $userID XML\n";
	
}

?>
