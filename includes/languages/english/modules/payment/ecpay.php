<?php
	/**
	 * @copyright  Copyright (c) 2017 ECPay (https://www.ecpay.com.tw/)
	 * @version 1.0.1021
	 * @author Shawn.Chang
	*/
			
	# Module description
	define('MODULE_PAYMENT_ECPAY_TITLE_TEXT', 'ECPay');
	define('MODULE_PAYMENT_ECPAY_DESC_TEXT', 'ECPay all in one payment');
	
	# Configurations description
	define('MODULE_PAYMENT_ECPAY_REQUIRE_FIELD_TEXT', 'Required field');
	define('MODULE_PAYMENT_ECPAY_ENABLE_TEXT', 'Enable ECPay Payment');
	define('MODULE_PAYMENT_ECPAY_TEST_MODE_TEXT', 'Test Mode');
	define('MODULE_PAYMENT_ECPAY_TEST_MODE_DESC_TEXT', 'Test order will add date as prefix');
	define('MODULE_PAYMENT_ECPAY_MERCHANT_ID_TEXT', 'Merchant ID');
	define('MODULE_PAYMENT_ECPAY_HASH_KEY_TEXT', 'Hash Key');
	define('MODULE_PAYMENT_ECPAY_HASH_IV_TEXT', 'Hash IV');
	define('MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID_TEXT', 'Order Created Status');
	define('MODULE_PAYMENT_ECPAY_PAID_STATUS_ID_TEXT', 'Paid Status');
	define('MODULE_PAYMENT_ECPAY_UNPAID_STATUS_ID_TEXT', 'Unpaid Status');
	define('MODULE_PAYMENT_ECPAY_AVAILABLE_PAYMENTS_TEXT', 'Available Payments');
	define('MODULE_PAYMENT_ECPAY_AVAILABLE_INSTALLMENTS_TEXT', 'Available Credit Installments');
	define('MODULE_PAYMENT_ECPAY_SORT_ORDER_TEXT', 'Sort Order');
	define('MODULE_PAYMENT_ECPAY_PAYMENT_ZONE_TEXT', 'ECPay Payment Zone');
	define('MODULE_PAYMENT_ECPAY_PAYMENT_ZONE_DESC_TEXT', 'If a zone is selected, only enable ECPay payment for that zone');
	
	# Payments description
	define('MODULE_PAYMENT_ECPAY_CREDIT', 'Credit');
	define('MODULE_PAYMENT_ECPAY_WEBATM', 'WEB-ATM');
	define('MODULE_PAYMENT_ECPAY_ATM', 'ATM');
	define('MODULE_PAYMENT_ECPAY_CVS', 'CVS');
	define('MODULE_PAYMENT_ECPAY_BARCODE', 'BARCODE');
	// define('MODULE_PAYMENT_ECPAY_TENPAY', 'Tenpay');
	// define('MODULE_PAYMENT_ECPAY_TOPUPUSED', 'TopUpUsed');
	define('MODULE_PAYMENT_ECPAY_INSTALLMENT', 'Installments');
	
	# Web description
	define('MODULE_PAYMENT_ECPAY_CHOOSE_PAYMENT_TITLE', 'Payment');
	define('MODULE_PAYMENT_ECPAY_CHOOSE_INSTALLMENT_TITLE', 'Credit installment');
	
	# Product description
	define('MODULE_PAYMENT_ECPAY_PRODUCT_NAME', 'A package of online goods');

	# Order comment
	define('MODULE_PAYMENT_ECPAY_COMMON_COMMENTS', 'Payment Method : %s' . "\n" . 'Trade Time : %s' . "\n");
	define('MODULE_PAYMENT_ECPAY_ATM_COMMENTS', 'Bank Code : %s' . "\n" . 'Virtual Account : %s' . "\n" . 'Payment Deadline : %s' . "\n");
	define('MODULE_PAYMENT_ECPAY_CVS_COMMENTS', 'Trade Code : %s' . "\n" . 'Payment Deadline : %s' . "\n");
	define('MODULE_PAYMENT_ECPAY_BARCODE_COMMENTS', 'Barcode1 : %s' . "\n" . 'Barcode2 : %s' . "\n" . 'Barcode3 : %s' . "\n" . 'Payment Deadline : %s' . "\n");
	define('MODULE_PAYMENT_ECPAY_GET_CODE_RESULT_COMMENTS', 'Getting Code Result : (%s)%s');
	define('MODULE_PAYMENT_ECPAY_PAYMENT_RESULT_COMMENTS', 'Payment Result : (%s)%s');
	define('MODULE_PAYMENT_ECPAY_FAILED_COMMENTS', 'Paid failed');
?>