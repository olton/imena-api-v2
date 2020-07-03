<?php

namespace Services\ImenaV2;

require_once "ImenaAPIv2Const.php";

use Exception;

class ImenaAPIv2 {
    private $_version = "1.0.0";

    private $end_point = "";

    private $_curl_present = false;
    private $_curl_info = null;
    private $_curl_raw_result = null;
    private $_curl_error = null;

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
        $this->_curl_present = function_exists("curl_exec") && is_callable("curl_exec");
        if (!$this->_curl_present) throw new Exception("CURL required!");
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
        $query = [
            "jsonrpc" => "2.0",
            "method" => $cmd,
            "params" => $data,
            "id" => $this->_transactionId()
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
                'X-ApiTransactionID: ' . $this->_transactionId(),
            ));

            $this->_curl_raw_result = curl_exec($ch);
            $this->_curl_info = curl_getinfo($ch);

            curl_close($ch);

            $this->result = json_decode($this->_curl_raw_result, true);

            if (!isset($this->result["result"])) {
                $this->error_api = $this->result["error"];
                $this->error = $this->result["error"]["code"];
                $this->error_message = $this->result["error"]["message"];
                $this->errors = isset($this->result["error"]["errors"]) ? $this->result["error"]["errors"] : [];
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
        return $result === false ? false : $result["authToken"];
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
        return $result === false ? false : $result["authToken"];
    }

    /**
     * Logout from endpoint
     * @return bool
     */
    public function Logout(){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_LOGOUT);
        return $result === false ? false : true;
    }

    /**
     * Get Reseller/Client info
     * Return info for current logged client
     * @return bool|mixed
     */
    public function TokenInfo(){
        return $this->_execute(ImenaAPIv2Const::COMMAND_TOKEN_INFO);
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
        return $result === false ? false : $result["list"];
    }

    /**
     * Return domains list filtered by argument or all account domains
     * Important. May be long operation
     * @param $filter - string, part of domain name
     * @return array|bool
     */
    public function DomainsBy($filter = null){
        $domains = [];
        $limit = 500;
        $count = $this->DomainsCount();

        if ($count === false) {
            return false;
        }

        if ($count === 0) {
            return $domains;
        }

        $pages = ceil($count / $limit);

        for($i = 0; $i < $pages; $i++) {
            $result = $this->Domains($limit, $limit * $i);

            if ($i === 0 && $result === false) {
                return false;
            }

            if ($result !== false) foreach ($result as $domain) {
                if ($filter) {
                    if (strpos($domain["domainName"], $filter) !== false) {
                        $domains[$domain["serviceCode"]] = $domain;
                    }
                }
            }
        }
        return $domains;
    }

    /**
     * Return count domains on account
     * @return bool|int
     */
    public function DomainsCount(){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_DOMAINS_LIST, [
            "limit" => 1,
            "offset" => 0
        ]);
        return $result === false ? false : intval($result["total"]);
    }

    /**
     * Get domain info by service code
     * @param $code - string - domain service code
     * @return bool|mixed
     */
    public function Domain($code){
        return $this->_execute(ImenaAPIv2Const::COMMAND_DOMAIN_INFO, [
            "serviceCode" => "".$code
        ]);
    }

    public function NS($code){
        $domain = $this->Domain($code);
        return $domain === false ? false : $domain["nameservers"];
    }

    public function ChildNS($code){
        $domain = $this->Domain($code);
        return $domain === false ? false : $domain["childNameservers"];
    }

    public function Contacts($code, $withPrivacy = false){
        $domain = $this->Domain($code);
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
     * Set name servers.
     * If second argument is an array, command sets user defined NS
     * If second argument is a string, command set one of default sets
     * For default sets you must use constants ImenaAPIv2HostingType::MIROHOST or ImenaAPIv2HostingType::DNS
     * ImenaAPIv2HostingType::DNS - for DNSHosting
     * ImenaAPIv2HostingType::MIROHOST - for Mirohost
     * ImenaAPIv2HostingType::DEFAULTS - for Imena default NS
     * @param $code
     * @param array $ns
     * @return bool
     */
    public function SetNS($code, $ns = []){
        $command = ImenaAPIv2Const::COMMAND_SET_NS_DEFAULT;
        $data = [
            "serviceCode" => $code
        ];

        if (is_array($ns)) {

            $command = ImenaAPIv2Const::COMMAND_SET_NS;
            $data["list"] = $ns;

        } else if (is_string($ns)) {

            switch (strtolower($ns)) {
                case ImenaAPIv2Const::HOSTING_TYPE_MIROHOST: $command = ImenaAPIv2Const::COMMAND_SET_NS_MIROHOST; break;
                case ImenaAPIv2Const::HOSTING_TYPE_DNS: $command = ImenaAPIv2Const::COMMAND_SET_NS_DNSHOSTING; break;
            }

        }

        $result = $this->_execute($command, $data);
        return $result === false ? false : true;
    }

    /**
     * Short method to set Imena default NS
     * @param $code - string - domain service code
     * @return bool
     */
    public function SetDefaultNS($code){
        return $this->SetNS($code, $command = ImenaAPIv2Const::COMMAND_SET_NS_DEFAULT);
    }

    /**
     * Short method to set DNSHosting NS
     * @param $code - string - domain service code
     * @return bool
     */
    public function SetDnsHostingNS($code){
        return $this->SetNS($code, $command = ImenaAPIv2Const::COMMAND_SET_NS_DNSHOSTING);
    }

    /**
     * Short method to set Mirohost NS
     * @param $code - string - domain service code
     * @return bool
     */
    public function SetMirohostNS($code){
        return $this->SetNS($code, $command = ImenaAPIv2Const::COMMAND_SET_NS_MIROHOST);
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
        return $result === false ? false : true;
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
    public function SetDomainContact($code, $contactType, $contactData){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_UPD_CONTACT, [
            "serviceCode" => "".$code,
            "contactType" => $contactType,
            "contact" => $contactData
        ]);
        return $result === false ? false : true;
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
        return $result === false ? false : true;
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
     * @return bool|mixed
     */
    public function ResellerBalance(){
        return $this->_execute(ImenaAPIv2Const::COMMAND_RESELLER_BALANCE);
    }

    /**
     * Get Reseller prices
     * @return bool|mixed
     */
    public function ResellerPrices(){
        return $this->_execute(ImenaAPIv2Const::COMMAND_RESELLER_PRICES);
    }

    /**
     * Get reseller prices for specified domains or part of domain name
     * @param $domain
     * @return array|bool
     */
    public function ResellerPriceFor($domain){
        $result = $this->ResellerPrices();
        if ($result === false) {
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

    /**
     * Renew domain
     * @param $code
     * @param $currentStopDate
     * @param int $term
     * @return bool|mixed
     */
    public function CreateRenewPayment($code, $currentStopDate, $term = 1){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CREATE_RENEW_PAYMENT, [
            "serviceCode" => "".$code,
            "currentStopDate" => $currentStopDate,
            "term" => intval($term)
        ]);
        return $result === false ? false : $result["paymentId"];
    }

    /**
     * Create payment for registration domain operation. Must executed after creating order.
     * @param $code
     * @param int $term
     * @return bool|mixed
     */
    public function CreateRegistrationPayment($code, $term = 1){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CREATE_REGISTRATION_PAYMENT, [
            "serviceCode" => "".$code,
            "term" => intval($term)
        ]);
        return $result === false ? false : $result["paymentId"];
    }

    /**
     * Create order for domain registration procedure.
     * @param $clientCode
     * @param $domainName
     * @param int $term
     * @param null $aeroId
     * @param null $ensAuthKey
     * @param null $patentNumber
     * @param null $patentDate
     * @param null $nicId
     * @return bool|mixed
     */
    public function CreateRegistrationOrder($clientCode, $domainName, $term = 1, $aeroId = null, $ensAuthKey = null, $patentNumber = null, $patentDate = null, $nicId = null){
        $data = [
            "clientCode" => "".$clientCode,
            "domainName" => $domainName,
            "term" => $term
        ];

        if ($aeroId) {$data['aeroId'] = $aeroId;}
        if ($aeroId && $ensAuthKey) {$data['ensAuthKey'] = $ensAuthKey;}
        if ($patentNumber) {$data['patentNumber'] = $patentNumber;}
        if ($patentNumber && $patentDate) {$data['patentDate'] = $patentDate;}
        if ($nicId) {$data['nicId'] = $nicId;}

        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CREATE_REGISTRATION_ORDER, $data);
        return $result === false ? false : $result["serviceCode"];
    }

    /**
     * Create transfer payment, must be executed after call method createTransferOrder
     * @param $code
     * @param int $term
     * @return bool|mixed
     */
    public function CreateTransferPayment($code, $term = 1){
        $result =  $this->_execute(ImenaAPIv2Const::COMMAND_CREATE_TRANSFER_PAYMENT, [
            "serviceCode" => "".$code,
            "term" => intval($term)
        ]);
        return $result === false ? false : $result["paymentId"];
    }

    /**
     * Create transfer order and service code for future domain
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
    public function CreateTransferOrder($clientCode, $domainName, $term = 1, $authCode = "", $aeroId = null, $ensAuthKey = null, $patentNumber = null, $patentDate = null, $nicId = null){
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

        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CREATE_TRANSFER_ORDER, $data);
        return $result === false ? false : $result["serviceCode"];
    }

    /**
     * Delete unused orders for domain, defined with service code
     * @param $code
     * @return bool
     */
    public function DeleteUnusedOrder($code){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_DELETE_ORDER, [
            "serviceCode" => "".$code
        ]);
        return $result === false ? false : true;
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
    public function PickDomain($resellerCode, $names, $zones){
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
    public function Client($clientCode){
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
    public function Clients($resellerCode, $limit = 500, $offset = 0){
        $result = $this->_execute(ImenaAPIv2Const::COMMAND_CLIENT_LIST, [
            "resellerCode" => $resellerCode,
            "limit" => $limit,
            "offset" => $offset
        ]);
        return $result === false ? false : $result["list"];
    }

    /**
     * @param $resellerCode
     * @param string $firstName
     * @param string $middleName
     * @param string $lastName
     * @param string $language ua|ru|en
     * @param string $clientType individual|sole proprietor|legal entity
     * @param bool $resident
     * @param array $contact
     * @param array $legal
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
    public function CreateClient($resellerCode, $firstName, $middleName, $lastName, $language, $clientType, $resident, $contact, $legal){
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
        return $result === false ? false : $result["clientCode"];
   }
}