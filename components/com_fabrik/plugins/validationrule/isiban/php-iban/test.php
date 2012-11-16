<?

require_once('php-iban.php');

# display registry contents
#print_r($_iban_registry);

# loop through the registry's examples, validating
foreach($_iban_registry as $country) {
 # get example iban
 $iban = $country['iban_example'];

 # output properties one by one
 print "[$iban]\n";
 print " - country  " . iban_get_country_part($iban) . "\n";
 print " - checksum " . iban_get_checksum_part($iban) . "\n";
 print " - bban     " . iban_get_bban_part($iban) . "\n";
 print " - bank     " . iban_get_bank_part($iban) . "\n";
 print " - branch   " . iban_get_branch_part($iban) . "\n";
 print " - account  " . iban_get_account_part($iban) . "\n";
 
 # output all properties
 #$parts = iban_get_parts($iban);
 #print_r($parts);
 
 # verify
 print "\nChecking validity... ";
 if(verify_iban($iban)) {
  print "IBAN $iban is valid.\n";
 }
 else {
  print "IBAN $iban is invalid.\n";
 }
 print "\n";
}

?>
