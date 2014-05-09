#PHP Client Library



##Getting Started
#### - Initializing client library

```php
$apiUsername = "[ApiUserName]";
$apiPasswd = "[ApiPassWd]";
    
$sid = 123; // SID
$afId = "123456"; // Affiliate ID
    
$pmApi = new PeopleMediaApi($apiUsername, $apiPasswd, $sid, $afId);
```
##Registrations
#### - Registering and Email Address
Registering an email address performs basic validation and ensures that the email address is not already in use.
```php
$siteID = 105;
$email = "test@testxp.com";
$pmApi->RegistrationService->RegisterEmail($siteID, $email);
```
#### - Registering a New Member
```php
$siteID = 105;
$username = "regtest01";
$email = $username . "@testxp.com";
$password = "test100";
$postalCode = "85286";
$birthDate = "1980-07-14";
$gender = "Male";
$mobilePhoneNumber = null;
$seekingGender = null;

$member = $pmApi->RegistrationService->RegisterMember($siteID, $email, $password, $birthDate, $gender, $postalCode, $username, $mobilePhoneNumber, $seekingGender);

var_dump($member);
```
##Search
#### - External Search
An external search occurs when a member has not been registered, or is not logged in, and a search is performed.
```php
$siteID = 105;
$postalCode = "85286";

// Perform a simple search without being logged in...
$searchParams = new ExternalSearchParameters($siteID, "Female", $postalCode);
$searchResults = $pmApi->SearchService->Search($searchParams);

var_dump($searchResults);
```
