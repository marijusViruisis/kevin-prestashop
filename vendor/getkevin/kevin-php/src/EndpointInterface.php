<?php

namespace Kevin;

/**
 * Interface to provide base list of supported endpoints.
 */
interface EndpointInterface
{
    /**
     * Base URL used for sending API calls.
     */
    const SERVICE_NAME = '/platform';
    const BASE_PATH_V01 = self::SERVICE_NAME.'/v0.1';
    const BASE_PATH_V02 = self::SERVICE_NAME.'/v0.2';
    const BASE_PATH_V03 = self::SERVICE_NAME.'/v0.3';

    /**
     * List of Auth related endpoints.
     */
    const PATH_COUNTRIES = '/auth/countries';
    const PATH_BANKS = '/auth/banks';
    const PATH_PAYMENT_METHODS = '/auth/paymentMethods';
    const PATH_BANK = '/auth/banks/{bankId}';
    const PATH_PROJECT_SETTINGS = '/auth/project/settings';
    const PATH_AUTH = '/auth';
    const PATH_RECEIVE_TOKEN = '/auth/token';
    const PATH_REFRESH_TOKEN = '/auth/token';
    const PATH_TOKEN_CONTENT = '/auth/token/content';

    /**
     * List of Payment related endpoints.
     */
    const PATH_INIT_PAYMENT = '/pis/payment';
    const PATH_PAYMENT = '/pis/payment/{paymentId}';
    const PATH_PAYMENT_STATUS = '/pis/payment/{paymentId}/status';
    const PATH_INITIATE_PAYMENT_REFUND = '/pis/payment/{paymentId}/refunds';
    const PATH_GET_PAYMENT_REFUNDS = '/pis/payment/{paymentId}/refunds';

    /**
     * List of Account related endpoints.
     */
    const PATH_ACCOUNT_LIST = '/ais/accounts';
    const PATH_ACCOUNT_DETAILS = '/ais/accounts/{accountId}';
    const PATH_ACCOUNT_TRANSACTIONS = '/ais/accounts/{accountId}/transactions';
    const PATH_ACCOUNT_BALANCE = '/ais/accounts/{accountId}/balance';
}
