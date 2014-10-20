<?php

class AuthenticationException extends Exception
{
}

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
        '<o:Security xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" s:mustunderstand="1">
            <o:UsernameToken u:Id="uuid-' . 'e1bdb25f-c13b-45f6-a785-4d6e01056eae-2938' . '">
                <o:Username>' . htmlspecialchars($user) . '</o:Username>
                <o:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($pass) . '</o:Password>
            </o:UsernameToken>
        </o:Security>', 
        XSD_ANYXML, NULL, $this->wss_ns, 'Security', $this->wss_ns);    

        parent::__construct($this->wss_ns, 'Security', $security_sv, true);
    }
}

abstract class PeopleMediaService {
    
    protected $Api;
    protected $Client;
    
    function __construct(PeopleMediaApi $api) {
        $this->Api = $api;
        $this->Client = $this->Api->CreateClient($this);
    }

    public abstract function GetWsdlUrl();
    public abstract function GetServiceUrl();
}

class Response {
    public $ResponseID = null;
    
    function __construct($responseID)
    {
        $this->ResponseID = $responseID;
    }
}

class Question {
    
    public $QuestionID = NULL;
    public $Responses = array();
    
    function __construct($questionID, $responseID)
    {
        $this->QuestionID = $questionID;
        array_push($this->Responses, new Response($responseID));
    }
}

class PeopleMediaRegistrationService extends PeopleMediaService {
    public function GetWsdlUrl() {
        return 'https://api.peoplemedia.com/v2/RegistrationService.svc?singleWsdl';
    }
    public function GetServiceUrl() {
        return "https://api.peoplemedia.com/v2/RegistrationService.svc/SOAP";
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
                    $this->Api->HandleSoapFault($soapFault);
            }
            break;
        }
        
        throw new Exception("Unknown error from People Media Registration Api");
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
                            "SourceIdentifier" => $this->Api->SourceId
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

    public function RegisterMember($communityId, $emailAddress, $password, $birthDate, $gender, $postalCode, $nickName, $mobilePhoneNumber = NULL, $seekingGender = NULL, $questionResponses = NULL) 
    {
        try
        {
            try 
            {
                $params = array();
                if ($this->Api->SourceId != null || $this->Api->AffiliateId != null)
                {
                    $params["RegistrationSource"] = array();
                    if ($this->Api->AffiliateId != null)
                        $params["RegistrationSource"]["AffiliateIdentifier"] = $this->Api->AffiliateId;
                    
                    if ($this->Api->SourceId != null)
                        $params["RegistrationSource"]["SourceIdentifier"] = $this->Api->SourceId;
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
                
                if ($mobilePhoneNumber != null || $seekingGender != null || $questionResponses != null)
                {
                    $params["OptionalParameters"] = array();
                    if ($mobilePhoneNumber != null)
                        $params["OptionalParameters"]["MobilePhoneNumber"] = $mobilePhoneNumber;

                    if ($seekingGender != null)
                        $params["OptionalParameters"]["SeekingGender"] = $seekingGender;
                    
                    if ($questionResponses != null)
                    {
                       $params["OptionalParameters"]["QuestionsAndResponses"] = $questionResponses;
                    }
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

class Location {
}

abstract class SearchParameters {
    public abstract function ToArray();
}

class ExternalSearchParameters extends SearchParameters {
    public $CommunityID;
    public $Gender;
    public $PostalCode;
    
    function __construct($communityId, $searchingGender, $postalCode) {
        $this->CommunityID = $communityId;
        $this->Gender = $searchingGender;
        $this->PostalCode = $postalCode;
    }
    
    public function ToArray() {
        return array(
            "Gender"         => $this->Gender,
            "Community"            => array(
                "Identifier"       => $this->CommunityID
            ),
            "Location"      => array(
                "PostalCode" => $this->PostalCode
            )
        );
    }
}

class PeopleMediaSearchService extends PeopleMediaService {
    public function GetWsdlUrl() {
        return 'https://api.peoplemedia.com/v2/SearchService.svc?singleWsdl';
    }
    public function GetServiceUrl() {
        return 'https://api.peoplemedia.com/v2/SearchService.svc/SOAP';
    }
    
    private function HandleSoapFault(SoapFault $soapFault)
    {
        $details = $soapFault->detail;
        foreach ($details as $k => $v) {
            switch ($v->Code)
            {
                default:
                    $this->Api->HandleSoapFault($soapFault);
            }
            break;
        }
        
        throw new Exception("Unknown error from People Media Search Api");
    }
    
    public function Search(SearchParameters $parameters, $pageNumber = 1, $resultsPerPage = 20) 
    {
        try
        {
            try 
            {                
                $result = $this->Client->SearchMembers(array(
                    "searchParameters" => array(
                        "AdditionalParameters" => new SoapVar($parameters->ToArray(), SOAP_ENC_OBJECT, get_class($parameters), "http://schemas.datacontract.org/2004/07/SocialNetworking.Entities.Search"),
                        "PageNumber" => $pageNumber,
                        "ResultsPerPage" => $resultsPerPage,
                        "SearchIdentifier" => $searchId
                    )
                ));
                
                $result = $result->SearchMembersResult;
                
                $result->Members = $result->Members->CommunityMember;
                
                if ($result->TotalCount == -1)
                    $result->TotalCount = null;
                
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

class PeopleMediaApi {
    
    public $UserName = "";
    public $Password = "";
    
    public $SourceId = "";
    public $AffiliateId = "";
    
    public $RegistrationService = null;
    public $SearchService = null;
    
    function __construct($username, $passwd, $sourceId = null, $affiliateId = null) {
        $this->UserName = $username;
        $this->Password = $passwd;
        
        $this->SourceId = $sourceId;
        $this->AffiliateId = $affiliateId;
        
        $this->RegistrationService = new PeopleMediaRegistrationService($this);
        $this->SearchService = new PeopleMediaSearchService($this);
    }

    public function CreateClient(PeopleMediaService $svc)
    {
        $options = array( 
            'soap_version'    => SOAP_1_1, 
            'exceptions'      => true, 
            'trace'           => 1, 
            'wsdl_local_copy' => true
        );
        
        $wsse_header = new WsseAuthHeader($this->UserName, $this->Password);    
        
        $client = new SoapClient($svc->GetWsdlUrl(), $options); 
        $client->__setSoapHeaders(array($wsse_header));
        
        $client->__setLocation($svc->GetServiceUrl());
        
        return $client;   
    }
    
    public function HandleSoapFault(SoapFault $soapFault)
    {
        $details = $soapFault->detail;
        foreach ($details as $k => $v) {
            switch ($v->Code)
            {
                case "80001": //General Auth/Security Exception
                    throw new AuthenticationException("Unable to authenticate for the requested API method", $v->Code);
                default:
                    throw new Exception($v->Title, $v->Code);
            }
            break;
        }
        
        throw new Exception("Unknown error from People Media Api");
    }

}
