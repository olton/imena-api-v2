<?php

require_once "ImenaAPIv2Command.php";
require_once "ImenaAPIv2ContactType.php";
require_once "ImenaAPIv2PaymentStatus.php";

class ImenaAPIv2 {
    private $_version = "2.0.0";

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

    private $error_api= null;
    private $error = null;
    private $error_message = "";
    private $errors = [];
    private $result = [];

    public function __construct($endPoint = "", $tr_prefix = "API-", $tr_suffix = "-IMENA-v2") {
        $this->end_point = $endPoint;
        $this->_tr_prefix = $tr_prefix;
        $this->_tr_suffix = $tr_suffix;
        $this->_curl_present = function_exists("curl_exec") && is_callable("curl_exec");
        if (!$this->_curl_present) throw new Exception("CURL required!");
    }

    private function _transactionId(){
        return $this->_tr_prefix
            . date('YmdHis')
            .""
            . round(microtime(true),0)
            . $this->_tr_suffix;
    }

    private function _curlExec($cmd, $data = []){
        $query = [
            "jsonrpc" => "2.0",
            "method" => $cmd,
            "params" => $data,
            "id" => $this->_transactionId()
        ];

        $this->_command = json_encode($query);
        $this->_command_array = $query;
        $this->error_api = null;
        $this->error = 0;
        $this->error_message = "";
        $this->errors = [];
        $this->_curl_error = "";

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
        } catch (Exception $e) {
            $this->_curl_error = $e->getMessage();
            return false;
        }
    }

    private function _execute($command, $arguments = []){
        if ($command !== ImenaAPIv2Command::LOGIN) {
            $arguments["authToken"] = $this->_auth_token;
        }

        $result = $this->_curlExec($command, $arguments);

        if (!$result || !isset($result["result"])) {
            return false;
        }
        if ($command === ImenaAPIv2Command::LOGIN) {
            $this->_auth_token = $result["result"]["authToken"];
        }
        if ($command === ImenaAPIv2Command::LOGOUT) {
            $this->_auth_token = null;
        }
        return !isset($result["result"]) ? false : $result["result"];
    }

    public function Ver(){
        return $this->_version;
    }

    public function Info(){
        return $this->_curl_info;
    }

    public function ResultRaw(){
        return $this->_curl_raw_result;
    }

    public function Result(){
        return $this->result;
    }

    public function Error($api = true){
        return $api ? $this->error_api : $this->_curl_error;
    }

    public function ErrorCode(){
        return $this->error;
    }

    public function ErrorMessage(){
        return $this->error_message;
    }

    public function Errors(){
        return $this->errors;
    }

    public function Command($as_array = false){
        return $as_array ? $this->_command_array : $this->_command;
    }

    /**
     * @param $user
     * @param $password
     * @param null $smsCode
     * @param null $gaCode
     * @return bool
     */
    public function Login($user, $password, $smsCode = null, $gaCode = null){
        $data = [
            "login" => $user,
            "password" => $password
        ];
        if ($smsCode) {$data["smsCode"] = $smsCode;}
        if ($gaCode) {$data["gaCode"] = $gaCode;}
        $result = $this->_execute(ImenaAPIv2Command::LOGIN, $data);
        return $result === false ? false : $result["authToken"];
    }

    public function Logout(){
        $result = $this->_execute(ImenaAPIv2Command::LOGOUT);
        return $result === false ? false : true;
    }

    public function TokenInfo(){
        return $this->_execute(ImenaAPIv2Command::TOKEN_INFO);
    }

    public function Domains($limit = 500, $offset = 0){
        $result = $this->_execute(ImenaAPIv2Command::DOMAINS_LIST, [
            "limit" => $limit,
            "offset" => $offset
        ]);
        return $result === false ? false : $result["list"];
    }

    public function DomainsBy($filter){
        $domains = [];
        $limit = 500;
        $count = $this->DomainsCount();

        if ($count === false) {
            return false;
        }

        if ($count === 0) {
            return $domains;
        }

        $pages = round($count / $limit) + 1;

        for($i = 0; $i < $pages; $i++) {
            $result = $this->Domains($limit, $limit * $i);

            if ($i === 0 && $result === false) {
                return false;
            }

            if ($result !== false) foreach ($result as $domain) {
                if (strpos($domain["domainName"], $filter) !== false) {
                    $domains['serviceCode'] = $domain;
                }
            }
        }
        return $domains;
    }

    public function DomainsCount(){
        $result = $this->_execute(ImenaAPIv2Command::DOMAINS_LIST, [
            "limit" => 1,
            "offset" => 0
        ]);
        return $result === false ? false : intval($result["total"]);
    }

    public function Domain($code){
        return $this->_execute(ImenaAPIv2Command::DOMAIN_INFO, [
            "serviceCode" => "".$code
        ]);
    }

    public function SetNS($code, $ns = []){
        $command = ImenaAPIv2Command::SET_NS_DEFAULT;
        $data = [
            "serviceCode" => $code
        ];

        if (is_array($ns)) {

            $command = ImenaAPIv2Command::SET_NS;
            $data["list"] = $ns;

        } else if (is_string($ns)) {

            switch (strtolower($ns)) {
                case 'mirohost': $command = ImenaAPIv2Command::SET_NS_MIROHOST; break;
                case 'dnshosting': $command = ImenaAPIv2Command::SET_NS_DNSHOSTING; break;
            }

        }

        $result = $this->_execute($command, $data);
        return $result === false ? false : true;
    }

    public function SetDefaultNS($code){
        $result = $this->_execute(ImenaAPIv2Command::SET_NS_DEFAULT, [
            "serviceCode" => "".$code
        ]);
        return $result === false ? false : true;
    }

    public function SetDnsHostingNS($code){
        $result =  $this->_execute(ImenaAPIv2Command::SET_NS_DNSHOSTING, [
            "serviceCode" => "".$code
        ]);
        return $result === false ? false : true;
    }

    public function SetMirohostNS($code){
        $result = $this->_execute(ImenaAPIv2Command::SET_NS_MIROHOST, [
            "serviceCode" => "".$code
        ]);
        return $result === false ? false : true;
    }

    public function AddChildNS($code, $host, $ip){
        $result = $this->_execute(ImenaAPIv2Command::ADD_CHILD_NS, [
            "serviceCode" => "".$code,
            "host" => $host,
            "ip" => $ip
        ]);
        return $result === false ? false : true;
    }

    public function DeleteChildNS($code, $host, $ip){
        return $this->_execute(ImenaAPIv2Command::DEL_CHILD_NS, [
            "serviceCode" => "".$code,
            "host" => $host,
            "ip" => $ip
        ]);
    }

    public function SetDomainContact($code, $contactType, $contactData){
        $result = $this->_execute(ImenaAPIv2Command::UPD_CONTACT, [
            "serviceCode" => "".$code,
            "contactType" => $contactType,
            "contact" => $contactData
        ]);
        return $result === false ? false : true;
    }

    public function SetPrivacy($code, $disclose = false){
        $result = $this->_execute(ImenaAPIv2Command::SET_PRIVACY, [
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

    public function ResellerBalance(){
        return $this->_execute(ImenaAPIv2Command::RESELLER_BALANCE);
    }

    public function ResellerPrices(){
        return $this->_execute(ImenaAPIv2Command::RESELLER_PRICES);
    }

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
        $result = $this->_execute(ImenaAPIv2Command::CREATE_RENEW_PAYMENT, [
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
        $result = $this->_execute(ImenaAPIv2Command::CREATE_REGISTRATION_PAYMENT, [
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
     * @param null $currentStopDate
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

        $result = $this->_execute(ImenaAPIv2Command::CREATE_REGISTRATION_ORDER, $data);
        return $result === false ? false : $result["serviceCode"];
    }

    public function CreateTransferPayment($code, $term = 1){
        $result =  $this->_execute(ImenaAPIv2Command::CREATE_TRANSFER_PAYMENT, [
            "serviceCode" => "".$code,
            "term" => intval($term)
        ]);
        return $result === false ? false : $result["paymentId"];
    }

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

        $result = $this->_execute(ImenaAPIv2Command::CREATE_TRANSFER_ORDER, $data);
        return $result === false ? false : $result["serviceCode"];
    }

    public function DeleteUnusedOrder($code){
        $result = $this->_execute(ImenaAPIv2Command::DELETE_ORDER, [
            "serviceCode" => "".$code
        ]);
        return $result === false ? false : true;
    }

    public function PaymentStatus($paymentId){
        return $this->_execute(ImenaAPIv2Command::PAYMENT_STATUS, [
            "paymentId" => $paymentId
        ]);
    }
}