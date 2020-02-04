<?php

class ImenaAPIv2Command {
    const LOGIN = 'authenticateResellerUser';
    const LOGOUT = 'invalidateAuthToken';
    const TOKEN_INFO = 'getAuthTokenInfo';
    const DOMAINS_LIST = 'getDomainsList';
    const DOMAIN_INFO = 'getDomain';
    const SET_NS = 'editDomainNameserversList';
    const SET_NS_DEFAULT = 'setDomainNameserversToDefault';
    const SET_NS_DNSHOSTING = 'setDomainNameserversToDnshosting';
    const SET_NS_MIROHOST = 'setDomainNameserversToMirohost';
    const ADD_CHILD_NS = 'addDomainChildNameserver';
    const DEL_CHILD_NS = 'deleteDomainChildNameserver';
    const UPD_CONTACT = 'editDomainContact';
    const SET_PRIVACY = 'setDomainPrivacy';
    const RESELLER_BALANCE = 'getResellerBalance';
    const RESELLER_PRICES = 'getResellerPrices';
}

