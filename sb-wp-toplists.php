<?php
/*
Plugin Name: SB TopLists
Plugin URI:  www.subashbasnet.com.np
Description: This plugin is used for generating top lists of any topic.
Version:     1.0
Author:      Subash Basnet
Author URI:  www.subashbasnet.com.np
License:     GPL2
*/

global $sb_toplists_db_version;
$sb_toplists_db_version = '1.0';

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


/*
 *
 */
function sb_toplists_install(){
    global $wpdb;
    global $sb_toplists_db_version;

    $table_name = $wpdb->prefix . 'sb_toplists';
    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      title VARCHAR(255) NOT NULL,
      lists text NULL,
      PRIMARY KEY  (id)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('sb_toplists_db_version', $sb_toplists_db_version);
}
register_activation_hook(__FILE__, 'sb_toplists_install');


/*
 *
 */
function sb_toplist_setup_menu(){
    add_menu_page( 'TopLists Plugin Page', 'TopLists', 'manage_options', 'sb-toplists-plugin', 'sb_toplists_init','dashicons-chart-bar',6);
}
add_action('admin_menu', 'sb_toplist_setup_menu');


class Sb_TopList_Table extends WP_List_Table{

    function __construct(){
        global $status, $page;
        parent::__construct(array(
            'singular' => 'Top Lists',
            'plural' => 'Top List',
        ));
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'title' => __('Title', 'sb_toplists'),
        );
        $sortable_columns = array(
            'title' => 'Title',
        );
        $hidden = array();
        $this->_column_headers = array($columns, $hidden, $sortable_columns);
    }

    function extra_tablenav( $which ){
        if ($which == "top") {
            echo '<h2>Top Lists <a href="admin.php?page=sb-toplists-plugin&type=new">Add New</a></h2>';
        }
    }

    function get_columns(){
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'title' => __('Title', 'sb_toplists'),
        );
        return $columns;
    }

    function column_default($item, $column_name){
        return $item[$column_name];
    }

    function prepare_items(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'sb_toplists';
        $per_page = 15;//

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'title';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}


/*
 *
 */
function sb_toplists_init(){
    if(isset($_GET['type']) && $_GET['type']=='new'){
        include(__DIR__.'/views/edit.php');
    }else{
        global $wpdb;
        $table = new Sb_TopList_Table();
        $table->prepare_items();

        $message = '';
        if ('delete' === $table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'sb_toplists'), count($_REQUEST['id'])) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <?php echo $message; ?>
            <form id="toplists-table" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }

}


