<?php

namespace Services\ImenaV2;

require_once "ImenaAPIv2Const.php";

use Exception;

class ImenaAPIv2 {
    private $_version = "1.0.0";

    private $end_point = "";

    private $_curl_info = null;
    private $_curl_raw_result = null;
    private $_curl_error = null;
    private $_curl_error_code = null;

    private $_tr_prefix = "";
    private $_tr_suffix = "";

    private $_auth_token = null;

    private $_command = "";
    private $_command_array = [];

    private $_user = "";
    private $_password = "";

    private $error_api= null;
    private $error = null;
    private $error_message = "";
    private $errors = [];
    private $result = [];

    private $userType;
    private $userExpired;
    private $userInfo;
    private $logged = false;
    private $transactionID = null;


    /**
     * ImenaAPIv2 constructor.
     * @param string $endPoint
     * @param string $tr_prefix
     * @param string $tr_suffix
     * @throws Exception
     */
    public function __construct($endPoint = "", $tr_prefix = "API-", $tr_suffix = "-IMENA-v2") {
        $this->end_point = $endPoint;
        $this->_tr_prefix = $tr_prefix;
        $this->_tr_suffix = $tr_suffix;

        if (!(function_exists("curl_exec") && is_callable("curl_exec")))
            throw new Exception("CURL required!");
    }

    /**
     * Setup endpoint
     * @param $endPoint
     */
    public function SetEndPoint($endPoint = ""){
        $this->end_point = $endPoint;
    }

    /**
     * Generate transaction ID
     * @return string
     */
    private function _transactionId(){
        return $this->_tr_prefix
            . date('YmdHis')
            .""
            . round(microtime(true),0)
            . $this->_tr_suffix;
    }

    /**
     * Execute API command with CURL lib
     * @param $cmd
     * @param array $data
     * @return array|bool|mixed
     */
    private function _curlExec($cmd, $data = []){
        $trID = $this->_transactionId();
        $query = [
            "jsonrpc" => "2.0",
            "method" => $cmd,
            "params" => $data,
            "id" => $trID
        ];

        $this->_command = json_encode($query);
        $this->_command_array = $query;

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->end_point);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_command);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-ApiTransactionID: ' . $trID,
            ));

            $this->_curl_raw_result = curl_exec($ch);
            $this->_curl_info = curl_getinfo($ch);
            $this->_curl_error = curl_error($ch);
            $this->_curl_error_code = curl_errno($ch);

            curl_close($ch);

            $this->result = json_decode($this->_curl_raw_result, true);

            if (!isset($this->result["result"])) {
                $this->error_api = $this->result["error"];
                $this->error = $this->result["error"]["code"];
                $this->error_message = $this->result["error"]["message"];
                $this->errors = isset($this->result["error"]["errors"]) ? $this->result["error"]["errors"] : [];
            } else {
                $this->transactionID = $this->result["id"];
            }

            return $this->result;
        } catch (\Exception $e) {
            $this->_curl_error = $e->getMessage();
            return false;
        }
    }

    private function _resetError(){
        $this->error_api = null;
        $this->error = 0;
        $this->error_message = "OK";
        $this->errors = [];
    }

    /**
     * Execute API method
     * @param $command
     * @param array $arguments
     * @return bool|mixed
     */
    private function _execute($command, $arguments = []){
        $this->_resetError();
        
        if ($command !== ImenaAPIv2Const::COMMAND_LOGIN) {
            $arguments["authToken"] = $this->_auth_token;
        }

        $result = $this->_curlExec($command, $arguments);

        if (!$result || !isset($result["result"])) {
            return false;
        }
        if ($command === ImenaAPIv2Const::COMMAND_LOGIN) {
            $this->_auth_token = $result["result"]["authToken"];
        }
        if ($command === ImenaAPIv2Const::COMMAND_LOGOUT) {
            $this->_auth_token = null;
        }
        return !isset($result["result"]) ? false : $result["result"];
    }

    public function ID(){
        return $this->transactionID;
    }

    /**
     * Return class version
     * @return string
     */
    public function Ver(){
        return $this->_version;
    }

    /**
     * Return last CURL info for executed command
     * @return null
     */
    public function Info(){
        return $this->_curl_info;
    }

    /**
     * Return last raw result from CURL executing
     * @return null
     */
    public function ResultRaw(){
        return $this->_curl_raw_result;
    }

    /**
     * Return API result
     * @return array
     */
    public function Result(){
        return $this->result;
    }

    /**
     * Get last error. API or CURL
     * @param bool $api
     * @return array or string
     */
    public function Error($api = true){
        return $api ? $this->error_api : $this->_curl_error;
    }

    /**
     * Get last API error code
     * @return null
     */
    public function ErrorCode(){
        return $this->error;
    }

    /**
     * Get last API error message
     * @return string
     */
    public function ErrorMessage(){
        return $this->error_message;
    }

    /**
     * Get errors, if exist in error object
     * @return array
     */
    public function Errors(){
        return $this->errors;
    }

    /**
     * Get last API command
     * @param bool $as_array
     * @return array|string
     */
    public function Command($as_array = false){
        return $as_array ? $this->_command_array : $this->_command;
    }

    /**
     * Login to endpoint or send second step verification
     * @param $user
     * @param $password
     * @param null $smsCode
     * @param null $gaCode
     * @return bool
     */
    public function Login($user, $password, $smsCode = null, $gaCode = null){
        $this->_user = $user;
        $this->_password = $password;
        $data = [
            "login" => $user,
            "password" => $password
        ];

        if ($smsCode) {$data["smsCode"] = $smsCode;}
        if ($gaCode) {$data["gaCode"] = $gaCode;}

        $result = $this->_execute(ImenaAPIv2Const::COMMAND_LOGIN, $data);

        if ($result !== false) {
            $this->logged = true;
            $this->TokenInfo();
        }

        return !$result ? false : $result["authToken"];
    }

    public function GetLogin(){
        return $this->_user;
    }

    /**
     * Second auth
     * @param $code
     * @param string $type
     * @return bool|mixed
     */
    public function SecondAuth($code, $type = ImenaAPIv2Const::SECOND_AUTH_SMS){
        $data = [
            "login" => $this->_user,
            "password" => $this->_password
        ];
        if (strtolower($type) === ImenaAPIv2Const::SECOND_AUTH_SMS) {
            $data["smsCode"] = $code;
        } else {
            $data["gaCode"] = $code;
        }

        $result = $this->_execute(ImenaAPIv2Const::COMMAND_LOGIN, $data);

        if ($result !== false) {
            $this->logged = true;
            $this->TokenInfo();
        }

        return !$result ? false : $result["authToken"];
    }

    /**
     * Logout from endpoint
     * @return bool
     */
    public function Logout(){
        $this->userType = null;
        $this->userExpired = null;
        $this->userInfo = null;
        $this->logged = false;

        $result = $this->_execute(ImenaAPIv2Const::COMMAND_LOGOUT);

        return $result !== false;
    }

    /**
     * get logged status
     * @return bool
     */
    public function IsLogged(){
        return $this->logged;
    }

    /**
     * Get Reseller/Client info
     * Return info for current logged client
     * @return bool|mixed
     */
    public function TokenInfo(){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_TOKEN_INFO);

        $this->userType = $result["userType"];
        $this->userExpired = $result["expiredAt"];
        $this->userInfo = $result["user"];

        return $result;
    }

    /**
     * get user type clientUser | resellerUser
     * @return mixed
     */
    public function GetUserType(){
        return $this->userType;
    }

    /**
     * get date of auth token expired
     * @return mixed
     */
    public function GetUserExpired(){
        return $this->userExpired;
    }

    /**
     * get user info
     * @return mixed
     */
    public function GetUserInfo(){
        return $this->userInfo;
    }

    /**
     * get reseller code
     * @return mixed
     */
    public function GetResellerCode(){
        return $this->userInfo["resellerCode"];
    }

    /**
     * Return domains list
     * @param int $limit
     * @param int $offset
     * @return bool|mixed
     */
    public function Domains($limit = 500, $offset = 0){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_DOMAINS_LIST, [
            "limit" => $limit,
            "offset" => $offset
        ]);
        return !$result ? false : $result["list"];
    }

    /**
     * Return domains list filtered by argument or all account domains
     * Important. May be long operation
     * @param $filter - string, part of domain name
     * @return array|bool
     */
    public function DomainsBy($filter = ""){
        $domains = [];
        $limit = 500;
        $count = $this->DomainsTotal();

        if ($count === false) {
            return false;
        }

        if ($count === 0) {
            return $domains;
        }

        $pages = ceil($count / $limit);

        for($i = 0; $i < $pages; $i++) {
            $result = $this->Domains($limit, $limit * $i);

            if ($i === 0 && !$result) {
                return false;
            }

            if ($result !== false) foreach ($result as $domain) {
                if ($filter) {
                    if (strpos($domain["domainName"], $filter) !== false) {
                        $domains[$domain["serviceCode"]] = $domain;
                    }
                } else {
                    $domains[$domain["serviceCode"]] = $domain;
                }
            }
        }
        return $domains;
    }

    public function DomainsAll(){
        return $this->DomainsBy();
    }

    /**
     * Return count domains on account
     * @return bool|int
     */
    public function DomainsTotal(){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_DOMAINS_LIST, [
            "limit" => 1,
            "offset" => 0
        ]);
        return !$result ? false : intval($result["total"]);
    }

    /**
     * Get domain info by service code
     * @param $code - string - domain service code
     * @return bool|mixed
     */
    public function DomainInfo($code){
        return $this->_execute(ImenaAPIv2Const::COMMAND_DOMAIN_INFO, [
            "serviceCode" => "".$code
        ]);
    }

    /**
     * get short info about domain
     * @param $domain_name
     * @return bool|mixed
     */
    public function DomainInfoShort($domain_name){
        return $this->_execute(ImenaAPIv2Const::COMMAND_DOMAIN_INFO_SHORT, [
            "domainName" => $domain_name
        ]);
    }

    /**
     * get domain tags
     * @param $code
     * @return bool|mixed
     */
    public function Tags($code){
        $domain = $this->DomainInfo($code);
        return $domain === false ? false : $domain["tagList"];
    }

    /**
     * get domain nameservers
     * @param $code
     * @return bool|mixed
     */
    public function Nameservers($code){
        $domain = $this->DomainInfo($code);
        return $domain === false ? false : $domain["nameservers"];
    }

    /**
     * get domain child nameservers
     * @param $code
     * @return bool|mixed
     */
    public function ChildNameservers($code){
        $domain = $this->DomainInfo($code);
        return $domain === false ? false : $domain["childNameservers"];
    }

    /**
     * Set name servers.
     * If second argument is an array, command sets user defined NS
     * If second argument is a string, command set one of default sets
     * For default sets you must use constants ImenaAPIv2HostingType::MIROHOST or ImenaAPIv2HostingType::DNS
     * ImenaAPIv2HostingType::DNS - for DNSHosting
     * ImenaAPIv2HostingType::MIROHOST - for Mirohost
     * ImenaAPIv2HostingType::DEFAULTS - for Imena default NS
     *
     * @param $code
     * @param array $ns
     * @return bool
     */
    public function SetNS($code, $ns = []){
        if (count($ns) < 2) {
            throw new \Error("You must define two or more Name servers!");
        }

        $data = [
            "serviceCode" => $code,
            "list" => $ns
        ];

        return $this->_execute(ImenaAPIv2Const::COMMAND_SET_NS, $data);
    }

    public function SetNSPreset($code, $preset = ImenaAPIv2Const::HOSTING_TYPE_DEFAULTS) {
        $command = ImenaAPIv2Const::COMMAND_SET_NS_DEFAULT;
        $data = [
            "serviceCode" => $code
        ];
        switch ($preset) {
            case ImenaAPIv2Const::HOSTING_TYPE_MIROHOST: $command = ImenaAPIv2Const::COMMAND_SET_NS_MIROHOST; break;
            case ImenaAPIv2Const::HOSTING_TYPE_DNS: $command = ImenaAPIv2Const::COMMAND_SET_NS_DNSHOSTING; break;
        }
        return $this->_execute($command, $data);
    }

    /**
     * Short method to set Imena default NS
     * @param $code - string - domain service code
     * @return bool
     */
    public function SetDefaultNS($code){
        return $this->SetNSPreset($code, ImenaAPIv2Const::HOSTING_TYPE_DEFAULTS);
    }

    /**
     * Short method to set DNSHosting NS
     * @param $code - string - domain service code
     * @return bool
     */
    public function SetDnshostingNS($code){
        return $this->SetNSPreset($code, ImenaAPIv2Const::HOSTING_TYPE_DNS);
    }

    /**
     * Short method to set Mirohost NS
     * @param $code - string - domain service code
     * @return bool
     */
    public function SetMirohostNS($code){
        return $this->SetNSPreset($code, ImenaAPIv2Const::HOSTING_TYPE_MIROHOST);
    }

    /**
     * Add child NS
     * @param $code - string, domain service code
     * @param $host - string, host name
     * @param $ip - string, ip address
     * @return bool
     */
    public function AddChildNS($code, $host, $ip){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_ADD_CHILD_NS, [
            "serviceCode" => "".$code,
            "host" => $host,
            "ip" => $ip
        ]);
        return !$result ? false : true;
    }

    /**
     * Delete child NS
     * @param $code - string, domain service code
     * @param $host - string, host name
     * @param $ip - string, ip address
     * @return bool|mixed
     */
    public function DeleteChildNS($code, $host, $ip){
        return $this->_execute(ImenaAPIv2Const::COMMAND_DEL_CHILD_NS, [
            "serviceCode" => "".$code,
            "host" => $host,
            "ip" => $ip
        ]);
    }

    /**
     * get domain contacts
     * @param $code
     * @param bool $withPrivacy
     * @return array|bool
     */
    public function Contacts($code, $withPrivacy = false){
        $domain = $this->DomainInfo($code);
        $contacts = [];
        if ($domain === false) return false;
        foreach ($domain["contacts"] as $contact) {
            $contacts[$contact["contactType"]] = $contact["contact"];
        }

        if ($withPrivacy === true) {
            $contacts['privacy'] = $domain["isWhoisPrivacyEnabled"];
        }
        return $contacts;
    }

    /**
     * get specified contact
     * @param $code
     * @param string $contactType admin-c, tech-c, billing-c, owner-c in lower case. Default admin-c
     * @return bool|mixed
     */
    public function Contact($code, $contactType = ImenaAPIv2Const::CONTACT_ADMIN){
        $result = $this->Contacts($code);
        return !$result ? false : $result[$contactType];
    }

    /**
     * get admin-c
     * @param $code
     * @return bool|mixed
     */
    public function ContactAdmin($code){
        $result = $this->Contacts($code);
        return !$result ? false : $result[ImenaAPIv2Const::CONTACT_ADMIN];
    }

    /**
     * get tech-c
     * @param $code
     * @return bool|mixed
     */
    public function ContactTech($code){
        $result = $this->Contacts($code);
        return !$result ? false : $result[ImenaAPIv2Const::CONTACT_TECH];
    }

    /**
     * get billing-c
     * @param $code
     * @return bool|mixed
     */
    public function ContactBilling($code){
        $result = $this->Contacts($code);
        return !$result ? false : $result[ImenaAPIv2Const::CONTACT_BILLING];
    }

    /**
     * get owner-c
     * @param $code
     * @return bool|mixed
     */
    public function ContactOwner($code){
        $result = $this->Contacts($code);
        return !$result ? false : $result[ImenaAPIv2Const::CONTACT_OWNER];
    }

    /**
     * Set domain contact
     * @param $code - string - domain service code
     * @param $contactType - string - use constants, defined in ImenaAPIv2ContactType (ImenaAPIv2ContactType::ADMIN, ImenaAPIv2ContactType::TECH, ImenaAPIv2ContactType::OWNER, ImenaAPIv2ContactType::BILLING)
     * @param $contactData - array
     * firstName - имя
     * middleName - отчество
     * lastName - фамилия
     * company - компания
     * email
     * country - ISO код страны
     * postalCode - почтовый код
     * region - область
     * city - город
     * address - строка адреса (улица, дом, квартира)
     * address2 - 2 строка адреса
     * phone - телефонный номер в формате E164
     * fax - телефонный номер в формате E164
     * @return bool
     */
    public function SetContact($code, $contactType, $contactData){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_UPD_CONTACT, [
            "serviceCode" => "".$code,
            "contactType" => $contactType,
            "contact" => $contactData
        ]);
        return !$result ? false : true;
    }

    /**
     * Set contact disclosure, if second argument is true, contact will be disclosed
     * @param $code
     * @param bool $disclose
     * @return bool
     */
    public function SetPrivacy($code, $disclose = false){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_SET_PRIVACY, [
            "serviceCode" => "".$code,
            "whoisPrivacy" => !$disclose
        ]);
        return !$result ? false : true;
    }

    /**
     * Synonym for TokenInfo
     * @return bool|mixed
     */
    public function ResellerInfo(){
        return $this->TokenInfo();
    }

    /**
     * Get reseller balance
     * @param $resellerCode
     * @return bool|mixed
     */
    public function BalanceInfo($resellerCode = null){
        if ($resellerCode == null) {
            $resellerCode = $this->GetResellerCode();
        }
        return $this->_execute(ImenaAPIv2Const::COMMAND_RESELLER_BALANCE, [
            "resellerCode" => $resellerCode
        ]);
    }

    public function Balance($resellerCode = null){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_RESELLER_BALANCE, [
            "resellerCode" => $resellerCode
        ]);//['balance'];

        return $result ? $result['balance'] : false;
    }

    public function Credit($resellerCode = null){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_RESELLER_BALANCE, [
            "resellerCode" => $resellerCode
        ]);//['creditLimit'];

        return $result ? $result['creditLimit'] : false;
    }

    /**
     * Get Reseller prices
     * @param null $resellerCode
     * @return bool|mixed
     */
    public function Price($resellerCode = null){
        if ($resellerCode == null) {
            $resellerCode = $this->GetResellerCode();
        }

        return $this->_execute(ImenaAPIv2Const::COMMAND_RESELLER_PRICES, [
            "resellerCode" => $resellerCode
        ]);
    }

    /**
     * Get reseller prices for specified domains or part of domain name
     * @param $domain
     * @param null $resellerCode
     * @return array|bool
     */
    public function PriceDomain($domain = "", $resellerCode = null){
        $result = $this->Price($resellerCode);
        if (!$result) {
            return false;
        }
        $domains = [];
        foreach ($result as $value) {
            if (strpos($value["domain"], $domain) !== false) {
                $domains[] = $value;
            }
        }
        return $domains;
    }

    public function PriceDomains($domainList = [], $resellerCode = null){
        $result = $this->Price($resellerCode);
        if (!$result) {
            return false;
        }
        $domains = [];
        foreach ($result as $value) {
            foreach ($domainList as $domain) {
                if (strpos($value["domain"], $domain) !== false) {
                    $domains[] = $value;
                }
            }
        }
        return $domains;
    }

    public function Payment($paymentType, $serviceCode, $term = 1, $currentStopDate = null){
        switch ($paymentType) {
            case ImenaAPIv2Const::PAYMENT_TYPE_RENEW:
                $cmd = ImenaAPIv2Const::COMMAND_CREATE_RENEW_PAYMENT;
                break;
            case ImenaAPIv2Const::PAYMENT_TYPE_TRANSFER:
                $cmd = ImenaAPIv2Const::COMMAND_CREATE_TRANSFER_PAYMENT;
                break;
            default:
                $cmd = ImenaAPIv2Const::COMMAND_CREATE_REGISTRATION_PAYMENT;
        }

        $body = [
            "serviceCode" => "".$serviceCode,
            "currentStopDate" => $currentStopDate,
            "term" => intval($term)
        ];

        if ($currentStopDate) {
            $body["currentStopDate"] = $currentStopDate;
        }

        $result = $this->_execute($cmd, $body);
        return !$result ? false : $result["paymentId"];
    }

    /**
     * Renew domain
     * @param $code
     * @param $currentStopDate
     * @param int $term
     * @return bool|mixed
     */
    public function Renew($code, $currentStopDate, $term = 1){
        $result = $this->Payment(ImenaAPIv2Const::PAYMENT_TYPE_RENEW, $code, $term, $currentStopDate);
        return !$result ? false : $result["paymentId"];
    }

    /**
     * Create payment for registration domain operation. Must executed after creating order.
     * @param $code
     * @param int $term
     * @return bool|mixed
     */
    public function Add($code, $term = 1){
        $result = $this->Payment(ImenaAPIv2Const::PAYMENT_TYPE_REGISTRATION, $code, $term);
        return !$result ? false : $result["paymentId"];
    }

    /**
     * Create transfer payment, must be executed after call method createTransferOrder
     * @param $code
     * @param int $term
     * @return bool|mixed
     */
    public function Transfer($code, $term = 1){
        $result =  $this->Payment(ImenaAPIv2Const::PAYMENT_TYPE_TRANSFER, $code, $term);
        return !$result ? false : $result["paymentId"];
    }

    /**
     * Create transfer or registration order and service code for future domain
     * @param $orderType - [ImenaAPIv2Const::COMMAND_CREATE_TRANSFER_ORDER | ImenaAPIv2Const::COMMAND_CREATE_REGISTRATION_ORDER]
     * @param $clientCode
     * @param $domainName
     * @param int $term
     * @param string $authCode
     * @param null $aeroId
     * @param null $ensAuthKey
     * @param null $patentNumber
     * @param null $patentDate
     * @param null $nicId
     * @return bool|mixed
     */
    public function Order($orderType, $clientCode, $domainName, $term = 1, $authCode = "", $aeroId = null, $ensAuthKey = null, $patentNumber = null, $patentDate = null, $nicId = null){
        $cmd = $orderType == "transfer" ? ImenaAPIv2Const::COMMAND_CREATE_TRANSFER_ORDER : ImenaAPIv2Const::COMMAND_CREATE_REGISTRATION_ORDER;

        $data = [
            "clientCode" => "".$clientCode,
            "domainName" => $domainName,
            "term" => $term,
            "authCode" => $authCode
        ];

        if ($aeroId) {$data['aeroId'] = $aeroId;}
        if ($aeroId && $ensAuthKey) {$data['ensAuthKey'] = $ensAuthKey;}
        if ($patentNumber) {$data['patentNumber'] = $patentNumber;}
        if ($patentNumber && $patentDate) {$data['patentDate'] = $patentDate;}
        if ($nicId) {$data['nicId'] = $nicId;}

        $result = $this->_execute($cmd, $data);
        return !$result ? false : $result["serviceCode"];
    }

    /**
     * Delete unused orders for domain, defined with service code
     * @param $code
     * @return bool
     */
    public function DeleteOrders($code){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_DELETE_ORDER, [
            "serviceCode" => "".$code
        ]);
        return !$result ? false : true;
    }

    /**
     * get payment status. You can get paymentId after execute methods create***Payment
     * @param $paymentId
     * @return bool|mixed
     */
    public function PaymentStatus($paymentId){
        return $this->_execute(ImenaAPIv2Const::COMMAND_PAYMENT_STATUS, [
            "paymentId" => $paymentId
        ]);
    }

    /**
     * pick domains by part of name
     * @param int $resellerCode
     * @param array|string $names
     * @param array|string $zones
     * @return bool|mixed
    */
    public function PickDomain($names, $zones, $resellerCode = null){
        if ($resellerCode == null) {
            $resellerCode = $this->GetResellerCode();
        }
        return $this->_execute(ImenaAPIv2Const::COMMAND_PICK_DOMAIN, [
            "resellerCode" => $resellerCode,
            "names" => is_array($names) ? $names : preg_split(" ", $names),
            "domainTypes" => is_array($zones) ? $zones : preg_split(" ", $zones),
        ]);
    }

    /**
     * get client info
     * @param $clientCode
     * @return bool|mixed
     */
    public function ClientInfo($clientCode){
        return $this->_execute(ImenaAPIv2Const::COMMAND_CLIENT_INFO, [
            "clientCode" => $clientCode
        ]);
    }

    /**
     * get client list for specified reseller
     * @param $resellerCode
     * @param int $limit
     * @param int $offset
     * @return bool|mixed
     */
    public function Clients($limit = 500, $offset = 0, $resellerCode = null) {
        if ($resellerCode == null) {
            $resellerCode = $this->GetResellerCode();
        }
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CLIENT_LIST, [
            "resellerCode" => $resellerCode,
            "limit" => $limit,
            "offset" => $offset
        ]);
        return !$result ? false : $result["list"];
    }

    /**
     * @param string $firstName
     * @param string $middleName
     * @param string $lastName
     * @param string $language ua|ru|en
     * @param string $clientType individual|sole proprietor|legal entity
     * @param bool $resident
     * @param array $contact
     * @param array $legal
     * @param $resellerCode
     * @return bool|mixed
     *
     * contactData = [
     *      country*
     *      postalCode*
     *      region
     *      city*
     *      address*
     *      address2
     *      email*
     *      phone* (format E164)
     *      fax
     *      phoneEmergency
     * ]
     *
     * legal = [
     *      companyName
     *      EDRPOU
     *      legalAddress
     *      accountingPhone
     * ]
     */
    public function CreateClient($firstName, $middleName, $lastName, $language, $clientType, $resident, $contact, $legal, $resellerCode = null ){

        if ($resellerCode == null) {
            $resellerCode = $this->GetResellerCode();
        }

        $data = [
            "resellerCode" => $resellerCode,
            "firstName" => $firstName,
            "middleName" => $middleName,
            "lastName" => $lastName,
            "messagesLanguage" => $language,
            "clientType" => $clientType,
            "isUaResident" => $resident,
            "contactData" => $contact,
        ];

        if ($resident === true && $clientType !== "individual") {
            $data["legalData"] = $legal;
        }

        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CREATE_CLIENT, $data);
        return !$result ? false : $result["clientCode"];
   }

    /**
     * get auth code for domain transfer
     * @param $code
     * @return bool|mixed
     */
   public function GetAuthCode($code){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_GET_AUTH_CODE, [
            "serviceCode" => $code
        ]);

        return !$result ? false : $result["authCode"];
   }

    /**
     * internal transfer process
     * @param $serviceCode - domain service code
     * @param $authCode - domain auth code
     * @param $clientCode - client code, who receive domain
     * @return bool
     */
   public function InternalTransfer($serviceCode, $authCode, $clientCode){
       $result = $this->_execute(ImenaAPIv2Const::COMMAND_INTERNAL_TRANSFER, [
           "serviceCode" => "".$serviceCode,
           "authCode" => $authCode,
           "clientCode" => "".$clientCode
       ]);

       return $result !== false;
   }

   public function DnsInfo($code){
       $result = $this->_execute(ImenaAPIv2Const::COMMAND_DNS_GET_DATA, [
           "serviceCode" => "".$code
       ]);

       return !$result ? false : $result;
   }

   public function SetDnsDefault($code){
       $result = $this->_execute(ImenaAPIv2Const::COMMAND_DNS_SET_DEFAULT, [
           "serviceCode" => "".$code
       ]);

       return $result !== false;
   }

   public function SetDnsInfo($code, $records, $retry = 600, $ttl = 3600, $negativeTtl = 1800, $refresh = 1800, $expire = 2419200){
       $dnsZone = [
           "serviceCode" => "".$code,
           "retry" => $retry,
           "ttl" => $ttl,
           "negativeTtl" => $negativeTtl,
           "refresh" => $refresh,
           "expire" => $expire,
           "records" => $records
       ];

       echo "Request:\n";
       var_dump($dnsZone);

       $result = $this->_execute(ImenaAPIv2Const::COMMAND_DNS_SET_DATA, $dnsZone);

       return $result !== false;
   }
}