<?php

/*
 * Plugin Name: WP Zombaio | CSV Operations
 * Plugin URI: http://barrycarlyon.co.uk/wordpress/wordpress-plugins/wp-zomabio/
 * Description: Bleeple
 * Author: Barry Carlyon
 * Version: 1.0.6
 * Author URI: http://barrycarlyon.co.uk/wordpress/
 */

/*
* $Id: wp_zombaio.php 615736 2012-10-22 19:14:33Z BarryCarlyon $
* $Revision: 615736 $
* $Date: 2012-10-22 20:14:33 +0100 (Mon, 22 Oct 2012) $
*/

if (is_admin()) {
    if (class_exists('wp_zombaio')) {

class wp_zombaio_csv extends wp_zombaio {
    function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    function admin_menu() {
        add_menu_page('WP Zombaio CSV', 'WP Zombaio CSV', 'activate_plugins', 'wp_zombaio_csv', array($this, 'admin_page_csv'), plugin_dir_url(__FILE__) . 'img/zombaio_icon.png');
    }

    function admin_page_csv() {
        $this->admin_page_top('WP Zombaio CSV Operations', false);

        $import_data = $export_data = false;

        if ($_POST) {
            // do stuff
            $import_data = $this->_processImportCsv($_POST['import_a_file'], $_POST['import_a_delimiter']);
        }

        if ($import_data) {
            $import_a_user = isset($_POST['import_a_user']) ? $_POST['import_a_user'] : false;
            if ($import_a_user) {
                $this->_import_data_go($import_data, $import_a_user);
            }
            $this->_import_data_setup($import_data);
        }

//        echo '
?>
<form action="" method="post" enctype="multipart/form-data">
    <fieldset>
        <p>Open the CSV file in a text editor like notepad or similar, copy and paste into below</p>
        <table>
            <tr><th valign="top"><label for="import_a_file">Import a File</label></th>
            <td><textarea name="import_a_file" id="import_a_file" style="width: 400px;" rows="5"></textarea></td></tr>

            <tr><th valign="top"><label for="import_a_delimiter">Delimiter</label></th>
            <td><select name="import_a_delimiter" id="import_a_delimiter">
                <option>;</option>
                <option>,</option>
                <option>tab</option>
            </select></td></tr>

        </table>
    </fieldset>
<?php

        echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Submit', 'wp-zombaio') . '" /></p>';
        echo '</form>';

        $this->admin_page_bottom();
    }

    private function _processImportCsv($file, $delimiter = ';') {
        $data = array();

        $lines = explode("\n", $file);

        $headers = str_getcsv(array_shift($lines), $delimiter);

        foreach ($lines as $line) {
            $line = str_getcsv($line, $delimiter);
            // format into header struct
            $formatted = array();
            foreach ($headers as $index => $header) {
                $formatted[$header] = isset($line[$index]) ? trim($line[$index]) : '';
            }
            $formatted = array_filter($formatted);
            if (count($formatted)) {
                $data[] = $formatted;
            }
        }

        return $data;
    }

    private function _import_data_setup($data) {
        echo '<form action="" method="post">';

        echo '
            <input name="import_a_file" type="hidden" value="' . $_POST['import_a_file'] . '" />
            <input name="import_a_delimiter" type="hidden" value="' . $_POST['import_a_delimiter'] . '" />
        ';

        echo '<table>';

        echo '<tr>
            <th>SubscriberID</th>
            <th>FirstName</th>
            <th>LastName</th>
            <th>Username</th>
            <th>Email</th>
            <th></th>
            <th>Import</th>
        </tr>';

        foreach ($data as $line) {
            echo '<tr>';
            echo '<td>' . $line['SubscriberID'] . '</td>';
            echo '<td>' . $line['FirstName'] . '</td>';
            echo '<td>' . $line['LastName'] . '</td>';
            echo '<td>' . $line['Username'] . '</td>';
            echo '<td>' . $line['Email'] . '</td>';

            $user = get_user_by('email', $line['Email']);
            if ($user) {
                echo '<td>' . $user->ID . '</td>';
            } else {
                echo '<td>Not Present</td>';
            }
            echo '<td style="text-align: center;"><input type="checkbox" name="import_a_user[]" value="' . $line['SubscriberID'] . '" /></td>';

            echo '</tr>';
        }

        echo '</table>';
        echo '<p class="submit"><input type="submit" class="button-primary" value="Import Users" /></p>';
        echo '</form>';

        $this->admin_page_spacer();
    }

    private function _import_data_go($data, $users) {
        $import = array();
        foreach ($data as $line) {
            if (in_array($line['SubscriberID'], $users)) {
                $import[] = $line;
            }
        }

/**
lifted from core
*/

        echo '<table>';
        foreach ($import as $line) {
            $logmsg = '';

                $username = $line['Username'];
                $email = $line['Email'];
                $fname = $line['FirstName'];
                $lname = $line['LastName'];
                $password = $line['Password_plain'];
                $subscription_id = $line['SubscriberID'];

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
                    $logmsg = 'ERROR';
                }

            echo '<tr><td>' . $email . '</td><td>' . $logmsg . '</td></tr>';
        }
        echo '</table>';

        $this->admin_page_spacer();
        return;
    }
}
new wp_zombaio_csv();

}}
