<?php

//Andy: This code is used to grab barcode which matches each patron's primary_id. and put barcode paragraph into each patron's xml file part.
$inputFile = 'all_patrons.xml'; // your original XML file with users
$barcodeFile = 'library_barcode_full_list.txt';
$outputFile = 'processed_results.xml';

// Load barcodes into an associative array
$barcodes = [];
$lines = file($barcodeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    list($primary_id, $barcode) = explode('|', $line);
    $barcodes[$primary_id] = $barcode;
}

// Read the input file
$xmlData = file_get_contents($inputFile);

// Split into individual user records using the XML declaration as delimiter
$users = preg_split('/<\?xml.*?\?>/', $xmlData, -1, PREG_SPLIT_NO_EMPTY);

// Clear output file first
file_put_contents($outputFile, ''); 

foreach ($users as $userXml) {
    $userXml = trim($userXml);
    if (empty($userXml)) continue;

    // Load user XML
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($userXml);

    // Get primary_id
    $primaryIdNodes = $dom->getElementsByTagName('primary_id');
    if ($primaryIdNodes->length == 0) continue;
    $primaryId = $primaryIdNodes->item(0)->nodeValue;

    // Check if barcode exists
    if (isset($barcodes[$primaryId])) {
        $barcode = $barcodes[$primaryId];

        // Remove existing <user_identifiers>
        $userIdentifiers = $dom->getElementsByTagName('user_identifiers');
        if ($userIdentifiers->length > 0) {
            $userIdentifiersNode = $userIdentifiers->item(0);
            while ($userIdentifiersNode->firstChild) {
                $userIdentifiersNode->removeChild($userIdentifiersNode->firstChild);
            }
        } else {
            $userIdentifiersNode = $dom->createElement('user_identifiers');
            $dom->documentElement->appendChild($userIdentifiersNode);
        }

        // Add new barcode node
        $userIdentifierNode = $dom->createElement('user_identifier');
        $userIdentifierNode->setAttribute('segment_type', 'Internal');

        $idTypeNode = $dom->createElement('id_type', 'BARCODE');
        $idTypeNode->setAttribute('desc', 'Barcode');
        $valueNode = $dom->createElement('value', $barcode);
        $noteNode = $dom->createElement('note', 'Added by Ex Libris 2025-10-28');
        $statusNode = $dom->createElement('status', 'ACTIVE');

        $userIdentifierNode->appendChild($idTypeNode);
        $userIdentifierNode->appendChild($valueNode);
        $userIdentifierNode->appendChild($noteNode);
        $userIdentifierNode->appendChild($statusNode);

        $userIdentifiersNode->appendChild($userIdentifierNode);

        echo "✅ Added barcode for user: $primaryId\n";

        // Save user XML with XML declaration
        $xmlOutput = $dom->saveXML($dom->documentElement); // only the <user> element
        $xmlOutput = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" . $xmlOutput;
        file_put_contents($outputFile, $xmlOutput . "\n", FILE_APPEND);

    } else {
        echo "⚠️  No barcode for user: $primaryId\n";
    }
}

echo "All users with barcode processed into $outputFile\n";
?>
