<?php

include_once "PeopleMediaApi.php";

try
{
    $apiUsername = "";
    $apiPasswd = "";
    
    $sid = 123; // SID
    $afId = "123456"; // Affiliate ID
    
    $pmApi = new PeopleMediaApi($apiUsername, $apiPasswd, $sid, $afId);
    
    $siteID = 105;
    $postalCode = "85286";
    
    // Perform a simple search without being logged in...
    $searchParams = new ExternalSearchParameters($siteID, "Female", $postalCode);
    $searchResults = $pmApi->SearchService->Search($searchParams);
    
    var_dump($searchResults);
    
    // Now lets validate an email address and register a new member...
    $username = "regtest05";
    $email = $username . "@testxp.com";
    $password = "test100";
    $birthDate = "1980-01-14";
    $gender = "Male";
    $mobilePhoneNumber = null;
    $seekingGender = null;
    
    $pmApi->RegistrationService->RegisterEmail($siteID, $email);
    
    $member = $pmApi->RegistrationService->RegisterMember($siteID, $email, $password, $birthDate, $gender, $postalCode, $username, $mobilePhoneNumber, $seekingGender);
    
    var_dump($member);
}
catch (UsernameTakenException $uEx)
{
    echo "The requested username is already in use, please try an alternative such as:<br/><br/>";
    foreach ($uEx->Alternatives as $alt){
        echo $alt . "<br/>";    
    }
    echo "<br/>";
    var_dump($uEx);
}
catch (DuplicateEmailAddressException $dEx) 
{
    echo "The requested email address is already in use";
    var_dump($dEx);
}
catch (UnderAgeException $uEx)
{
    echo "You must be at least 18 years of age to register";
    var_dump($uEx);
}
catch (Exception $ex)
{
    var_dump($ex);
}

?>
