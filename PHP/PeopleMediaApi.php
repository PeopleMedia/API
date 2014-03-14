<?php

class DuplicateEmailAddressException extends Exception
{
}

class UnderAgeException extends Exception
{
}

class UsernameTakenException extends Exception
{
    public $Alternatives = array();
    
    function __construct($message = "", $code = 0, $alternatives, $previous = NULL) 
    {
        $this->Alternatives = $alternatives;
        
        parent::__construct($message, $code, $previous);
    }
}

class WsseAuthHeader extends SoapHeader 
{
    private $wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    function __construct($user, $pass, $ns = null) 
    {    
        if ($ns) 
        {        
            $this->wss_ns = $ns;    
        }    

        $security_sv = new SoapVar(
        '<o:Security xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" s:mustUnderstand="1">
            <o:UsernameToken u:Id="uuid-' . 'e1bdb25f-c13b-45f6-a785-4d6e01056eae-2938' . '">
                <o:Username>' . htmlspecialchars($user) . '</o:Username>
                <o:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($pass) . '</o:Password>
            </o:UsernameToken>
        </o:Security>', 
        XSD_ANYXML, NULL, $this->wss_ns, 'Security', $this->wss_ns);    

        parent::__construct($this->wss_ns, 'Security', $security_sv, true);
    }
}

class PeopleMediaApi {
    
    public $UserName = "";
    public $Password = "";
    
    public $SourceId = "";
    public $AffiliateId = "";
    
    private $Client = null;
    
    function __construct($username, $passwd, $sourceId = null, $affiliateId = null) {
        $this->UserName = $username;
        $this->Password = $passwd;
        
        $this->SourceId = $sourceId;
        $this->AffiliateId = $affiliateId;
        
        $this->Client = $this->CreateClient($this->UserName, $this->Password);
    }

    private function CreateClient()
    {
        $options = array( 
            'soap_version'    => SOAP_1_1, 
            'exceptions'      => true, 
            'trace'           => 1, 
            'wsdl_local_copy' => true
        );
        
        $wsse_header = new WsseAuthHeader($this->UserName, $this->Password);    
        
        $client = new SoapClient('https://api.peoplemedia.com/v2/RegistrationService.svc?singleWsdl', $options); 
        $client->__setSoapHeaders(array($wsse_header));
        
        $client->__setLocation("https://api.peoplemedia.com/v2/RegistrationService.svc/SOAP");
        
        return $client;   
    }
    
    private function HandleSoapFault(SoapFault $soapFault)
    {
        $details = $soapFault->detail;
        foreach ($details as $k => $v) {
            switch ($v->Code)
            {
                case "1400": //UsernameTaken
                    throw new UsernameTakenException($v->Title, $v->Code, $v->AdditionalDetails->Alternatives->string);
                case "1410": //DuplicateEmailAddress
                    throw new DuplicateEmailAddressException($v->Title, $v->Code);
                case "1300": //UnderMinimumAge
                    throw new UnderAgeException($v->Title, $v->Code);
                default:
                    throw new Exception($v->Title, $v->Code);
            }
            break;
        }
        
        throw new Exception("Unknown error from People Media Api");
    }
    
    public function RegisterEmail($communityId, $emailAddress) 
    {
        try
        {
            try 
            {
                $result = $this->Client->RegisterMarketingCapture(array(
                    "details" => 
                    array(
                        "EmailAddress"         => $emailAddress,
                        "Community"            => array(
                            "Identifier"       => $communityId
                        ),
                        "MarketingSource"      => array(
                            "SourceIdentifier" => $this->SourceId
                        )
                    )
                ));
                
                return $result;
            }
            catch (SoapFault $sf)
            {
                $this->HandleSoapFault($sf);
            }
        }
        catch(Exception $e) 
        { 
            throw $e;
        }
    }

    public function RegisterMember($communityId, $emailAddress, $password, $birthDate, $gender, $postalCode, $nickName, $mobilePhoneNumber = NULL, $seekingGender = NULL) 
    {
        try
        {
            try 
            {
                $params = array();
                if ($this->SourceId != null || $this->AffiliateId != null)
                {
                    $params["RegistrationSource"] = array();
                    if ($affiliateId != null)
                        $params["RegistrationSource"]["AffiliateIdentifier"] = $this->AffiliateId;
                    
                    if ($sourceId != null)
                        $params["RegistrationSource"]["SourceIdentifier"] = $this->SourceId;
                }
                
                $params["RequiredParameters"] = array(
                            "BirthDate"            => $birthDate . "T00:00:00",
                            "Community"            => array("Identifier" => $communityId),
                            "EmailAddress"         => $emailAddress,
                            "Gender"               => $gender,
                            "Location"             => array("PostalCode" => $postalCode),
                            "NickName"             => $nickName,
                            "Password"             => $password
                        );
                
                if ($mobilePhoneNumber != null || $seekingGender != null)
                {
                    $params["OptionalParameters"] = array();
                    if ($mobilePhoneNumber != null)
                        $params["OptionalParameters"]["MobilePhoneNumber"] = $mobilePhoneNumber;

                    if ($seekingGender != null)
                        $params["OptionalParameters"]["SeekingGender"] = $seekingGender;
                }
                
                $result = $this->Client->RegisterNewMember(array("registrationParameters" => $params));
                
                return $result;
            }
            catch (SoapFault $sf)
            {
                $this->HandleSoapFault($sf);
            }
        }
        catch(Exception $e) 
        { 
            throw $e;
        }
    }
    
}