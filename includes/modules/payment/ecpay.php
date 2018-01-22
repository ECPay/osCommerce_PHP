<?php
/**
 * @copyright  Copyright (c) 2015 ECPay (https://www.ecpay.com.tw/)
 * @version 1.0.1021
 * @author Shawn.Chang
*/

class ecpay
{
    var $code, $title, $description, $enabled;
    
    # Necessary functions
    function ecpay()
    {
        $this->code = 'ecpay';
        $this->title = MODULE_PAYMENT_ECPAY_TITLE_TEXT;
        $this->description = MODULE_PAYMENT_ECPAY_DESC_TEXT;
        $this->enabled = ((MODULE_PAYMENT_ECPAY_ENABLE_STATUS == 'True') ? true : false);
        
        if ((int)MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID;
        }
        
        global $order;
        if (is_object($order)) {
            $this->update_status();
            }
        
        $this->form_action_url = tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
    }

    function install()
    {
        $config_list = $this->get_ecpay_config();
        $insert_fields = array(
            'configuration_title'
            , 'configuration_key'
            , 'configuration_value'
            , 'configuration_description'
            , 'configuration_group_id'
            , 'set_function'
            , 'use_function'
        );
        foreach ($config_list as $sort_order => $config_row)
        {
            $insert_columns = 'sort_order, date_added, configuration_group_id';
            $insert_values = '"' . $sort_order++ . '", now(), "6"';
            $comma = ', ';
            foreach ($insert_fields as $insert_field)
            {
                if (isset($config_row[$insert_field]))
                {
                    $insert_columns .= $comma . $insert_field;
                    $insert_values .= $comma . '"' . $config_row[$insert_field] . '"';
                }
            }
            $insert_sql = 'INSERT INTO ' . TABLE_CONFIGURATION . ' (' . $insert_columns . ')';
            $insert_sql .= ' VALUES (' . $insert_values . ');';
            tep_db_query($insert_sql);
        }
        }

    function remove()
    {
        $delete_sql = 'DELETE FROM ' . TABLE_CONFIGURATION;
        $delete_sql .= ' WHERE configuration_key in ("' . implode('", "', $this->keys()) . '");';
        tep_db_query($delete_sql);
    }
    
    function check()
    {
        if (!isset($this->_check)) {
            $select_sql = 'SELECT configuration_value FROM ' . TABLE_CONFIGURATION;
            $select_sql .= ' WHERE configuration_key = "MODULE_PAYMENT_ECPAY_ENABLE_STATUS";';
            $check_query = tep_db_query($select_sql);
            $this->_check = tep_db_num_rows($check_query);
        }
        
        return $this->_check;
    }
    
    function keys()
    {
        $config_list = $this->get_ecpay_config();
        $keys = array();
        foreach ($config_list as $config_row)
        {
            array_push($keys, $config_row['configuration_key']);
        }
        
        return $keys;
    }
    
    function javascript_validation()
    {
        return false;
    }
    
    function selection()
    {
        $selection = array(
            'id' => $this->code
            , 'module' => $this->title
        );
        
        # Installments javascript
        $js = ecpay_set_html('<script type="text/javascript">');
        $js .= ecpay_set_html('  function ecpay_enable_installments()');
        $js .= ecpay_set_html('  {');
        $js .= ecpay_set_html('    var ecpay_choose_payment = $("select[name=ecpay_choose_payment]");');
        $js .= ecpay_set_html('    var ecpay_choose_installment = $("select[name=ecpay_choose_installment]");');
        $js .= ecpay_set_html('    if (ecpay_choose_payment.val() == "Credit")');
        $js .= ecpay_set_html('    {');
        $js .= ecpay_set_html('      ecpay_choose_installment.removeAttr("disabled");');
        $js .= ecpay_set_html('    }');
        $js .= ecpay_set_html('    else');
        $js .= ecpay_set_html('    {');
        $js .= ecpay_set_html('      ecpay_choose_installment[0].selectedIndex = 0;');
        $js .= ecpay_set_html('      ecpay_choose_installment.attr("disabled", true);');
        $js .= ecpay_set_html('    }');
        $js .= ecpay_set_html('  }');
        $js .= ecpay_set_html('  $(function() {');
        $js .= ecpay_set_html('    ecpay_enable_installments();');
        $js .= ecpay_set_html('    $("select[name=ecpay_choose_payment]").change(function(){');
        $js .= ecpay_set_html('      ecpay_enable_installments();');
        $js .= ecpay_set_html('    });');
        $js .= ecpay_set_html('  });');
        $js .= ecpay_set_html('</script>');
        
        # Get the available payments
        $selection['fields'] = array();
        array_push(
            $selection['fields']
            , array(
                'title' => MODULE_PAYMENT_ECPAY_CHOOSE_PAYMENT_TITLE . '&nbsp;:&nbsp;'
                , 'field' => tep_draw_pull_down_menu('ecpay_choose_payment', $this->get_selection_payments(MODULE_PAYMENT_ECPAY_AVAILABLE_PAYMENTS)) . $js
            )
        );
        
        # Get the credit installments
        if (MODULE_PAYMENT_ECPAY_AVAILABLE_INSTALLMENTS)
        {
            array_push(
                $selection['fields']
                , array(
                    'title' => MODULE_PAYMENT_ECPAY_CHOOSE_INSTALLMENT_TITLE . '&nbsp;:&nbsp;'
                    , 'field' => tep_draw_pull_down_menu(
                        'ecpay_choose_installment', $this->get_selection_field('0,' . MODULE_PAYMENT_ECPAY_AVAILABLE_INSTALLMENTS))
                )
            );
        }
        
        return $selection;
    }
    
    function pre_confirmation_check()
    {
        return false;
    }
    
    function confirmation()
    {
        $ecpay_choose_payment = $this->check_payment($_POST['ecpay_choose_payment']);
        $confirmation = array(
            'title' => $this->title
            , 'fields' => array()
        );
        array_push(
            $confirmation['fields']
            , array('title' => MODULE_PAYMENT_ECPAY_CHOOSE_PAYMENT_TITLE . ' : ', 'field' => get_ecpay_payment_description($ecpay_choose_payment))
        );
        
        $ecpay_choose_installment = $this->check_installment($_POST['ecpay_choose_installment'], $ecpay_choose_payment);
        if ($ecpay_choose_installment > 0)
        {
            array_push(
                $confirmation['fields']
                , array('title' => MODULE_PAYMENT_ECPAY_CHOOSE_INSTALLMENT_TITLE . ' : ', 'field' => $ecpay_choose_installment)
            );
        }
        
        return $confirmation;
    }
    
    function process_button()
    {
        $choose_payment = $this->check_payment($_POST['ecpay_choose_payment']);
        $process_button_string = tep_draw_hidden_field('ecpay_choose_payment', $ecpay_choose_payment);
        $process_button_string .= tep_draw_hidden_field('ecpay_choose_installment', $this->check_installment($_POST['ecpay_choose_installment'], $ecpay_choose_payment));
        
        return $process_button_string;
    }
    
    function before_process()
    {
        global $order, $merchant_trade_no;
        
        # Set the chosen payment
        $ecpay_choose_payment = $this->check_payment($_POST['ecpay_choose_payment']);
        $payment_method = '-' . get_ecpay_payment_description($ecpay_choose_payment);
        $ecpay_choose_installment = (int)($this->check_installment($_POST['ecpay_choose_installment'], $ecpay_choose_payment));
        if ($ecpay_choose_installment > 0)
        {
            $payment_method .= '-' . $ecpay_choose_installment . MODULE_PAYMENT_ECPAY_INSTALLMENT;
        }
        $order->info['payment_method'] .= $payment_method;
        
        return false;
    }
    
    function after_process()
    {
        header('Content-Type: text/html; charset=utf-8');
        try
        {
            # Load SDK
            require(DIR_FS_CATALOG . 'ext/modules/payment/ecpay/ECPay.Payment.Integration.php');
            
            # Set  parameters
            $aio = new ECPay_AllInOne();
            $aio->Send['MerchantTradeNo'] = '';
            if (MODULE_PAYMENT_ECPAY_TEST_MODE)
            {
                $service_url = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V4';
                $aio->Send['MerchantTradeNo'] = date('YmdHis');
            }
            else
            {
                $service_url = 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V4';
            }
            $aio->MerchantID = MODULE_PAYMENT_ECPAY_MERCHANT_ID;
            $aio->HashKey = MODULE_PAYMENT_ECPAY_HASH_KEY;
            $aio->HashIV = MODULE_PAYMENT_ECPAY_HASH_IV;
            $aio->ServiceURL = $service_url;
            $aio->EncryptType = ECPay_EncryptType::ENC_SHA256;
            $aio->Send['ReturnURL'] = tep_href_link('ext/modules/payment/ecpay/response.php', '', 'SSL');
            $aio->Send['ClientBackURL'] = tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL');
            
            global $insert_id;
            $aio->Send['MerchantTradeNo'] .= $insert_id;
            $aio->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');
            
            # Set the product info
            global $order;
            $total_price = round($order->info['total']);
            array_push(
                $aio->Send['Items']
                , array(
                    'Name' => MODULE_PAYMENT_ECPAY_PRODUCT_NAME
                    , 'Price' => $total_price
                    , 'Currency' => $order->info['currency']
                    , 'Quantity' => 1
                )
            );
            $aio->Send['TotalAmount'] = $total_price;
            $aio->Send['TradeDesc'] = 'ecpay_module_oscommerce_1.0.1221';
            
            # Set the payment
            $ecpay_choose_payment = $this->check_payment($_POST['ecpay_choose_payment']);
            $aio->Send['ChoosePayment'] = $ecpay_choose_payment;
            
            # Set the parameters by payment
            $ecpay_choose_installment = 0;
            switch ($aio->Send['ChoosePayment'])
            {
                case 'ATM':
                    $aio->SendExtend['ExpireDate'] = 3;
                    $aio->SendExtend['PaymentInfoURL'] = $aio->Send['ReturnURL'];
                    break;
                case 'CVS':
                    $aio->SendExtend['Desc_1'] = '';
                    $aio->SendExtend['Desc_2'] = '';
                    $aio->SendExtend['Desc_3'] = '';
                    $aio->SendExtend['Desc_4'] = '';
                    $aio->SendExtend['PaymentInfoURL'] = $aio->Send['ReturnURL'];
                    break;

                case 'BARCODE':
                    $aio->SendExtend['Desc_1'] = '';
                    $aio->SendExtend['Desc_2'] = '';
                    $aio->SendExtend['Desc_3'] = '';
                    $aio->SendExtend['Desc_4'] = '';
                    $aio->SendExtend['PaymentInfoURL'] = $aio->Send['ReturnURL'];
                    break;

                // case 'Tenpay':
                //     $aio->SendExtend['ExpireTime'] = date('Y/m/d H:i:s', strtotime('+3 days'));
                //     break;
                
                case 'Credit':
                    # Do not support UnionPay
                    $aio->SendExtend['UnionPay'] = false;
                    
                    $ecpay_choose_installment = $this->check_installment($_POST['ecpay_choose_installment'], $ecpay_choose_payment);
                    
                    # Credit installment parameters
                    if ($ecpay_choose_installment > 0)
                    {
                        $aio->SendExtend['CreditInstallment'] = $ecpay_choose_installment;
                        $aio->SendExtend['InstallmentAmount'] = $aio->Send['TotalAmount'];
                        $aio->SendExtend['Redeem'] = false;
                    }
                    break;
            }
            
            global $cart;
            $cart->reset(true);
            
            # Unregister session variables used during checkout
            tep_session_unregister('sendto');
            tep_session_unregister('billto');
            tep_session_unregister('shipping');
            tep_session_unregister('payment');
            tep_session_unregister('comments');
            
            $aio->CheckOut();
        }
        catch(Exception $e)
        {
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($e->getMessage()), 'SSL'));
        }
        exit;
    }
    
    
    # Optional functions
    function update_status()
    {
        global $order;
        
        # Check the payment enabled zone
        if ( ($this->enabled == true) and ((int)MODULE_PAYMENT_ECPAY_PAYMENT_ZONE > 0))
        {
            $check_flag = false;
            $check_sql = 'SELECT `zone_id` FROM `' . TABLE_ZONES_TO_GEO_ZONES . '`';
            $check_sql .= ' WHERE `geo_zone_id` = "' . MODULE_PAYMENT_ECPAY_PAYMENT_ZONE . '"';
            $check_sql .= ' AND `zone_country_id` = "' . $order->delivery['country']['id'] . '"';
            $check_sql .= ' AND `zone_country_id` = "' . $order->delivery['country']['id'] . '"';
            $check_query = tep_db_query($check_sql);
            while ($check = tep_db_fetch_array($check_query))
            {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }
            
            if (!$check_flag)
            {
                $this->enabled = false;
            }
        }
    }
            
    # Custom functions
    function get_ecpay_config()
    {
        # Get the translation
        if (!defined(MODULE_PAYMENT_ECPAY_TITLE_TEXT))
        {
            global $language;
            include(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/payment/ecpay.php');
        }
        
        $require_mark = '<span style=\"color: #F00;\">*</span>';
        $ecpay_config = array(
            array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_ENABLE_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_ENABLE_STATUS'
                , 'configuration_value' => 'True'
                , 'configuration_description' => ''
                , 'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_TEST_MODE_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_TEST_MODE'
                , 'configuration_value' => 'True'
                , 'configuration_description' => MODULE_PAYMENT_ECPAY_TEST_MODE_DESC_TEXT
                , 'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            )
            , array(
                'configuration_title' => $require_mark . MODULE_PAYMENT_ECPAY_MERCHANT_ID_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_MERCHANT_ID'
                , 'configuration_value' => '2000132'
                , 'configuration_description' => '(' . MODULE_PAYMENT_ECPAY_REQUIRE_FIELD_TEXT . ')'
            )
            , array(
                'configuration_title' => $require_mark . MODULE_PAYMENT_ECPAY_HASH_KEY_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_HASH_KEY'
                , 'configuration_value' => '5294y06JbISpM5x9'
                , 'configuration_description' => '(' . MODULE_PAYMENT_ECPAY_REQUIRE_FIELD_TEXT . ')'
            )
            , array(
                'configuration_title' => $require_mark . MODULE_PAYMENT_ECPAY_HASH_IV_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_HASH_IV'
                , 'configuration_value' => 'v77hoKGq4kWxNNIS'
                , 'configuration_description' => '(' . MODULE_PAYMENT_ECPAY_REQUIRE_FIELD_TEXT . ')'
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID'
                , 'configuration_value' => ''
                , 'configuration_description' => ''
                , 'set_function' => 'tep_cfg_pull_down_order_statuses('
                , 'use_function' => 'tep_get_order_status_name'
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_PAID_STATUS_ID_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_PAID_STATUS_ID'
                , 'configuration_value' => ''
                , 'configuration_description' => ''
                , 'set_function' => 'tep_cfg_pull_down_order_statuses('
                , 'use_function' => 'tep_get_order_status_name'
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_UNPAID_STATUS_ID_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_UNPAID_STATUS_ID'
                , 'configuration_value' => ''
                , 'configuration_description' => ''
                , 'set_function' => 'tep_cfg_pull_down_order_statuses('
                , 'use_function' => 'tep_get_order_status_name'
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_AVAILABLE_PAYMENTS_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_AVAILABLE_PAYMENTS'
                , 'configuration_value' => ''
                , 'configuration_description' => ''
                , 'use_function' => 'ecpay_display_multi_config'
                , 'set_function' => 'ecpay_checkbox_payments('
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_AVAILABLE_INSTALLMENTS_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_AVAILABLE_INSTALLMENTS'
                , 'configuration_value' => ''
                , 'configuration_description' => ''
                , 'use_function' => 'ecpay_display_multi_config'
                , 'set_function' => 'ecpay_checkbox_installments('
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_SORT_ORDER_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_SORT_ORDER'
                , 'configuration_value' => ''
                , 'configuration_description' => ''
            )
            , array(
                'configuration_title' => MODULE_PAYMENT_ECPAY_PAYMENT_ZONE_TEXT
                , 'configuration_key' => 'MODULE_PAYMENT_ECPAY_PAYMENT_ZONE'
                , 'configuration_value' => ''
                , 'configuration_description' => MODULE_PAYMENT_ECPAY_PAYMENT_ZONE_DESC_TEXT . '.'
                , 'use_function' => 'tep_get_zone_class_title'
                , 'set_function' => 'tep_cfg_pull_down_zone_classes('
            )
        );
        
        return $ecpay_config;
    }

    function get_selection_payments($available_payments)
    {
        $payments_array = explode(',', $available_payments);
        $selection_payments = array();
        foreach($payments_array as $payment)
        {
            array_push($selection_payments, array('id' => $payment, 'text' => get_ecpay_payment_description($payment)));
        }
        
        return $selection_payments;
    }

    function get_selection_field($source_string)
    {
        $value_array = explode(',', $source_string);
        $selection_field = array();
        foreach($value_array as $value)
        {
            if ($value != '')
            {
                array_push($selection_field, array('id' => $value, 'text' => $value));
            }
        }
        
        return $selection_field;
    }

    function check_payment($ecpay_choose_payment)
    {
        if (!in_array($ecpay_choose_payment, get_ecpay_payments()))
        {
            $ecpay_choose_payment = '';
        }
            
        return $ecpay_choose_payment;
    }
    
    function check_installment($ecpay_choose_installment, $ecpay_choose_payment)
    {
        $installment = 0;
        if ($ecpay_choose_payment == 'Credit')
        {
            if (in_array($ecpay_choose_installment, get_ecpay_credit_installments()))
            {
                $installment = $ecpay_choose_installment;
            }
        }
        
        return $installment;
    }
}

# Custom functions
define('PAYMENT_CHECKBOX_NAME', 'ecpay_payments');
define('PAYMENT_HIDDEN_NAME', 'available_payments');
define('INSTALLMENT_CHECKBOX_NAME', 'credit_installments');
define('INSTALLMENT_HIDDEN_NAME', 'available_installments');
function get_ecpay_payments()
{
    return array(
        'Credit'
        , 'WebATM'
        , 'ATM'
        , 'CVS'
        , 'BARCODE'
    );
}

function get_ecpay_payment_description($payment)
{
    $payment_desc = array(
        'Credit' => MODULE_PAYMENT_ECPAY_CREDIT
        , 'WebATM' => MODULE_PAYMENT_ECPAY_WEBATM
        , 'ATM' => MODULE_PAYMENT_ECPAY_ATM
        , 'CVS' => MODULE_PAYMENT_ECPAY_CVS
        , 'BARCODE' => MODULE_PAYMENT_ECPAY_BARCODE
    );
    
    return $payment_desc[$payment];
}

function ecpay_set_html($html_content)
{
    return $html_content . "\n";
}

function ecpay_display_multi_config($config_values)
{
    return nl2br(implode(" / ", explode(',', $config_values)));
}

function ecpay_checkbox_payments($payments, $config_key)
{
    $payments_list = explode(',', $payments);
    $output = '';
    $ecpay_payments = get_ecpay_payments();
    foreach($ecpay_payments as $payment)
    {
        $tmp_output = tep_draw_checkbox_field(PAYMENT_CHECKBOX_NAME . '[]', $payment, in_array($payment, $payments_list));
        $tmp_output .= '&nbsp;' . tep_output_string(get_ecpay_payment_description($payment)) . '<br />' . "\n";
        $output .= ecpay_set_html($tmp_output);
    }
    $output .= tep_draw_hidden_field('configuration[' . $config_key . ']', '', 'id="' . PAYMENT_HIDDEN_NAME . '"');
    $js_function = 'update_payments_config';
    $output .= ecpay_set_html('<script type="text/javascript">');
    $output .= ecpay_set_html('  function ' . $js_function . '()');
    $output .= ecpay_set_html('  {');
    $output .= ecpay_set_html('    var hidden_value = "";');
    $output .= ecpay_set_html('    if($("input[name=\'' . PAYMENT_CHECKBOX_NAME . '[]\']").length > 0)');
    $output .= ecpay_set_html('    {');
    $output .= ecpay_set_html('      var comma = "";');
    $output .= ecpay_set_html('      $("input[name=\'' . PAYMENT_CHECKBOX_NAME . '[]\']:checked").each(function() {');
    $output .= ecpay_set_html('        hidden_value += comma + $(this).attr("value");');
    $output .= ecpay_set_html('        comma = ",";');
    $output .= ecpay_set_html('      });');
    $output .= ecpay_set_html('      $("#' . PAYMENT_HIDDEN_NAME . '").val(hidden_value);');
    $output .= ecpay_set_html('    }');
    $output .= ecpay_set_html('    if (hidden_value.indexOf("Credit") < 0)');
    $output .= ecpay_set_html('    {');
    $output .= ecpay_set_html('      $("input[name=\'' . INSTALLMENT_CHECKBOX_NAME . '[]\']").each(function() {');
    $output .= ecpay_set_html('        $(this).prop("checked", false);');
    $output .= ecpay_set_html('      });');
    $output .= ecpay_set_html('      $("input[name=\'' . INSTALLMENT_CHECKBOX_NAME . '[]\']").attr("disabled", true);');
    $output .= ecpay_set_html('      $("input[name=\'' . INSTALLMENT_CHECKBOX_NAME . '[]\']").trigger("change")');
    $output .= ecpay_set_html('    }');
    $output .= ecpay_set_html('    else');
    $output .= ecpay_set_html('    {');
    $output .= ecpay_set_html('      $("input[name=\'' . INSTALLMENT_CHECKBOX_NAME . '[]\']").removeAttr("disabled")');
    $output .= ecpay_set_html('    }');
    $output .= ecpay_set_html('  }');
    $output .= ecpay_get_intergrate_checkbox_js($js_function, PAYMENT_CHECKBOX_NAME);
    $output .= ecpay_set_html('</script>');
    
    return $output;
}


function get_ecpay_credit_installments()
{
    return array('3', '6', '12', '18', '24');
}

function ecpay_checkbox_installments($installments, $config_key)
{
    $installments_list = explode(',', $installments);
    $output = '';
    $credit_installments = get_ecpay_credit_installments();
    foreach($credit_installments as $installment)
    {
        $tmp_output = tep_draw_checkbox_field(INSTALLMENT_CHECKBOX_NAME . '[]', $installment, in_array($installment, $installments_list));
        $tmp_output .= '&nbsp;' . tep_output_string($installment) . '<br />' . "\n";
        $output .= ecpay_set_html($tmp_output);
    }
    $output .= tep_draw_hidden_field('configuration[' . $config_key . ']', '', 'id="' . INSTALLMENT_HIDDEN_NAME . '"');
    $js_function = 'update_installments_config';
    $output .= ecpay_set_html('<script type="text/javascript">');
    $output .= ecpay_set_html('  function ' . $js_function . '()');
    $output .= ecpay_set_html('  {');
    $output .= ecpay_set_html('    if($("input[name=\'' . INSTALLMENT_CHECKBOX_NAME . '[]\']").length > 0)');
    $output .= ecpay_set_html('    {');
    $output .= ecpay_set_html('      var hidden_value = "";');
    $output .= ecpay_set_html('      var comma = "";');
    $output .= ecpay_set_html('      $("input[name=\'' . INSTALLMENT_CHECKBOX_NAME . '[]\']:checked").each(function() {');
    $output .= ecpay_set_html('        hidden_value += comma + $(this).attr("value");');
    $output .= ecpay_set_html('        comma = ",";');
    $output .= ecpay_set_html('      });');
    $output .= ecpay_set_html('      $("#' . INSTALLMENT_HIDDEN_NAME . '").val(hidden_value);');
    $output .= ecpay_set_html('    }');
    $output .= ecpay_set_html('  }');
    $output .= ecpay_get_intergrate_checkbox_js($js_function, INSTALLMENT_CHECKBOX_NAME);
    $output .= ecpay_set_html('</script>');
    
    return $output;
}

function ecpay_get_intergrate_checkbox_js($js_function, $checkbox_name)
{
    $output .= ecpay_set_html('  $(function() {');
    $output .= ecpay_set_html('    ' . $js_function . '();');
    $output .= ecpay_set_html('    $("input[name=\'' . $checkbox_name . '[]\']").change(function() {');
    $output .= ecpay_set_html('      ' . $js_function . '();');
    $output .= ecpay_set_html('    });');
    $output .= ecpay_set_html('  });');
    
    return $output;
}
    
?>
