<?php

/*
* $Id: wp_zombaio_dashboard.php 615425 2012-10-22 00:15:54Z BarryCarlyon $
* $Revision: 615425 $
* $Date: 2012-10-22 01:15:54 +0100 (Mon, 22 Oct 2012) $
*/

class wp_zombaio_dashboard {
    /**
    Setup
    */
    function __construct() {
        if (current_user_can('activate_plugins')) {
            wp_add_dashboard_widget('wp_zombaio_signup_stats', __('WP Zombaio SignUp Stats', 'wp-zombaio'), array($this, 'signup_stats'), array($this, 'signup_stats_config'));
            wp_add_dashboard_widget('wp_zombaio_credit_stats', __('WP Zombaio Credits Stats', 'wp-zombaio'), array($this, 'credit_stats'), array($this, 'credit_stats_config'));
            wp_add_dashboard_widget('wp_zombaio_earnings_stats', __('WP Zombaio Earnings Stats', 'wp-zombaio'), array($this, 'earnings_stats'), array($this, 'earnings_stats_config'));
        }
    }

    public function signup_stats() {
        $options = get_option('wp_zombaio_signup_stats');
        if (!$options) {
            $options = new StdClass();
            $options->limit = 28;
            $options->period = 'days';
            $options->money = false;
            update_option('wp_zombaio_signup_stats', $options);
        }
        // use strtotime to get start point
        $limit = strtotime('-' . $options->limit . ' ' . $options->period);

        global $wpdb;
        // build some data
        $query = 'SELECT UNIX_TIMESTAMP(user_registered) AS unixtime FROM ' . $wpdb->users . ' WHERE UNIX_TIMESTAMP(user_registered) > ' . $limit . ' ORDER BY user_registered DESC';
        $result = $wpdb->get_results($query);

        $data = array();
        foreach ($result as $row) {
            $date = date('Y-m-d', $row->unixtime);
            $data[$date] = isset($data[$date]) ? $data[$date] + 1 : 1;
        }
        // fill in missing dates
        for ($x=(time());$x>$limit;$x=$x-86400) {
            $date = date('Y-m-d', $x);
            $data[$date] = isset($data[$date]) ? $data[$date] : 0;
        }

        if ($options->money) {
            // get paymetns on that day
            foreach ($data as $date => $members) {
                $date_test = explode('-', $date);
                $posts = get_posts(array(
                    'numberposts'   => -1,
                    'post_type'     => 'wp_zombaio',
                    'post_status'   => array('user_add', 'rebill'),
                    'year'          => $date_test[0],
                    'monthnum'      => $date_test[1],
                    'day'           => $date_test[2],
                ));
                $money = 0;
                foreach ($posts as $post) {
                    $money += get_post_meta($post->ID, 'wp_zombaio_amount', true);
                }
                $data[$date] = array($members, $money);
            }
            $yaxis = array(
                __('Sign Ups', 'wp-zombaio'),
                __('Membership Payments', 'wp-zombaio'),
            );
        } else {
            $yaxis = __('Sign Ups', 'wp-zombaio');
        }

        // key sort so data is in the right order
        ksort($data);

        echo $this->date_graph_data($data, 'wp_zombaio_site_sign_ups', __('Site Sign Ups', 'wp-zombaio'), $yaxis);
        return;
    }

    public function signup_stats_config() {
        $options = get_option('wp_zombaio_signup_stats');
        if (isset($_POST['widget_id']) && $_POST['widget_id'] == 'wp_zombaio_signup_stats') {
            $options->limit = $_POST['limit'];
            $options->period = $_POST['period'];
            $options->money = $_POST['money'];
            update_option('wp_zombaio_signup_stats', $options);
            return;
        }
        $form = '
<p>
    <label>' . __('Show Signups from Last:', 'wp-zombaio') . '
        <select name="limit">';

        for ($x=1;$x<=31;$x++) {
            $form .= '<option value="' . $x . '"';
            $form .= ($x == $options->limit) ? ' selected="selected" ' : '';
            $form .= '>' . $x . '</option>';
        }

        $form .= '
        </select>
        <select name="period">
            <option value="days"' . ($options->period == 'days' ? ' selected="selected" ' : '') . '>' . __('Days', 'wp-zombaio') . '</option>
            <option value="months"' . ($options->period == 'months' ? ' selected="selected" ' : '') . '>' . __('Months', 'wp-zombaio') . '</option>
        </select>
    </label>
</p>
';

        $form .= '
<p>
    <label>' . __('Show Money/Payments', 'wp-zombaio') . '
        <select name="money">
            <option value="0" ' . (!$options->money ? 'selected="selected"' : '') . '>' . __('No', 'wp-zombaio') . '</option>
            <option value="1" ' . ($options->money ? 'selected="selected"' : '') . '>' . __('Yes', 'wp-zombaio') . '</option>
        </select>
        <br />
        ' . __('Will show Rebill Amounts also', 'wp-zombaio') . '
    </label>
</p>
';

        echo $form;
        return;
    }

    /**
    credits
    */
    function credit_stats() {
        $options = get_option('wp_zombaio_credit_stats');
        if (!$options) {
            $options = new StdClass();
            $options->limit = 28;
            $options->period = 'days';
            update_option('wp_zombaio_credit_stats', $options);
        }
        // use strtotime to get start point
        $limit = strtotime('-' . $options->limit . ' ' . $options->period);

        global $wpdb;

        // not time
        $query = 'SELECT SUM(meta_value) AS current_credits FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'wp_zombaio_credits\'';
        $current_credits = $wpdb->get_var($query);

        // current spent
        $query = 'SELECT SUM(meta_value) AS spent_credits
            FROM ' . $wpdb->postmeta . ' pm
            LEFT JOIN ' . $wpdb->posts . ' p
            ON p.id = pm.post_id
            WHERE p.post_type = \'wp_zombaio\'
            AND p.post_status = \'credit_spend\'
            AND pm.meta_key = \'wp_zombaio_credits\'';
        $spent = $wpdb->get_var($query);

        echo '<p style="text-align: center;">' . sprintf(__('Users have Spent a total of %s Credits and there are %s unspent credits in User Accounts', 'wp-zombaio'), $spent, ($current_credits - $spent)) . '</p>';
//      echo $spent . '/' . $current_credits;

        // generate a bar graph


        /**
        Total Sales
        */
        $query = 'SELECT pm.meta_value AS boughtpost_id FROM ' . $wpdb->posts . ' p
            LEFT JOIN ' . $wpdb->postmeta . ' pm
            ON pm.post_id = p.ID
            WHERE post_type = \'wp_zombaio\'
            AND UNIX_TIMESTAMP(post_date_gmt) > ' . $limit . '
            AND post_status = \'credit_spend\'
            AND pm.meta_key = \'wp_zombaio_post_id\'
            ORDER BY UNIX_TIMESTAMP(post_date_gmt) DESC';
        $results = $wpdb->get_results($query);

        $data = array();
        foreach ($results as $bought) {
            $post = get_post($bought->boughtpost_id);
            $title = '(' . $post->ID . ') ' . $post->post_title . ' (' . ucwords(get_post_meta($bought->boughtpost_id, 'wp_zombaio_credit_cost_type', true) . ')');
            $data[$title] = isset($data[$title]) ? $data[$title] + 1 : 1;
        }
        ksort($data);

        echo $this->data_graph_column($data, 'wp_zomabio_best_sellers', __('Total Sales', 'wp-zombaio'), __('Posts', 'wp-zombaio'), __('Total Sales', 'wp-zombaio'));

        /**
        Single and Repeat
        */
        $query = 'SELECT ID AS log_id FROM ' . $wpdb->posts . '
            WHERE post_type = \'wp_zombaio\'
            AND UNIX_TIMESTAMP(post_date_gmt) > ' . $limit . '
            AND post_status = \'credit_spend\'
            ORDER BY UNIX_TIMESTAMP(post_date_gmt) DESC';
        $results = $wpdb->get_results($query);

        $data = array();
        foreach ($results as $log) {
            $post = get_post(get_post_meta($log->log_id, 'wp_zombaio_post_id', true));
            $title = '(' . $post->ID . ') ' . $post->post_title . ' (' . ucwords(get_post_meta($bought->boughtpost_id, 'wp_zombaio_credit_cost_type', true) . ')');
            if (isset($data[$title][get_post_meta($log->log_id, 'wp_zombaio_user_id', true)])) {
                // repeat
                $data[$title][get_post_meta($log->log_id, 'wp_zombaio_user_id', true)][1]++;
            } else {
                // first
                $data[$title][get_post_meta($log->log_id, 'wp_zombaio_user_id', true)] = array(
                    1,
                    0
                );
            }
        }
        foreach ($data as $title => $sales_data) {
            $singles = 0;
            $repeats = 0;
            foreach ($sales_data as $user_id => $sales) {
                $singles += $sales[0];
                $repeats += $sales[1];
                $data[$title] = array($singles, $repeats);
            }
        }
//      echo '<pre>'.print_r($data,true).'</pre>';return;
        ksort($data);

        echo $this->data_graph_column($data, 'wp_zomabio_repeat_sellers', __('Repeat Sellers', 'wp-zombaio'), __('Posts', 'wp-zombaio'), array(__('Inital Sales', 'wp-zombaio'), __('Repeat Sales', 'wp-zombaio')));

        // get credits in play
/*
        // get credit spending
        $query = 'SELECT *, UNIX_TIMESTAMP(post_date_gmt) AS unixtime FROM ' . $wpdb->posts . '
            WHERE post_type = \'wp_zombaio\'
            AND UNIX_TIMESTAMP(post_date_gmt) > ' . $limit . '
            AND post_status = \'credit_spend\'
            ORDER BY UNIX_TIMESTAMP(post_date_gmt) DESC';
        $results = $wpdb->get_results($query);

        $data = array();
        foreach ($results as $result) {
            $date = date('Y-m-d', $row->unixtime);
            $data[$date] = isset($data[$date]) ? $data[$date] = $data[$date] + 1 : 1;
        }

        ksort($data);

        echo '<pre>' . print_r($data,true) . '</pre>';
*/
    }
    public function credit_stats_config() {
        $options = get_option('wp_zombaio_credit_stats');
        if (isset($_POST['widget_id']) && $_POST['widget_id'] == 'wp_zombaio_credit_stats') {
            $options->limit = $_POST['limit'];
            $options->period = $_POST['period'];
            update_option('wp_zombaio_credit_stats', $options);
            return;
        }
        $form = '
<p>
    <label for="last">' . __('Show Credit Stats From Last:', 'wp-zombaio') . '
        <select name="limit">';

        for ($x=1;$x<=31;$x++) {
            $form .= '<option value="' . $x . '"';
            $form .= ($x == $options->limit) ? ' selected="selected" ' : '';
            $form .= '>' . $x . '</option>';
        }

        $form .= '
        </select>
        <select name="period">
            <option value="days"' . ($options->period == 'days' ? ' selected="selected" ' : '') . '>' . __('Days', 'wp-zombaio') . '</option>
            <option value="months"' . ($options->period == 'months' ? ' selected="selected" ' : '') . '>' . __('Months', 'wp-zombaio') . '</option>
        </select>
    </labe>
';

        echo $form;
        return;
    }

    function earnings_stats() {
        $options = get_option('wp_zombaio_earnings_stats');
        if (!$options) {
            $options = new StdClass();
            $options->limit = 28;
            $options->period = 'days';
            update_option('wp_zombaio_earnings_stats', $options);
        }
        // use strtotime to get start point
        $limit = strtotime('-' . $options->limit . ' ' . $options->period);

        global $wpdb;

        // get payments
        $query = 'SELECT *, UNIX_TIMESTAMP(post_date_gmt) AS unixtime FROM ' . $wpdb->posts . '
            WHERE post_type = \'wp_zombaio\'
            AND UNIX_TIMESTAMP(post_date_gmt) > ' . $limit . '
            AND post_status = \'user_add\'
            ORDER BY UNIX_TIMESTAMP(post_date_gmt) DESC';
        $result = $wpdb->get_results($query);

        $data = array();
        foreach ($result as $row) {
            $date = date('Y-m-d', $row->unixtime);
            $data[$date] = isset($data[$date]) ? $data[$date] + get_post_meta($row->ID, 'wp_zombaio_amount', true) : 1;
        }
        // fill in missing dates
        for ($x=(time());$x>$limit;$x=$x-86400) {
            $date = date('Y-m-d', $x);
            $data[$date] = isset($data[$date]) ? $data[$date] : 0;
        }
        ksort($data);

        $period_total = 0;
        // get payments on that day other types
        foreach ($data as $date => $user_add) {
            $period_total += $user_add;

            $date_test = explode('-', $date);
            $posts = get_posts(array(
                'numberposts'   => -1,
                'post_type'     => 'wp_zombaio',
                'post_status'   => 'rebill',
                'year'          => $date_test[0],
                'monthnum'      => $date_test[1],
                'day'           => $date_test[2],
            ));
            $rebills = 0;
            foreach ($posts as $post) {
                $rebills += get_post_meta($post->ID, 'wp_zombaio_amount', true);
            }
            $period_total += $rebills;

            $posts = get_posts(array(
                'numberposts'   => -1,
                'post_type'     => 'wp_zombaio',
                'post_status'   => 'user_addcredits',
                'year'          => $date_test[0],
                'monthnum'      => $date_test[1],
                'day'           => $date_test[2],
            ));
            $add_credits = 0;
            foreach ($posts as $post) {
                $add_credits += get_post_meta($post->ID, 'wp_zombaio_amount', true);
            }
            $period_total += $add_credits;

            $data[$date] = array($user_add, $period_total, $rebills, $add_credits);
        }
//      echo '<pre>'.print_r($data,true).'</pre>';return;
        $yaxis = array(
            __('New User', 'wp-zombaio'),
            __('Period Total', 'wp-zombaio'),
            __('Rebills', 'wp-zombaio'),
            __('Credit Purchase', 'wp-zombaio'),
        );

        echo $this->date_graph_data($data, 'wp_zomabio_earnings', __('Total Earnings', 'wp-zombaio'), $yaxis);
    }
    function earnings_stats_config() {
        $options = get_option('wp_zombaio_earnings_stats');
        if (isset($_POST['widget_id']) && $_POST['widget_id'] == 'wp_zombaio_earnings_stats') {
            $options->limit = $_POST['limit'];
            $options->period = $_POST['period'];
            update_option('wp_zombaio_earnings_stats', $options);
            return;
        }
        $form = '
<p>
    <label for="last">' . __('Show Credit Stats From Last:', 'wp-zombaio') . '
        <select name="limit">';

        for ($x=1;$x<=31;$x++) {
            $form .= '<option value="' . $x . '"';
            $form .= ($x == $options->limit) ? ' selected="selected" ' : '';
            $form .= '>' . $x . '</option>';
        }

        $form .= '
        </select>
        <select name="period">
            <option value="days"' . ($options->period == 'days' ? ' selected="selected" ' : '') . '>' . __('Days', 'wp-zombaio') . '</option>
            <option value="months"' . ($options->period == 'months' ? ' selected="selected" ' : '') . '>' . __('Months', 'wp-zombaio') . '</option>
        </select>
    </labe>
';

        echo $form;
        return;
    }

    /**
    General graphing functions
    */
    /**
    Basic Singular/double line graph
    */
    private function date_graph_data($data, $id, $title, $yaxis, $double = true) {
        $chart = '
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(' . $id . '_draw_chart);
function ' . $id . '_draw_chart() {
';
        // output data
        $chart .= '
    var data = new google.visualization.DataTable();
    data.addColumn(\'date\', \'' . __('Date', 'wp-zombaio') . '\');
';

        $dual = false;

        $test = $data;
        $test = array_pop($test);
        if (is_array($test)) {
            $dual = true;
            for ($x=0;$x<count($test);$x++) {
                $chart .= '
    data.addColumn(\'number\', \'' . $yaxis[$x] . '\');
';
            }
        } else {
            $chart .= '
    data.addColumn(\'number\', \'' . $yaxis . '\');
';
        }
        $chart .= '
    data.addRows([' . "\n";
        foreach ($data as $x => $y) {
            $x = 'new Date(' . str_replace('-', ',', $x) . ')';
            if (is_array($y)) {
                $y = implode(',', $y);
            }
            $rows[] = '[' . $x . ', ' . $y . ']';
        }
        $chart .= implode($rows, ',' . "\n");
        $chart .= "\n" . ']);' . "\n";

        $chart .= '
    var options = {
        title: \'' . $title . '\',
        legend: {
            position: \'none\'
        },
        ';

        if ($dual && $double) {
            $chart .= '
        vAxes: {
            0: {logScale: false},
            1: {logScale: false}
        },
        series: {
            0:{targetAxisIndex:0},
            1:{targetAxisIndex:1}
        },
';
        }

        $chart .= '
        hAxis: {
            minorGridlines: {
                count: 6
            },
            format: \'d MMM\'
        }
    }
    var chart = new google.visualization.LineChart(document.getElementById(\'' . $id . '\'));
    chart.draw(data, options);
}
</script>';

        return $chart . '<div style="width: 100%;" id="' . $id . '"></div>';
    }
    private function data_graph_column($data, $id, $title, $xaxis, $yaxis) {
        $chart = '
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(' . $id . '_draw_chart);
function ' . $id . '_draw_chart() {
';
        // output data
        $chart .= '
    var data = new google.visualization.DataTable();
    data.addColumn(\'string\', \'' . $xaxis . '\');
';

        $dual = false;

        $test = $data;
        $test = array_pop($test);
        if (is_array($test)) {
            $dual = true;
            for ($x=0;$x<count($test);$x++) {
                $chart .= '
    data.addColumn(\'number\', \'' . $yaxis[$x] . '\');
';
            }
        } else {
            $chart .= '
    data.addColumn(\'number\', \'' . $yaxis . '\');
';
        }
        $chart .= '
    data.addRows([' . "\n";
        foreach ($data as $x => $y) {
            if (is_array($y)) {
                $y = implode(',', $y);
            }
            $rows[] = '[\'' . $x . '\', ' . $y . ']';
        }
        $chart .= implode($rows, ',' . "\n");
        $chart .= "\n" . ']);' . "\n";

        $chart .= '
    var options = {
        title: \'' . $title . '\',
        legend: {
            position: \'none\'
        }
    }
        ';

        $chart .= '
        var chart = new google.visualization.ColumnChart(document.getElementById(\'' . $id . '\'));
    chart.draw(data, options);
}
</script>';

        return $chart . '<div style="width: 100%;" id="' . $id . '"></div>';
    }
}
