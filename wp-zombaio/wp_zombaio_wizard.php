<?php

$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 0;
$nextstep = $step;
switch ($step) {
    case '3':
        $this->options->wizard = true;
        $this->saveoptions();
        echo '<div id="message" class="updated"><p>' . __('All Done, you are ready to go', 'wp-zombaio') . '</p></div>';
        echo __('<p>You can now review the current options and change advanced options</p>', 'wp-zombaio');
        $do = false;
        break;
    case '2':
        $gw_pass = isset($_REQUEST['gw_pass']) ? $_REQUEST['gw_pass'] : false;
        if (!$gw_pass) {
            echo '<div id="message" class="error"><p>' . __('You Need to enter your Zombaio GW Pass', 'wp-zombaio') . '</p></div>';
        } else {
            $this->options->gw_pass = $gw_pass;
            $this->saveoptions();

            echo __('<h3>Communications - Postback</h3>'
                . '<p>Now the final step</p>'
                . '<p>Update the <strong>Postback URL (ZScript)</strong> to the following:</p>', 'wp-zombaio');
            echo $this->_postbackurl();
            echo sprintf(__('<p>Then Press Validate</p>'
                . '<p>Zombaio will then Validate the Settings, and if everything is correct, should say Successful and save the URL</p>'
                . '<p>If not, please <a href="%s">Click Here</a> and we will restart the Wizard to confirm your settings</p>'
                . '<p>If everything worked, just hit Submit below</p>', 'wp-zombaio'), admin_url('admin.php?page=wp_zombaio&do=wizard'));

            $nextstep = 3;
            break;
        }
    case '1':
        if ($step == 1) {
            $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false;
            if (!$site_id) {
                echo '<div id="message" class="error"><p>' . __('You Need to enter your Site ID', 'wp-zombaio') . '</p></div>';
            } else {
                $this->options->site_id = $site_id;
                $this->saveoptions();

                echo __('<h3>Communications - Gateway Password</h3>'
                    . '<p>Next we need to setup the Zombaio -&gt; Communications</p>'
                    . '<p>In Website Management, select Settings</p>'
                    . '<p>Copy and Enter the <strong>Zombaio GW Pass</strong> below</p>', 'wp-zombaio');
                echo '<label for="gw_pass">' . __('Zombaio GW Pass:', 'wp-zombaio') . ' <input type="text" name="gw_pass" id="gw_pass" value="' . $this->options->gw_pass . '" /></label>';
                $nextstep = 2;
                break;
            }
        }
    case '0':
    default:
        echo __('<h3>Introduction</h3>'
            . '<p>This Wizard will Guide you thru the Zombaio Setup</p>'
            . '<h3>Site ID</h3>'
            . '<p>First your will need a Zombaio Account</p>'
            . '<p>And to have added your Website under Website Management</p>'
            . '<p>This will give you a <strong>Site ID</strong>, enter that now:</p>', 'wp-zombaio');
        echo '<label for="site_id">' . __('Site ID:', 'wp-zombaio') . ' <input type="text" name="site_id" id="site_id" value="' . $this->options->site_id . '" /></label>';
        $nextstep = 1;
}
echo '<input type="hidden" name="step" value="' . $nextstep . '" />';
echo '<input type="hidden" name="do" value="' . $do . '" />';