<?php
    /**
     * @copyright  Copyright (c) 2017 ECPay (https://www.ecpay.com.tw/
     * @version 1.0.180925
     * @author Wesley.Chen
    */
    
    chdir('../../../../');
    require('includes/application_top.php');
    
    function add_comments($order_id, $status, $comments)
    {
        $sql_data_array = array(
            'orders_id' => (int)$order_id
            , 'orders_status_id' => $status
            , 'date_added' => 'now()'
            , 'customer_notified' => '0'
            , 'comments' => $comments
        );
        
        return tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    }
    
    function update_order_status($order_id, $status, $comments)
    {
        $sql_data_array = array(
            'orders_status' => $status
            , 'last_modified' => 'now()'
        );
        $sql_condition = 'orders_id = \'' . (int)$order_id . '\'';
        
        if (!tep_db_perform(TABLE_ORDERS, $sql_data_array, 'update', $sql_condition))
        {
            return false;
        }
        else
        {
            return add_comments($order_id, $status, $comments);
        }
    }
    
    function query_column($sql, $column_name)
    {
        $query = tep_db_query($sql);
        if (tep_db_num_rows($query) > 0)
        {
            $fetch_array = tep_db_fetch_array($query);
        }
        tep_db_free_result($query);
        
        return $fetch_array[$column_name];
    }
                
    # Get the translation
    if (!defined(MODULE_PAYMENT_ECPAY_TITLE_TEXT))
    {
        global $language;
        include(DIR_WS_LANGUAGES . $language . '/modules/payment/ecpay.php');
    }
    
    try
    {
        # Load SDK
        require('ECPay.Payment.Integration.php');
        
        # Set the parameters
        $aio = new ECPay_AllInOne();
        $aio->HashKey = MODULE_PAYMENT_ECPAY_HASH_KEY;
        $aio->HashIV = MODULE_PAYMENT_ECPAY_HASH_IV;
        $aio->MerchantID = MODULE_PAYMENT_ECPAY_MERCHANT_ID;
        
        # Retrieve the check out result
        $aio->EncryptType = ECPay_EncryptType::ENC_SHA256;
        $ecpay_result = $aio->CheckOutFeedback();

        unset($aio);
        
        if(count($ecpay_result) < 1)
        {
            throw new Exception('Get Ecpay feedback failed.');
        }
        else
        {
            # Get osCommerce order id
            $osc_order_id = $ecpay_result['MerchantTradeNo'];
            if (MODULE_PAYMENT_ECPAY_TEST_MODE === 'True')
            {
                $osc_order_id = substr($osc_order_id, 14);
            }
            
            # Get osCommerce order
            require(DIR_WS_CLASSES . 'order.php');
            $order = new order($osc_order_id);
            list($osc_currency, $osc_amount) = explode('$', $order->info['total']);
            
            # Get the order status
            global $languages_id;
            $osc_order_status_id = 0;
            $order_status_sql = 'SELECT `orders_status_id` FROM `' . TABLE_ORDERS_STATUS . '`';
            $order_status_sql .= ' WHERE `orders_status_name` = "' . $order->info['orders_status'] . '"';
            $order_status_sql .= ' AND `language_id` = ' . $languages_id . ';';
            $osc_order_status_id = (int)query_column($order_status_sql, 'orders_status_id');
            unset($order);
            
            # Check the amount
            $ecpay_amount = $ecpay_result['TradeAmt'];
            if (round($osc_amount) != $ecpay_amount)
            {
                throw new Exception('Order ' . $osc_order_id . ' amount are not identical.');
            }
            else
            {
                $success_msg = '1|OK';
                
                # Set the order created status id
                $order_create_status_id = DEFAULT_ORDERS_STATUS_ID;
                if (MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID > 0) {
                    $order_create_status_id = MODULE_PAYMENT_ECPAY_ORDER_CREATE_STATUS_ID;
                }
                
                # Set the order paid status id
                $order_paid_status_id = DEFAULT_ORDERS_STATUS_ID;
                if (MODULE_PAYMENT_ECPAY_PAID_STATUS_ID > 0) {
                    $order_paid_status_id = MODULE_PAYMENT_ECPAY_PAID_STATUS_ID;
                }
                
                # Set the order unpaid status id
                $order_unpaid_status_id = DEFAULT_ORDERS_STATUS_ID;
                if (MODULE_PAYMENT_ECPAY_UNPAID_STATUS_ID > 0) {
                    $order_unpaid_status_id = MODULE_PAYMENT_ECPAY_UNPAID_STATUS_ID;
                }

                # Set the common comments
                $comments = sprintf(
                    MODULE_PAYMENT_ECPAY_COMMON_COMMENTS
                    , $ecpay_result['PaymentType']
                    , $ecpay_result['TradeDate']
                );
                
                # Set the getting code comments
                $return_code = $ecpay_result['RtnCode'];
                $return_message = $ecpay_result['RtnMsg'];
                $get_code_result_comments = sprintf(
                    MODULE_PAYMENT_ECPAY_GET_CODE_RESULT_COMMENTS
                    , $return_code
                    , $return_message
                );
                
                # Set the payment result comments
                $payment_result_comments = sprintf(
                    MODULE_PAYMENT_ECPAY_PAYMENT_RESULT_COMMENTS
                    , $return_code
                    , $return_message
                );
                
                # Get payment and payment target
                list($ecpay_payment_method, $ecpay_payment_target) = explode('_', $ecpay_result['PaymentType']);
                $ecpay_payment_method = strtolower($ecpay_payment_method);
                switch ($ecpay_payment_method)
                {
                    case 'credit':
                    case 'webatm':
                        if ($return_code != 1 and $return_code != 800)
                        {
                            throw new Exception('Order ' . $osc_order_id . ' Exception.(' . $return_code . ': ' . $ecpay_result['RtnMsg'] . ')');
                        }
                        else
                        {
                            # Only finish the order when the status is processing
                            if ($osc_order_status_id != $order_create_status_id)
                            {
                                # The order already paid or not in the standard procedure, do nothing
                            }
                            else
                            {
                                update_order_status(
                                    $osc_order_id
                                    , $order_paid_status_id
                                    , $payment_result_comments
                                );
                            }
                            
                            echo $success_msg;
                        }
                        break;
                    case 'atm':
                        if ($return_code != 1 and $return_code != 2 and $return_code != 800)
                        {
                            throw new Exception('Order ' . $osc_order_id . ' Exception.(' . $return_code . ': ' . $ecpay_result['RtnMsg'] . ')');
                        }
                        else
                        {
                            # Set the extra payment info
                            if ($return_code == 2)
                            {
                                $comments .= sprintf(
                                    MODULE_PAYMENT_ECPAY_ATM_COMMENTS
                                    , $ecpay_result['BankCode']
                                    , $ecpay_result['vAccount']
                                    , $ecpay_result['ExpireDate']
                                );
                                $comments .= $get_code_result_comments;
                                update_order_status($osc_order_id, $osc_order_status_id, $comments);
                            }
                            else
                            {
                                # Only finish the order when the status is processing
                                if ($osc_order_status_id != $order_create_status_id)
                                {
                                    # The order already paid or not in the standard procedure, do nothing
                                }
                                else
                                {
                                    update_order_status(
                                        $osc_order_id
                                        , $order_paid_status_id
                                        , $payment_result_comments
                                    );
                                }
                            }
                            
                            echo $success_msg;
                        }
                        break;
                    case 'cvs':
                        if ($return_code != 1 and $return_code != 800 and $return_code != 10100073)
                        {
                            throw new Exception('Order ' . $osc_order_id . ' Exception.(' . $return_code . ': ' . $ecpay_result['RtnMsg'] . ')');
                        }
                        else
                        {
                            if ($return_code == 10100073)
                            {
                                # Set the extra payment info
                                $comments .= sprintf(
                                    MODULE_PAYMENT_ECPAY_CVS_COMMENTS
                                    , $ecpay_result['PaymentNo']
                                    , $ecpay_result['ExpireDate']
                                );
                                $comments .= $get_code_result_comments;
                                update_order_status($osc_order_id, $osc_order_status_id, $comments);
                            }
                            else
                            {
                                # Only finish the order when the status is processing
                                if ($osc_order_status_id != $order_create_status_id)
                                {
                                    # The order already paid or not in the standard procedure, do nothing
                                }
                                else
                                {
                                    update_order_status(
                                        $osc_order_id
                                        , $order_paid_status_id
                                        , $payment_result_comments
                                    );
                                }
                            }
                            
                            echo $success_msg;
                        }
                        break;


                        case 'barcode':
                        if ($return_code != 1 and $return_code != 800 and $return_code != 10100073)
                        {
                            throw new Exception('Order ' . $osc_order_id . ' Exception.(' . $return_code . ': ' . $ecpay_result['RtnMsg'] . ')');
                        }
                        else
                        {
                            if ($return_code == 10100073)
                            {
                                # Set the extra payment info
                                $comments .= sprintf(
                                    MODULE_PAYMENT_ECPAY_BARCODE_COMMENTS 
                                    , $ecpay_result['Barcode1']
                                    , $ecpay_result['Barcode2']
                                    , $ecpay_result['Barcode3']
                                    , $ecpay_result['ExpireDate']
                                    
                                );
                                $comments .= $get_code_result_comments;
                                update_order_status($osc_order_id, $osc_order_status_id, $comments);
                            }
                            else
                            {
                                # Only finish the order when the status is processing
                                if ($osc_order_status_id != $order_create_status_id)
                                {
                                    # The order already paid or not in the standard procedure, do nothing
                                }
                                else
                                {
                                    update_order_status(
                                        $osc_order_id
                                        , $order_paid_status_id
                                        , $payment_result_comments
                                    );
                                }
                            }
                            
                            echo $success_msg;
                        }
                        break;
                    default:
                        throw new Exception('Invalid payment method of the order ' . $osc_order_id . '.');
                        break;
                }
            }
        }
    }
    catch(Exception $e)
    {
        if (isset($osc_order_id))
        {
            update_order_status($osc_order_id, $order_unpaid_status_id, MODULE_PAYMENT_ECPAY_FAILED_COMMENTS);
        }
        echo '0|' . $e->getMessage();
    }
?>
