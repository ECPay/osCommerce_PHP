<?php
    /**
     * @copyright  Copyright (c) 2017 ECPay (https://www.ecpay.com.tw/)
     * @version 1.0.1021
     * @author Shawn.Chang
    */
            
    # Module description
    define('MODULE_PAYMENT_ECPAY_TITLE_TEXT', '綠界科技整合金流');
    define('MODULE_PAYMENT_ECPAY_DESC_TEXT', '綠界科技整合金流');
    
    # Configurations description
    define('MODULE_PAYMENT_ECPAY_REQUIRE_FIELD_TEXT', '必要欄位');
    define('MODULE_PAYMENT_ECPAY_ENABLE_TEXT', '啟用綠界科技付款模組');
    define('MODULE_PAYMENT_ECPAY_TEST_MODE_TEXT', '測試模式');
    define('MODULE_PAYMENT_ECPAY_TEST_MODE_DESC_TEXT', '測試訂單將加上日期作為前綴');
    define('MODULE_PAYMENT_ECPAY_MERCHANT_ID_TEXT', '特店編號(Merchant ID)');
    define('MODULE_PAYMENT_ECPAY_HASH_KEY_TEXT', 'Hash Key');
    define('MODULE_PAYMENT_ECPAY_HASH_IV_TEXT', 'Hash IV');
    define('MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID_TEXT', '訂單建立狀態');
    define('MODULE_PAYMENT_ECPAY_PAID_STATUS_ID_TEXT', '付款完成狀態');
    define('MODULE_PAYMENT_ECPAY_UNPAID_STATUS_ID_TEXT', '未付款狀態');
    define('MODULE_PAYMENT_ECPAY_AVAILABLE_PAYMENTS_TEXT', '有效付款方式');
    define('MODULE_PAYMENT_ECPAY_AVAILABLE_INSTALLMENTS_TEXT', '有效信用卡分期期數');
    define('MODULE_PAYMENT_ECPAY_SORT_ORDER_TEXT', '優先順序代號');
    define('MODULE_PAYMENT_ECPAY_PAYMENT_ZONE_TEXT', '模組適用地區');
    define('MODULE_PAYMENT_ECPAY_PAYMENT_ZONE_DESC_TEXT', '若選擇模組適用地區, 模組僅能使用於該地區');
    
    # Payments description
    define('MODULE_PAYMENT_ECPAY_CREDIT', '信用卡');
    define('MODULE_PAYMENT_ECPAY_WEBATM', '網路 ATM');
    define('MODULE_PAYMENT_ECPAY_ATM', 'ATM');
    define('MODULE_PAYMENT_ECPAY_CVS', '超商代碼');
    define('MODULE_PAYMENT_ECPAY_BARCODE', '超商條碼');
    // define('MODULE_PAYMENT_ECPAY_TENPAY', '財付通');
    // define('MODULE_PAYMENT_ECPAY_TOPUPUSED', '儲值/餘額消費');
    define('MODULE_PAYMENT_ECPAY_INSTALLMENT', '期');
    
    # Web description
    define('MODULE_PAYMENT_ECPAY_CHOOSE_PAYMENT_TITLE', '付款方式');
    define('MODULE_PAYMENT_ECPAY_CHOOSE_INSTALLMENT_TITLE', '信用卡分期期數');
    
    # Product description
    define('MODULE_PAYMENT_ECPAY_PRODUCT_NAME', '網路商品一批');

    # Order comment
    define('MODULE_PAYMENT_ECPAY_COMMON_COMMENTS', '付款方式 : %s' . "\n" . '付款時間 : %s' . "\n");
    define('MODULE_PAYMENT_ECPAY_ATM_COMMENTS', '銀行代碼 : %s' . "\n" . '虛擬帳號 : %s' . "\n" . '付款截止日 : %s' . "\n");
    define('MODULE_PAYMENT_ECPAY_CVS_COMMENTS', '繳費代碼 : %s' . "\n" . '付款截止日 : %s' . "\n");
    define('MODULE_PAYMENT_ECPAY_BARCODE_COMMENTS', '第一段條碼 : %s' . '第二段條碼 : %s' . '第三段條碼 : %s' . "\n" . '付款截止日 : %s' . "\n");

    define('MODULE_PAYMENT_ECPAY_GET_CODE_RESULT_COMMENTS', '取號結果 : (%s)%s');
    define('MODULE_PAYMENT_ECPAY_PAYMENT_RESULT_COMMENTS', '付款結果 : (%s)%s');
    define('MODULE_PAYMENT_ECPAY_FAILED_COMMENTS', '付款失敗');
?>