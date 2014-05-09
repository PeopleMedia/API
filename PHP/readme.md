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
$pmApi->RegistrationService->RegisterEmail($siteID, $email);
```
