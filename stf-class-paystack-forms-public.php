<?php
/**
* Project: A tool to extend Paystacks payment form
* Description: Extends Paystacks payment form
* Version: 1.0
* Author: Laud Tetteh
* Author URI: https://www.studiotenfour.com
*/

// Check if the Paystack Plugin Class exists
if( class_exists('Kkd_Pff_Paystack_Public') ) {

    class STF_Paystack_Public extends Kkd_Pff_Paystack_Public
    {
        private $plugin_name;
        private $version;

        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }
        public function enqueue_styles()
        {
            wp_enqueue_style($this->plugin_name . '1', './css/pff-paystack-style.css', array(), $this->version, 'all');
            wp_enqueue_style($this->plugin_name . '2', './css/font-awesome.min.css', array(), $this->version, 'all');
        }

        public static function fetchPublicKey()
        {
            $mode =  esc_attr(get_option('mode'));
            if ($mode == 'test') {
                $key = esc_attr(get_option('tpk'));
            } else {
                $key = esc_attr(get_option('lpk'));
            }
            return $key;
        }

        public static function fetchFeeSettings()
        {
            $ret = [];
            $ret['prc'] = intval(floatval(esc_attr(get_option('prc', 1.5))) * 100) / 10000;
            $ret['ths'] = intval(floatval(esc_attr(get_option('ths', 2500))) * 100);
            $ret['adc'] = intval(floatval(esc_attr(get_option('adc', 100))) * 100);
            $ret['cap'] = intval(floatval(esc_attr(get_option('cap', 2000))) * 100);
            return $ret;
        }

        public function enqueue_scripts()
        {
            wp_enqueue_script('blockUI', plugin_dir_url(__FILE__) . 'js/jquery.blockUI.min.js', array('jquery'), $this->version, true, true);
            wp_enqueue_script('jQuery_UI', plugin_dir_url(__FILE__) . 'js/jquery.ui.min.js', array('jquery'), $this->version, true, true);
            wp_register_script('Paystack', 'https://js.paystack.co/v1/inline.js', false, '1');
            wp_enqueue_script('Paystack');
            wp_enqueue_script('paystack_frontend', plugin_dir_url(__FILE__) . 'js/paystack-forms-public.js', array('jquery'), $this->version, true, true);
            wp_localize_script('paystack_frontend', 'kkd_pff_settings', array('key' => Kkd_Pff_Paystack_Public::fetchPublicKey(), 'fee' => Kkd_Pff_Paystack_Public::fetchFeeSettings()), $this->version, true, true);
        }
    }

    function stf_pff_paystack_form_shortcode($atts) {
        ob_start();

        global $current_user;
        $user_id = $current_user->ID;
        $email = $current_user->user_email;
        $fname = $current_user->user_firstname;
        $lname = $current_user->user_lastname;
        if ($fname == '' && $lname == '') {
            $fullname = '';
        } else {
            $fullname = $fname . ' ' . $lname;
        }
        extract(
            shortcode_atts(
                array(
                    'id' => 0,
                ),
                $atts
            )
        );
        $pk = Kkd_Pff_Paystack_Public::fetchPublicKey();
        if (!$pk) {
            $settingslink = get_admin_url() . 'edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php';
            echo "<h5>You must set your Paystack API keys first <a href='" . $settingslink . "'>settings</a></h5>";
        } elseif ($id != 0) {
            $obj = get_post($id);
            if ($obj->post_type == 'paystack_form') {
                $amount = get_post_meta($id, '_amount', true);
                $thankyou = get_post_meta($id, '_successmsg', true);
                $paybtn = get_post_meta($id, '_paybtn', true);
                $loggedin = get_post_meta($id, '_loggedin', true);
                $txncharge = get_post_meta($id, '_txncharge', true);
                $currency = get_post_meta($id, '_currency', true);
                $recur = get_post_meta($id, '_recur', true);
                $recurplan = get_post_meta($id, '_recurplan', true);
                $usequantity = get_post_meta($id, '_usequantity', true);
                $quantity = get_post_meta($id, '_quantity', true);
                $quantityunit = get_post_meta($id, '_quantityunit', true);
                $useagreement = get_post_meta($id, '_useagreement', true);
                $agreementlink = get_post_meta($id, '_agreementlink', true);
                $minimum = get_post_meta($id, '_minimum', true);
                $variableamount = get_post_meta($id, '_variableamount', true);
                $usevariableamount = get_post_meta($id, '_usevariableamount', true);
                $hidetitle = get_post_meta($id, '_hidetitle', true);
                $contact_phone = get_field( 'contact_phone', 'option' );
                $current_projects = stf_get_current_projects('ids', -1);

                if ($minimum == "") {
                    $minimum = 0;
                }
                if ($usevariableamount == "") {
                    $usevariableamount = 0;
                }
                if ($usevariableamount == 1) {
                    $paymentoptions = explode(',', $variableamount);
                    // echo "<pre>";
                    // print_r($paymentoptions);
                    // echo "</pre>";
                    // die();
                }
                $showbtn = true;
                $planerrorcode = 'Input Correct Recurring Plan Code';
                if ($recur == 'plan') {
                    if ($recurplan == '' || $recurplan == null) {
                        $showbtn = false;
                    } else {
                        $plan =    kkd_pff_paystack_fetch_plan($recurplan);
                        if (isset($plan->data->amount)) {
                            $planamount = $plan->data->amount / 100;
                        } else {
                            $showbtn = false;
                        }
                    }
                }
                $useinventory = get_post_meta($id, '_useinventory', true);
                $inventory = get_post_meta($id, '_inventory', true);
                $sold = get_post_meta($id, '_sold', true);
                if ($inventory == "") {
                    $inventory = '1';
                }
                if ($sold == "") {
                    $sold = '0';
                }
                if ($useinventory == "") {
                    $useinventory = "no";
                }
                $stock = $inventory - $sold;
            }
        }

                echo '<section class="xs-section-padding bg-gray">
                <div class="container">
                <div class="row">

                <div class="xs-donation-form-wraper" >
                    <div class="xs-heading xs-mb-30">
                        <br>
                        <h2 class="xs-title">Make a donation</h2>
                        <span class="xs-separetor v2"></span>
                    </div><!-- .xs-heading end -->';

                echo '<form version="' . KKD_PFF_PAYSTACK_VERSION . '" data-test="laud" enctype="multipart/form-data" action="' . admin_url('admin-ajax.php') . '" url="' . admin_url() . '" method="post" class="stf-paystack-form paystack-form j-forms xs-donation-form" id="donate-section" novalidate>
                 <div class="j-row">';
                echo '<input type="hidden" name="action" value="stf_pff_paystack_submit_action">';
                echo '<input type="hidden" name="pf-id" value="' . $id . '" />';
                echo '<input type="hidden" name="pf-user_id" value="' . $user_id . '" />';
                echo '<input type="hidden" name="pf-recur" value="' . $recur . '" />';
                echo '<input type="hidden" name="pf-currency" id="pf-currency" value="' . $currency . '" />';


                echo '<input type="hidden" name="Project Title" value="" />';



                $feeSettings = Kkd_Pff_Paystack_Public::fetchFeeSettings();
                echo '<script>window.KKD_PAYSTACK_CHARGE_SETTINGS={
                    percentage:' . $feeSettings['prc'] . ',
                    additional_charge:' . $feeSettings['adc'] . ',
                    threshold:' . $feeSettings['ths'] . ',
                    cap:' . $feeSettings['cap'] . '
                }</script>';

                echo '<div class="span12">
                 <label class="">Select a Project <span>*</span></label>
                 <div class="input xs-input-group">
                    <select name="Project ID" id="xs-donate-charity" class="form-control">
                        <option value="">General Donation</option>';
                        foreach( $current_projects->posts as $project_id ){
                            echo '<option value="'. $project_id     .'">'. get_the_title($project_id) .'</option>';
                        }

                echo '</select>

                </div>
                </div>';
                echo '<div class="span12 unit">
                 <label class="">Full Name <span>*</span></label>
                 <div class="input">
                     <input type="text" name="pf-fname" placeholder="First & Last Name" value="' . $fullname . '"
                     ';

                echo ' required>
                 </div>
                 </div>';
                echo '<div class="span12 unit">
                 <label class="">Email <span>*</span></label>
                 <div class="input">
                     <input type="email" name="pf-pemail" placeholder="Enter Email Address"  id="pf-email" value="' . $email . '"
                     ';
                if ($loggedin == 'yes') {
                    echo 'readonly ';
                }
                echo ' required>
                 </div>
                 </div>';
                echo '<div class="span12 unit">
                 <label class="">Amount (' . $currency;
                if ($minimum == 0 && $amount != 0 && $usequantity == 'yes') {
                    echo ' ' . number_format($amount);
                }


                echo ') <span>*</span></label>
                 <div class="input">';
                if ($usevariableamount == 0) {
                    if ($minimum == 1) {
                        echo '<small> Minimum payable amount <b style="font-size:87% !important;">' . $currency . '  ' . number_format($amount) . '</b></small>';
                        //make it available for javascript so we can test against the input value
                        echo '<input type="hidden" name="pf-minimum-hidden" value="' . number_format($amount) . '" id="pf-minimum-hidden">';
                    }
                    if ($recur == 'plan') {
                        if ($showbtn) {
                            echo '<input type="text" name="pf-amount" value="' . $planamount . '" id="pf-amount" readonly required/>';
                        } else {
                            echo '<div class="span12 unit">
                                    <label class="" style="font-size:18px;font-weight:600;line-height: 20px;">' . $planerrorcode . '</label>
                                </div>';
                        }
                    } elseif ($recur == 'optional') {
                        echo '<input type="text" name="pf-amount" class="pf-number" id="pf-amount" value="' . $amount . '" required/>';
                    } else {
                        if ($amount == 0) {
                            echo '<input type="text" name="pf-amount" class="pf-number" value="0" id="pf-amount" required/>';
                        } elseif ($amount != 0 && $minimum == 1) {
                            echo '<input type="text" name="pf-amount" value="' . $amount . '" id="pf-amount" required/>';
                        } else {
                            echo '<input type="text" name="pf-amount" value="' . $amount . '" id="pf-amount" readonly required/>';
                        }
                    }
                } else {
                    if ($usevariableamount == "") {
                        echo "Form Error, set variable amount string";
                    } else {
                        if (count($paymentoptions) > 0) {
                            echo '<div class="select">
                                    <input type="hidden"  id="pf-vname" name="pf-vname" />
                                    <input type="hidden"  id="pf-amount" />
                                    <select class="form-control" id="pf-vamount" name="pf-amount">';
                            $max = $quantity + 1;
                            if ($max > ($stock + 1)) {
                                $max = $stock + 1;
                            }
                            foreach ($paymentoptions as $key => $paymentoption) {
                                list($a, $b) = explode(':', $paymentoption);
                                echo '<option value="' . $b . '" data-name="' . $a . '">' . $a . ' - ' . $currency . ' ' . number_format($b) . '</option>';
                            }
                            echo '</select> <i></i> </div>';
                        }
                    }
                }
                if ($txncharge != 'merchant' && $recur != 'plan' && $usequantity !== "yes") {
                    echo '<small>Transaction Charge: <b class="pf-txncharge"></b>, Total: <b  class="pf-txntotal"></b></small>';
                }

                echo '<span id="pf-min-val-warn" style="color: red; font-size: 13px;"></span>
                </div>
             </div>';
                if ($recur == 'no' && $usequantity == 'yes') {  //&& ($usevariableamount == 1 || $amount != 0)) { //Commented out because the frontend stops transactions of 0 amount to go through
                    // if ($minimum == 0 && $recur == 'no' && $usequantity == 'yes' && $amount != 0) {
                    echo
                        '<div class="span12 unit">
                        <label class="">' . $quantityunit . '</label>
                        <div class="select">
                            <input type="hidden" value="' . $amount . '" id="pf-qamount"/>
                            <select class="form-control" id="pf-quantity" name="pf-quantity" >';
                    $max = $quantity + 1;

                    if ($max > ($stock + 1) && $useinventory == 'yes') {
                        $max = $stock + 1;
                    }
                    for ($i = 1; $i < $max; $i++) {
                        echo  ' <option value="' . $i . '">' . $i . '</option>';
                    }
                    echo  '</select>
                            <i></i>
                        </div>
                    </div>
                    <div class="span12 unit">
                        <label class="">Total (' . $currency;
                    echo ') <span>*</span></label>
                        <div class="input">
                            <input type="text" id="pf-total" name="pf-total" placeholder="" value="" disabled>';
                    if ($txncharge != 'merchant' && $recur != 'plan') {
                        echo '<small>Transaction Charge: <b class="pf-txncharge"></b>, Total: <b  class="pf-txntotal"></b></small>';
                    }
                    echo '</div>
                    </div>';
                }

                if ($recur == 'optional') {
                    echo '<div class="span12 unit">
                             <label class="">Recurring Payment</label>
                             <div class="select">
                                 <select class="form-control" name="pf-interval" >
                                     <option value="no">None</option>
                                     <option value="daily">Daily</option>
                                     <option value="weekly">Weekly</option>
                                     <option value="monthly">Monthly</option>
                                     <option value="biannually">Biannually</option>
                                     <option value="annually">Annually</option>
                                 </select>
                                 <i></i>
                             </div>
                         </div>';
                } elseif ($recur == 'plan') {
                    if ($showbtn) {
                        echo '<input type="hidden" name="pf-plancode" value="' . $recurplan . '" />';
                        echo '<div class="span12 unit">
                                    <label class="" style="font-size:18px;font-weight:600;line-height: 20px;">' . $plan->data->name . ' ' . $plan->data->interval . ' recuring payment - ' . $plan->data->currency . ' ' . number_format($planamount) . '</label>
                                </div>';
                    } else {
                        echo '<div class="span12 unit">
                                 <label class="" style="font-size:18px;font-weight:600;line-height: 20px;">' . $planerrorcode . '</label>
                             </div>';
                    }
                }

                echo (do_shortcode($obj->post_content));

                if ($useagreement == 'yes') {
                    echo '<div class="span12 unit">
                        <label class="checkbox ">
                            <input type="checkbox" name="agreement" id="pf-agreement" required value="yes">
                            <i id="pf-agreementicon" ></i>
                            Accept terms <a target="_blank" href="' . $agreementlink . '">Link </a>
                        </label>
                    </div><br>';
                }
                echo '<div class="span12 unit donate-form-footer">
                            <small class="compulsory-notice"><span style="color: red;">*</span> are compulsory</small><br />
                            <img src="' . get_template_directory_uri() .'/assets/images/paystack_logos@2x.png' . '" alt="cardlogos"  class="paystack-cardlogos size-full wp-image-1096" />';

                if ($showbtn) {
                    echo '<button type="reset" class="btn secondary-btn donate-form-btn reset-btn">Reset</button>
                            <button type="submit" class="btn primary-btn donate-form-btn donate-btn">' . $paybtn . '</button>';
                }
                echo '</div>';

                echo '</div>
            </form>';

        echo '</div>
        </div>
        </div>
        </section>';

        return ob_get_clean();
    }
    add_shortcode('pff-paystack', 'stf_pff_paystack_form_shortcode');
























    add_action('wp_ajax_stf_pff_paystack_submit_action', 'stf_pff_paystack_submit_action');
    add_action('wp_ajax_nopriv_stf_pff_paystack_submit_action', 'stf_pff_paystack_submit_action');
    function stf_pff_paystack_submit_action()
    {
        if (trim($_POST['pf-pemail']) == '') {
            $response['result'] = 'failed';
            $response['message'] = 'Email is required';

            // Exit here, for not processing further because of the error
            exit(json_encode($response));
        }

        // Hookable location. Allows other plugins use a fresh submission before it is saved to the database.
        // Such a plugin only needs do
        // add_action( 'kkd_pff_paystack_before_save', 'function_to_use_posted_values' );
        // somewhere in their code;
        do_action('kkd_pff_paystack_before_save');

        global $wpdb;
        $code = kkd_pff_paystack_generate_code();

        $table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
        $metadata = $_POST;
        $fullname = $_POST['pf-fname'];
        $recur = $_POST['pf-recur'];
        unset($metadata['action']);
        unset($metadata['pf-recur']);
        unset($metadata['pf-id']);
        unset($metadata['pf-pemail']);
        unset($metadata['pf-amount']);
        unset($metadata['pf-user_id']);
        unset($metadata['pf-interval']);

        // echo '<pre>';
        // print_r($_POST);

        $untouchedmetadata = kkd_pff_paystack_meta_as_custom_fields($metadata);
        $fixedmetadata = [];
        // print_r($fixedmetadata );
        $filelimit = get_post_meta($_POST["pf-id"], '_filelimit', true);
        $currency = get_post_meta($_POST["pf-id"], '_currency', true);
        $formamount = get_post_meta($_POST["pf-id"], '_amount', true); /// From form
        $recur = get_post_meta($_POST["pf-id"], '_recur', true);
        $subaccount = get_post_meta($_POST["pf-id"], '_subaccount', true);
        $txnbearer = get_post_meta($_POST["pf-id"], '_txnbearer', true);
        $transaction_charge = get_post_meta($_POST["pf-id"], '_merchantamount', true);
        $transaction_charge = $transaction_charge * 100;

        $txncharge = get_post_meta($_POST["pf-id"], '_txncharge', true);
        $minimum = get_post_meta($_POST["pf-id"], '_minimum', true);
        $variableamount = get_post_meta($_POST["pf-id"], '_variableamount', true);
        $usevariableamount = get_post_meta($_POST["pf-id"], '_usevariableamount', true);
        $amount = (int) str_replace(' ', '', $_POST["pf-amount"]);
        $variablename = $_POST["pf-vname"];
        $originalamount = $amount;
        $quantity = 1;
        $usequantity = get_post_meta($_POST["pf-id"], '_usequantity', true);

        if (($recur == 'no') && ($formamount != 0)) {
            $amount = (int) str_replace(' ', '', $formamount);
        }
        if ($minimum == 1 && $formamount != 0) {
            if ($originalamount < $formamount) {
                $amount = $formamount;
            } else {
                $amount = $originalamount;
            }
        }
        if ($usevariableamount == 1) {
            $paymentoptions = explode(',', $variableamount);
            if (count($paymentoptions) > 0) {
                foreach ($paymentoptions as $key => $paymentoption) {
                    list($a, $b) = explode(':', $paymentoption);
                    if ($variablename == $a) {
                        $amount = $b;
                    }
                }
            }
        }
        $fixedmetadata[] =  array(
            'display_name' => 'Unit Price',
            'variable_name' => 'Unit_Price',
            'type' => 'text',
            'value' => $currency . number_format($amount)
        );
        if ($usequantity === 'yes' && !(($recur === 'optional') || ($recur === 'plan'))) {
            $quantity = $_POST["pf-quantity"];
            $unitamount = (int) str_replace(' ', '', $amount);
            $amount = $quantity * $unitamount;
        }
        //--------------------------------------

        //--------------------------------------
        if ($txncharge == 'customer') {
            $amount = kkd_pff_paystack_add_paystack_charge($amount);
        }
        $maxFileSize = $filelimit * 1024 * 1024;

        if (!empty($_FILES)) {
            foreach ($_FILES as $keyname => $value) {
                if ($value['size'] > 0) {
                    if ($value['size'] > $maxFileSize) {
                        $response['result'] = 'failed';
                        $response['message'] = 'Max upload size is ' . $filelimit . "MB";
                        exit(json_encode($response));
                    } else {
                        $attachment_id = media_handle_upload($keyname, $_POST["pf-id"]);
                        $url = wp_get_attachment_url($attachment_id);
                        $fixedmetadata[] =  array(
                            'display_name' => ucwords(str_replace("_", " ", $keyname)),
                            'variable_name' => $keyname,
                            'type' => 'link',
                            'value' => $url
                        );
                    }
                } else {
                    $fixedmetadata[] =  array(
                        'display_name' => ucwords(str_replace("_", " ", $keyname)),
                        'variable_name' => $keyname,
                        'type' => 'text',
                        'value' => 'No file Uploaded'
                    );
                }
            }
        }
        $plancode = 'none';
        if ($recur != 'no') {
            if ($recur == 'optional') {
                $interval = $_POST['pf-interval'];
                if ($interval != 'no') {
                    unset($metadata['pf-interval']);
                    $mode =  esc_attr(get_option('mode'));
                    if ($mode == 'test') {
                        $key = esc_attr(get_option('tsk'));
                    } else {
                        $key = esc_attr(get_option('lsk'));
                    }
                    $koboamount = $amount * 100;
                    //Create Plan
                    $paystack_url = 'https://api.paystack.co/plan';
                    $check_url = 'https://api.paystack.co/plan?amount=' . $koboamount . '&interval=' . $interval;
                    $headers = array(
                        'Content-Type'    => 'application/json',
                        'Authorization' => 'Bearer ' . $key
                    );

                    $checkargs = array(
                        'headers'    => $headers,
                        'timeout'    => 60
                    );
                    // Check if plan exist
                    $checkrequest = wp_remote_get($check_url, $checkargs);
                    if (!is_wp_error($checkrequest)) {
                        $response = json_decode(wp_remote_retrieve_body($checkrequest));
                        if ($response->meta->total >= 1) {
                            $plan = $response->data[0];
                            $plancode = $plan->plan_code;
                            $fixedmetadata[] =  array(
                                'display_name' => 'Plan Interval',
                                'variable_name' => 'Plan Interval',
                                'type' => 'text',
                                'value' => $plan->interval
                            );
                        } else {
                            //Create Plan
                            $body = array(
                                'name'     => $currency . number_format($originalamount) . ' [' . $currency . number_format($amount) . '] - ' . $interval,
                                'amount'   => $koboamount,
                                'interval' => $interval
                            );
                            $args = array(
                                'body'     => json_encode($body),
                                'headers'  => $headers,
                                'timeout'  => 60
                            );

                            $request = wp_remote_post($paystack_url, $args);
                            if (!is_wp_error($request)) {
                                $paystack_response = json_decode(wp_remote_retrieve_body($request));
                                $plancode    = $paystack_response->data->plan_code;
                                $fixedmetadata[] =  array(
                                    'display_name' => 'Plan Interval',
                                    'variable_name' => 'Plan Interval',
                                    'type' => 'text',
                                    'value' => $paystack_response->data->interval
                                );
                            }
                        }
                    }
                }
            } else {
                //Use Plan Code
                $plancode = $_POST['pf-plancode'];
                unset($metadata['pf-plancode']);
            }
        }

        if ($plancode != 'none') {
            $fixedmetadata[] =  array(
                'display_name' => 'Plan',
                'variable_name' => 'Plan',
                'type' => 'text',
                'value' => $plancode
            );
        }

        $fixedmetadata = json_decode(json_encode($fixedmetadata, JSON_NUMERIC_CHECK), true);
        $fixedmetadata = array_merge($untouchedmetadata, $fixedmetadata);

        $insert =  array(
            'post_id' => strip_tags($_POST["pf-id"], ""),
            'email' => strip_tags($_POST["pf-pemail"], ""),
            'user_id' => strip_tags($_POST["pf-user_id"], ""),
            'cause_id' => strip_tags($_POST["pf-cause_id"], ""),
            'amount' => strip_tags($amount, ""),
            'plan' => strip_tags($plancode, ""),
            'ip' => kkd_pff_paystack_get_the_user_ip(),
            'txn_code' => $code,
            'metadata' => json_encode($fixedmetadata)
        );
        $exist = $wpdb->get_results(
            "SELECT * FROM $table WHERE (post_id = '" . $insert['post_id'] . "'
                AND email = '" . $insert['email'] . "'
                AND user_id = '" . $insert['user_id'] . "'
                AND amount = '" . $insert['amount'] . "'
                AND plan = '" . $insert['plan'] . "'
                AND ip = '" . $insert['ip'] . "'
                AND paid = '0'
                AND metadata = '" . $insert['metadata'] . "')"
        );
        if (count($exist) > 0) {
            // $insert['txn_code'] = $code;
            // $insert['plan'] = $exist[0]->plan;
            $wpdb->update($table, array('txn_code' => $code, 'plan' => $insert['plan']), array('id' => $exist[0]->id));
        } else {
            $wpdb->insert(
                $table,
                $insert
            );
            if("yes" == get_post_meta($insert['post_id'],'_sendinvoice',true)){
            kkd_pff_paystack_send_invoice($currency, $insert['amount'], $fullname, $insert['email'], $code);
             }
        }
        if ($subaccount == "" || !isset($subaccount)) {
            $subaccount = null;
            $txnbearer = null;
            $transaction_charge = null;
        }
        if ($transaction_charge == "" || $transaction_charge == 0 || $transaction_charge == null) {
            $transaction_charge = null;
        }

        $amount = floatval($insert['amount']) * 100;
        $response = array(
            'result' => 'success',
            'code' => $insert['txn_code'],
            'plan' => $insert['plan'],
            'quantity' => $quantity,
            'email' => $insert['email'],
            'name' => $fullname,
            'total' => round($amount),
            'currency' => $currency,
            'custom_fields' => $fixedmetadata,
            'subaccount' => $subaccount,
            'txnbearer' => $txnbearer,
            'transaction_charge' => $transaction_charge
        );

        //-------------------------------------------------------------------------------------------

        // $pstk_logger = new paystack_plugin_tracker('pff-paystack', Kkd_Pff_Paystack_Public::fetchPublicKey());
        // $pstk_logger->log_transaction_attempt($code);

        echo json_encode($response);
        die();
    }
}
