<?php

include_once "PeopleMediaApi.php";

try
{
    $apiUsername = "";
    $apiPasswd = "";
    
    $sid = 305; // SID
    $afId = "123456"; // Affiliate ID
    
    $pmApi = new PeopleMediaApi($apiUsername, $apiPasswd, $sid, $afId);
    
    $siteID = 105;
    $username = "regtest05";
    $email = $username . "@testxp.com";
    $password = "test100";
    $birthDate = "1980-01-14";
    $gender = "Male";
    $postalCode = "85286";
    $mobilePhoneNumber = null;
    $seekingGender = null;
    
    $pmApi->RegisterEmail($siteID, $email, $sid);
    
    $member = $pmApi->RegisterMember($siteID, $email, $password, $birthDate, $gender, $postalCode, $username, $mobilePhoneNumber, $seekingGender, $sid, $affId);
    
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