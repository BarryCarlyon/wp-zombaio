<?php

/*
* $Id: wp_zombaio.php 615736 2012-10-22 19:14:33Z BarryCarlyon $
* $Revision: 615736 $
* $Date: 2012-10-22 20:14:33 +0100 (Mon, 22 Oct 2012) $
*/

class wp_zombaio_processor extends wp_zombaio {
    function __construct() {
        $this->setup();
    }

    protected function process() {
        $this->init();

$fp = fopen('/tmp/zom', 'w');
fwrite($fp, print_r($_GET, true));
fwrite($fp, print_r($_POST, true));
fwrite($fp, print_r($_SERVER, true));
fclose($fp);

        $gw_pass = isset($_GET['ZombaioGWPass']) ? $_GET['ZombaioGWPass'] : false;
        if (!$gw_pass) {
            return;
        }
        if ($gw_pass != $this->options->gw_pass) {
            header('HTTP/1.0 401 Unauthorized');
//            echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. GW Pass</h3>';
            echo '<h3>Authentication failed. GW Pass</h3><h1>Zombaio Gateway 1.1</h1>';
            exit;
        }

        if (!$this->verify_ipn_ip()) {
            header('HTTP/1.0 403 Forbidden');
//            echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed, you are not Zombaio.</h3>';
            echo '<h3>Authentication failed, you are not Zombaio.</h3><h1>Zombaio Gateway 1.1</h1>';
            exit;
        }

        $user_id = false;
        $username = isset($_GET['username']) ? $_GET['username'] : false;

        // verify site ID
        $site_id = isset($_GET['SITE_ID']) ? $_GET['SITE_ID'] : (isset($_GET['SiteID']) ? $_GET['SiteID'] : false);
        if (!$site_id || $site_id != $this->options->site_id) {
            if (substr($username, 0, 4) == 'Test') {
                // test mode
                header('HTTP/1.1 200 OK');
                echo 'OK';
                exit;
            }
            // patch for simulator
            if (!$site_id && count($_GET) == 3 && !empty($username)) {
                // we got Action/pass/username
                $_GET['partial_data'] = 1;
            } else {
                header('HTTP/1.0 401 Unauthorized');
    //            echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. Site ID MisMatch</h3>';
                echo '<h3>Authentication failed. Site ID MisMatch</h3><h1>Zombaio Gateway 1.1</h1>';
                exit;
            }
        }

        $action = isset($_GET['Action']) ? $_GET['Action'] : false;
        if (!$action) {
            header('HTTP/1.0 401 Unauthorized');
            echo '<h3>Authentication failed. No Action</h3><h1>Zombaio Gateway 1.1</h1>';
            exit;
        }

        $logid = $this->log();
        $logmsg = '';

        $action = strtolower($action);
        switch ($action) {
            case 'user.add': {
                $subscription_id = isset($_GET['SUBSCRIPTION_ID']) ? $_GET['SUBSCRIPTION_ID'] : false;
                if (!$subscription_id) {
                    header('HTTP/1.0 401 Unauthorized');
//                    echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. No Sub</h3>';
                    echo '<h3>Authentication failed. No Sub</h3><h1>Zombaio Gateway 1.1</h1>';
                    exit;
                }

                $email = $_GET['EMAIL'];
                $fname = $_GET['FIRSTNAME'];
                $lname = $_GET['LASTNAME'];
                $password = $_GET['password'];

                $user_id = username_exists($username);
                if (!$user_id) {
                    $email_test = is_email($email);
                    if ($email_test == $email) {
                        $user_id = email_exists($email);
                        if (!$user_id) {
                            $user_id = wp_create_user( $username, $password, $email );
                            if (!is_wp_error($user_id)) {
                                $logmsg = 'User Created OK';
                            } else {
                                // error
                                $logmsg = 'User Create: Fail ' . $user_id->get_error_message();
                            }
                        } else {
                            // email exists
                            $logmsg = 'User Create: Email Exists, Activating User';
                        }
                    } else {
                        // invalid/empty email
                        $logmsg = 'User Create: Failed ' . $email_test;
                    }
                } else {
                    // username exists
                    $logmsg = 'User Create: UserName Exists, Activating User';
                }

                if ($user_id) {
                    update_user_meta($user_id, 'wp_zombaio_delete', false);
                    update_user_meta($user_id, 'wp_zombaio_subscription_id', $subscription_id);
                    update_user_meta($user_id, 'first_name', $fname);
                    update_user_meta($user_id, 'last_name', $lname);
                } else {
                    // epic fail
                    echo 'ERROR';
                    exit;
                }
                break;
            }
            case 'user.delete': {
                $user = get_user_by('login', $username);
                if (!$user) {
                    echo 'USER_DOES_NOT_EXIST';
                    exit;
                }
                // delete of suspend?
                if ($this->options->delete == true) {
                    include(plugin_dir_path(__FILE__) . '/../../../wp-admin/includes/user.php');
                    wp_delete_user($user->ID);
                    // could test for deleted and return ERROR if needed
                    $logmsg = 'User was deleted';
                } else {
                    update_user_meta($user->ID, 'wp_zombaio_delete', true);
                    $logmsg ='User was suspended';
                }
                break;
            }
            case 'rebill': {
                $subscription_id = isset($_GET['SUBSCRIPTION_ID']) ? $_GET['SUBSCRIPTION_ID'] : false;
                if (!$subscription_id) {
                    header('HTTP/1.0 401 Unauthorized');
//                    echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. Rebill No SUB ID</h3>';
                    echo '<h3>Authentication failed. Rebill No SUB ID</h3><h1>Zombaio Gateway 1.1</h1>';
                    exit;
                }

                //get user ID by subscription ID
                global $wpdb;
                $query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'wp_zombaio_subscription_id\' AND meta_value = \'' . $subscription_id . '\'';

                $user_id = $wpdb->get_var($query);
                if (!$user_id) {
                    echo 'USER_DOES_NOT_EXIST';
                    exit;
                } else {
                    $success = ym_GET('Success', 0);
                    // 0 FAIL 2 FAIL retry in 5 days
                    if ($success == 1) {
                        // all good
                        update_user_meta($user_id, 'wp_zombaio_delete', false);
                    } else {
                        if ($success) {
                            $logmsg = 'Rebill Charge Failed: Retry in 5 Days';
                        } else {
                            $logmsg = 'Rebill Charge Failed: REASON CODE';
                        }
                    }
                }
                $logmsg = 'User rebilled cleared';
                break;
            }
            case 'chargeback': {
                $logmsg = 'A Chargeback Occured';
                break;
            }
            case 'declined': {
                $subscription_id = isset($_GET['SUBSCRIPTION_ID']) ? $_GET['SUBSCRIPTION_ID'] : false;
                if ($subscription_id) {
                    //get user ID by subscription ID
                    global $wpdb;
                    $query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'wp_zombaio_subscription_id\' AND meta_value = \'' . $subscription_id . '\'';

                    $user_id = $wpdb->get_var($query);
                    // should fire a user.delete after true fail
                    $logmsg = 'User Card Rebill was Declined';
                } else {
                    $user_id = '';
                    $logmsg = 'User Card was Declined';
                }
                break;
            }
            case 'user.addcredits': {
                // dont match on email Identifier is more useful
                // sicne a differnet persons details could be used to buy credits for the user in the Identifier
                $id = isset($_GET['Identifier']) ? $_GET['Identifier'] : false;
                $credits_purchased = isset($_GET['Credits']) ? $_GET['Credits'] : false;
                if ($id && $credits_purchased) {
                    $user = get_user_by('id', $id);
                    if ($user) {
                        // validate hash
                        $myhash = md5($id . $this->options->gw_pass . $credits_purchased . $this->options->site_id);
                        $theirhash = isset($_GET['Hash']) ? $_GET['Hash'] : false;
                        if ($myhash == $theirhash) {
                            $user_id = $id;

                            // get current add add away
                            $credits = get_user_meta($user_id, 'wp_zombaio_credits', true);
                            if (!$credits) {
                                $credits = 0;
                            }
                            $credits += $credits_purchased;

                            // update
                            update_user_meta($user_id, 'wp_zombaio_credits', $credits);
                            break;
                        } else {
                            header('HTTP/1.0 401 Unauthorized');
                            echo 'ERROR';
                            exit;
                        }
                    } else {
                        header('HTTP/1.0 401 Unauthorized');
                        echo 'ERROR';
                        exit;
                    }
                } else {
                    header('HTTP/1.0 401 Unauthorized');
                    echo 'ERROR';
                    exit;
                }
            }
            default: {
                header('HTTP/1.0 401 Unauthorized');
//                echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed. No Idea: ' . $action . '</h3>';
                echo '<h3>Authentication failed. No Idea: ' . $action . '</h3><h1>Zombaio Gateway 1.1</h1>';
                exit;
            }
        }

        // log result
        $this->logresult($logid, $logmsg, $user_id);
        $this->notifyadmin($logid, $logmsg);

        echo 'OK';

        // emit hook
        do_action('wp_zombaio_process', $action, $_GET, $user_id, $username, $logid);

        exit;
    }

    /**
    Payment Processor utility
    */
    private function log() {
        $username = isset($_GET['username']) ? ' - ' . $_GET['username'] : '';
        $post = array(
            'post_title'        => 'Zombaio ' . $_GET['Action'] . $username,
            'post_type'         => 'wp_zombaio',
            'post_status'       => (isset($_GET['Action']) ? str_replace('.', '_', strtolower($_GET['Action'])) : 'unknown'),
            'post_content'      => print_r($_GET, true),
        );
        $r = @wp_insert_post($post);

        update_post_meta($r, 'wp_zombaio_json_packet', json_encode($_GET));

        return $r;
    }
    private function logresult($logid, $logmsg, $user_id) {
        update_post_meta($logid, 'wp_zombaio_logmessage' , $logmsg);
        update_post_meta($logid, 'wp_zombaio_user_id', $user_id);
        if (isset($_GET['Amount'])) {
            update_post_meta($logid, 'wp_zombaio_amount', $_GET['Amount']);
        }
        if (isset($_GET['Credits'])) {
            update_post_meta($logid, 'wp_zombaio_credits', $_GET['Credits']);
        }
        if (isset($_GET['credits'])) {
            update_post_meta($logid, 'wp_zombaio_credits', $_GET['credits']);
        }
        return;
    }

    private function notifyadmin($logid, $logmsg) {
        if (!$this->options->notify_enable) {
            return;
        }
        // notify admin
        $subject = 'WP Zombaio: Payment Result';
        $message = 'A Payment has been processed' . "\n"
            . 'The Result was: ' . $logmsg . "\n"
            . 'Full Log: ' . print_r($_GET, true) . "\n"
            . 'Love WP Zombaio';
        $target = !empty($this->options->notify_target) ? $this->options->notify_target : get_option('admin_email');
        @wp_mail($target, $subject, $message);
        return;
    }

    private function verify_ipn_ip() {
        if ($this->options->bypass_ipn_ip_verification) {
            return true;
        }
        $ip = $_SERVER['REMOTE_ADDR'];

        $ips = $this->load_ipn_ips();
        if ($ips) {
            if (in_array($ip, $ips)) {
                return true;
            }
        }
        return false;
    }
}
