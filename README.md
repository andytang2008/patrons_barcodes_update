<h2>Updating patron’s barcodes by using API</h2>
In ALMA, assigning the correct user barcodes in the Identifiers tab of each user profile is essential. It ensures that students can successfully access library systems via SAML and Shibboleth authentication.
However, due to certain special circumstances, workflows or interdepartmental coordination may not always function smoothly. As a result, barcode data may fail to synchronize properly with user data delivery. In such cases, either Ex Libris support may need to intervene to resolve the issue, or systems staff can use the API to perform a batch update of barcodes. Ultimately, the long-term solution lies in ensuring seamless coordination among all departments involved in the workflow.

Step 1: Use ALMA Analytics to generate a complete list of all users who either do not have a barcode or have incorrect barcode data. Then, export the Primary Identifier column from this list and save it to a file named patronlist_no_barcode.txt.
 
Step 2:  Use the get_patronlist_without_barcode.php script to read the Primary Identifier values from the patronlist_no_barcode.txt file. Then, retrieve each user’s XML data from Alma using the Users GET API, and save all retrieved records into a single file named all_patrons.xml.
Please note that within each user’s XML record, the <user_identifiers/> tag indicates that the barcode field is empty.

 
 
Step 3: Run the inject_barcode_into_patronfile.php script to process each employee’s XML data. The script reads the employee ID, searches for the matching ID in the library_barcode_full_list.txt file (which typically contains both employee IDs and their corresponding barcodes), retrieves the corresponding barcode, inserts it into the employee’s XML record, and writes the updated data to a new file named processed_results.xml.
 
 

Step 4: Use the User PUT API to upload all updated patron XML data from processed_results.xml back into Alma.
 
Be careful: when writing back library faculty data using the PUT API, you may encounter an error such as:
“The user has a role with code 214 (an internal Alma role ID). This role type requires an extra parameter, called ServiceUnit, which is missing from your <user_role> block.”
However, when checking in Alma, no roles may appear to be missing a location parameter, which can be confusing. If library staff or faculty members have multiple assigned roles, the script may fail for them. For students with only the “Patron” role, the script typically runs without problems.

The code has been uploaded to https://github.com/andytang2008/patrons_barcodes_update
I hope it’s helpful.

 Andy
