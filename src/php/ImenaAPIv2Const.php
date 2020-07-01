<?php

namespace Services\ImenaV2;

class ImenaAPIv2Const {
    const COMMAND_LOGIN = 'authenticateResellerUser';
    const COMMAND_LOGOUT = 'invalidateAuthToken';
    const COMMAND_TOKEN_INFO = 'getAuthTokenInfo';
    const COMMAND_DOMAINS_LIST = 'getDomainsList';
    const COMMAND_DOMAIN_INFO = 'getDomain';
    const COMMAND_SET_NS = 'editDomainNameserversList';
    const COMMAND_SET_NS_DEFAULT = 'setDomainNameserversToDefault';
    const COMMAND_SET_NS_DNSHOSTING = 'setDomainNameserversToDnshosting';
    const COMMAND_SET_NS_MIROHOST = 'setDomainNameserversToMirohost';
    const COMMAND_ADD_CHILD_NS = 'addDomainChildNameserver';
    const COMMAND_DEL_CHILD_NS = 'deleteDomainChildNameserver';
    const COMMAND_UPD_CONTACT = 'editDomainContact';
    const COMMAND_SET_PRIVACY = 'setDomainPrivacy';
    const COMMAND_RESELLER_BALANCE = 'getResellerBalance';
    const COMMAND_RESELLER_PRICES = 'getResellerPrices';
    const COMMAND_CREATE_RENEW_PAYMENT = 'createDomainRenewPayment';
    const COMMAND_CREATE_REGISTRATION_PAYMENT = 'createDomainRegistrationPayment';
    const COMMAND_CREATE_REGISTRATION_ORDER = 'createDomainRegistrationOrder';
    const COMMAND_CREATE_TRANSFER_PAYMENT = 'createDomainTransferPayment';
    const COMMAND_CREATE_TRANSFER_ORDER = 'createDomainTransferOrder';
    const COMMAND_DELETE_ORDER = 'deleteDomainOrder';
    const COMMAND_PAYMENT_STATUS = 'getResellerPaymentStatus';

    const CONTACT_ADMIN = 'admin-c';
    const CONTACT_TECH = 'tech-c';
    const CONTACT_OWNER = 'owner-c';
    const CONTACT_BILLING = 'owner-c';

    const HOSTING_TYPE_MIROHOST = 'mirohost';
    const HOSTING_TYPE_DNS = 'dnshosting';
    const HOSTING_TYPE_DEFAULTS = 'default';

    const PAYMENT_STATUS_NEW = 'new';
    const PAYMENT_STATUS_PROCESS = 'inProcess';
    const PAYMENT_STATUS_SUCCESS = 'success';
    const PAYMENT_STATUS_RETURNED = 'returned';
    const PAYMENT_STATUS_DELETED = 'deleted';

    const SECOND_AUTH_SMS = 'sms';
    const SECOND_AUTH_GOOGLE = 'google';
}
