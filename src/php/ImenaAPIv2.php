<?php

require_once "ImenaAPIv2Command.php";
require_once "ImenaAPIv2ContactType.php";

class ImenaAPIv2 {
    private $_version = "2.0.0";

    private $end_point = "";

    private $_curl_present = false;
    private $_curl_info = null;
    private $_curl_raw_result = null;

    private $_tr_prefix = "API-";
    private $_tr_suffix = "-IMENA-v2";

    private $_auth_token = null;

    private $_command = "";
    private $_command_array = [];

    public $error = null;
    public $api_error = null;
    public $result = [];

    public function __construct() {
        $this->_curl_present = function_exists("curl_exec") && is_callable("curl_exec");
    }

    private function _transactionId(){
        return $this->_tr_prefix
            . date('YmdHisu')
            .""
            . round(microtime(true),0)
            . $this->_tr_suffix;
    }

    private function _curlExec($cmd, $data = []){
        if (!$this->_curl_present) throw new Exception("CURL required!");

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
                $this->api_error = $this->result["error"];
            }

            return $this->result;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function Result(){
        return $this->result;
    }

    public function Error($api = true){
        return $api ? $this->api_error : $this->error;
    }

    public function Command($as_array = false){
        return $as_array ? $this->_command_array : $this->_command;
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
        return $result["result"];
    }

    public function Login($to, $user, $password){
        $this->end_point = $to;
        return $this->_execute(ImenaAPIv2Command::LOGIN, [
                "login" => $user,
                "password" => $password
        ]);
    }

    public function Logout(){
        return $this->_execute(ImenaAPIv2Command::LOGOUT);
    }

    public function TokenInfo(){
        return $this->_execute(ImenaAPIv2Command::TOKEN_INFO);
    }

    public function Domains(){
        return $this->_execute(ImenaAPIv2Command::DOMAINS_LIST);
    }

    public function Domain($code){
        return $this->_execute(ImenaAPIv2Command::DOMAIN_INFO, [
            "serviceCode" => "".$code
        ]);
    }

    public function SetNS($code, $ns = []){
        if (is_array($ns)) {
            if (count($ns) < 2) {
                throw new Exception('Argument $ns must contains min two ns servers!');
            }
            return $this->_execute(ImenaAPIv2Command::SET_NS, [
                "serviceCode" => "".$code,
                "list" => $ns
            ]);
        } else if (is_string($ns)) {
            switch (strtolower($ns)) {
                case 'mirohost': $command = ImenaAPIv2Command::SET_NS_MIROHOST; break;
                case 'dnshosting': $command = ImenaAPIv2Command::SET_NS_DNSHOSTING; break;
                default : $command = ImenaAPIv2Command::SET_NS_DEFAULT;
            }
            return $this->_execute($command, [
                "serviceCode" => "".$code
            ]);
        }

        throw new Exception('Argument $ns must be array or string!');
    }

    public function SetDefaultNS($code){
        return $this->_execute(ImenaAPIv2Command::SET_NS_DEFAULT, [
            "serviceCode" => "".$code
        ]);
    }

    public function SetDnsHostingNS($code){
        return $this->_execute(ImenaAPIv2Command::SET_NS_DNSHOSTING, [
            "serviceCode" => "".$code
        ]);
    }

    public function SetMirohostNS($code){
        return $this->_execute(ImenaAPIv2Command::SET_NS_MIROHOST, [
            "serviceCode" => "".$code
        ]);
    }

    public function AddChildNS($code, $host, $ip){
        return $this->_execute(ImenaAPIv2Command::ADD_CHILD_NS, [
            "serviceCode" => "".$code,
            "host" => $host,
            "ip" => $ip
        ]);
    }

    public function DelChildNS($code, $host, $ip){
        return $this->_execute(ImenaAPIv2Command::DEL_CHILD_NS, [
            "serviceCode" => "".$code,
            "host" => $host,
            "ip" => $ip
        ]);
    }

    public function UpdDomainContact($code, $contactType, $contactData){
        return $this->_execute(ImenaAPIv2Command::UPD_CONTACT, [
            "serviceCode" => "".$code,
            "contactType" => $contactType,
            "contact" => $contactData
        ]);
    }

    public function SetPrivacy($code, $disclose = false){
        return $this->_execute(ImenaAPIv2Command::SET_PRIVACY, [
            "serviceCode" => "".$code,
            "whoisPrivacy" => !$disclose
        ]);
    }

    public function ResellerInfo(){
        return $this->_execute(ImenaAPIv2Command::TOKEN_INFO);
    }

    public function ResellerBalance(){
        return $this->_execute(ImenaAPIv2Command::RESELLER_BALANCE);
    }

    public function ResellerPrices(){
        return $this->_execute(ImenaAPIv2Command::RESELLER_PRICES);
    }
}