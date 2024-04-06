<?php

/**
 * Plugin Name:       React Chart
 * Description:
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Ron Agapito
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */
class React_Chart_Plugin
{
    private $plugin_dir = '';

    public function __construct()
    {
         $this->plugin_dir = basename(dirname(__FILE__));
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget' ) );
        register_activation_hook( __FILE__, array($this, 'activate' ) );
        register_deactivation_hook( __FILE__, array($this, 'deactivate' ) );
        add_action( 'init', function () {

            load_plugin_textdomain( 'react-chart', false, $this->plugin_dir. '/languages' );

        });
    }

    public function register_routes()
    {
        register_rest_route('react-chart/v1', '/get-chart', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_chart'),
            'permission_callback' => function () {
                return current_user_can('read');
            },
        ));
    }

    public function get_chart( WP_REST_Request $request )
    {
        global $wpdb;

        $days = $request->get_param( 'days' ) ? absint( $request->get_param('days') ) : 7;

        if ( $days == 15 ) {
            $where = "`date` >= DATE(NOW() - INTERVAL 15 DAY)";
        } elseif ( $days == 30 ) {
            $where = "`date` >= DATE(NOW() - INTERVAL 30 DAY)";
        } else {
            $where = "`date` >= DATE(NOW() - INTERVAL 7 DAY)";
        }

        $query = $wpdb->prepare( "SELECT name, sum(pv) as pv, sum(uv) as uv, sum(amt) as amt FROM wp_rc_page WHERE $where GROUP BY `name`" );

        $result = $wpdb->get_results( $query );

        wp_send_json( $result );
    }

    public function enqueue_scripts( $hook )
    {
        if ( 'index.php' != $hook ) {
            // Not dashboard
            return;
        }

        wp_enqueue_style( 'chart-style', plugin_dir_url( __FILE__ ) . 'build/index.css' );
        wp_register_script( 'chart-script', plugin_dir_url( __FILE__ ) . 'build/index.js', array( 'wp-element', 'wp-i18n', 'wp-api-fetch', 'wp-components' ), '1.0.0', true );
        wp_enqueue_script( 'chart-script' );

        wp_localize_script( 'chart-script', 'rc', array(
            'api_url' => 'react-chart/v1/get-chart',
        ) );

        wp_set_script_translations( 'chart-script', 'react-chart', plugin_dir_path( __FILE__ ) . 'languages' );
    }

    public function add_dashboard_widget()
    {
        $title = __( 'Chart', 'react-chart' );
        wp_add_dashboard_widget( 'chart_widget', $title, array( $this, 'render_dashboard_widget' ) );
    }

    public function render_dashboard_widget()
    {
        echo '<div id="react-chart"></div>';
    }

    public function activate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_page';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            pv int(11) NOT NULL,
            uv int(11) NOT NULL,
            amt int(11) NOT NULL,
            date date NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        $this->insertData();
    }

    public function deactivate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_page';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query( $sql );
    }

    private function insertData()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rc_page';

        $now = date( 'Y-m-d' );
        $seven = date( 'Y-m-d', strtotime( $now . ' -5 days' ) );
        $fifteen = date( 'Y-m-d', strtotime( $now . ' -10 days' ) );
        $thirty = date( 'Y-m-d', strtotime( $now . ' -25 days' ) );

        $data = array(
            array( 'id' => 1, 'name' => 'A', 'uv' => 100, 'pv' => 100, 'amt' => 100, 'date' => $seven ),
            array( 'id' => 2, 'name' => 'B', 'uv' => 200, 'pv' => 200, 'amt' => 300, 'date' => $seven ),
            array( 'id' => 3, 'name' => 'C', 'uv' => 300, 'pv' => 300, 'amt' => 400, 'date' => $seven ),
            array( 'id' => 4, 'name' => 'D', 'uv' => 400, 'pv' => 400, 'amt' => 500, 'date' => $seven ),
            array( 'id' => 5, 'name' => 'A', 'uv' => 100, 'pv' => 100, 'amt' => 100, 'date' => $fifteen ),
            array( 'id' => 6, 'name' => 'A', 'uv' => 100, 'pv' => 100, 'amt' => 100, 'date' => $fifteen ),
            array( 'id' => 7, 'name' => 'A', 'uv' => 0, 'pv' => 100, 'amt' => 100, 'date' => $fifteen ),
            array( 'id' => 8, 'name' => 'A', 'uv' => 0, 'pv' => 100, 'amt' => 100, 'date' => $fifteen ),
            array( 'id' => 9, 'name' => 'B', 'uv' => 100, 'pv' => 100, 'amt' => 100, 'date' => $thirty ),
            array( 'id' => 10, 'name' => 'C', 'uv' => 100, 'pv' => 100, 'amt' => 100, 'date' => $thirty ),
            array( 'id' => 11, 'name' => 'D', 'uv' => 200, 'pv' => 200, 'amt' => 200, 'date' => $thirty ),
            array( 'id' => 12, 'name' => 'A', 'uv' => 1000, 'pv' => 1000, 'amt' => 1000, 'date' => $thirty ),
        );

        foreach ( $data as $row ) {
            $wpdb->insert( $table_name, $row );
        }
    }
}

new React_Chart_Plugin();
