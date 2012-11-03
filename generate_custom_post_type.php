<?php
/*
A php script to generate a custom_post_type plugin.
Based on generate_dak_events_post_type by Snorre Davøen and Lisa Halvorsen.
Author: Snorre Davøen, Lisa Halvorsen Robin G. Aaberg
Version: 0.0003
*/


// Enter metadata id and title for metabox in array:
$post_type_namespace = "dak_event";
$post_type_name = "Event";


$plugin_description = "/*
Plugin Name: Event Post Type
Description: Add penguins to your smugmug album posts!
Author: Snorre Davøen, Lisa Halvorsen, Robin Garen Aaberg.
Version: 0.0003
*/\n\n";

$metaboxes = array(
        "id" => "id",
    	"linkout" => "linkout",
    	"start_date" => "startDate",
    	"start_time" => "startTime",
    	"end_date" => "endDate",
    	"end_time" => "endTime",
    	"is_accepted" => "is_accepted",
    	"is_public" => "is_visible",
    	"custom_location" => "customLocation",
    	"location_id" => "location_id",
    	"arranger_id" => "arranger_id",
    	"festival_id" => "festival_id",
    	"primary_picture" => "primaryPicture",
    	"covercharge" => "covercharge",
    	"age_limit" => "age_limit",
    	"created_at" => "created_at",
    	"updated_at" => "updated_at"

    );

$xmlrpc_methods = array(
        "update_event",
        "delete_event"
    ); 



// Function to prepend every penguin with ze post_type_name
function prepend($item, $delimiter="_") {
    global $post_type_namespace;
    return $post_type_namespace . $delimiter . $item;
}

//  Prepends the metaboxes-ids with the post_type_name
$tmp_metaboxes = array();
foreach ($metaboxes as $key => $value) {
    $tmp_metaboxes[prepend($key)] = $value;
}
$metaboxes = $tmp_metaboxes;




// Enter id of post type

$myFile = prepend("post_type.php");
$myXmlrpcFile = prepend("xmlrpc.php");

$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh,"<?php\n");
fwrite($fh, $plugin_description);
fwrite($fh, sprintf("require_once(\"%s\");\n\n", $myXmlrpcFile));

fwrite($fh, "\$post_type_namespace = \"{$post_type_namespace}\";");



$add_action_method = <<<'EOD'
// Set up hooks
add_action('init', '%s');
//add_action('add_meta_boxes', 'dak_add_meta_boxes');

function %s() {
    global $post_type_namespace;

    register_post_type(
        $post_type_namespace,
         array(
            'labels' => array(
                'name' => __( '%s' ),
                'singular_name' => __( '%s' ),
                'add_new' => __( 'Add New %s' ),
                'add_new_item' => __( 'Add New %s' ),
                'edit_item' => __( 'Edit %s' ),
                'new_item' => __( 'Add New %s' ),
                'view_item' => __( 'View %s' ),
                'search_items' => __( 'Search %s' ),
                'not_found' => __( 'No %s found' ),
                'not_found_in_trash' => __( 'No %s found in trash' )
            ),
            'public' => true,
            'supports' => array( 'title', "content", 'thumbnail' ),
            'capability_type' => 'post',
            'register_meta_box_cb' => '%s'
        )
    );

    // Adding xml-rpc methods
    add_filter( 'xmlrpc_methods', %s);
}


EOD;

// Dynamic function names
$add_action_method = sprintf($add_action_method, 
    prepend("create_post_type"),
    prepend("create_post_type"),
    $post_type_name.s,     // name
    $post_type_name,       // name_singular
    $post_type_name,       // add_new
    $post_type_name,       // add_new_item
    $post_type_name,       // edit_item
    $post_type_name,       // new_item
    $post_type_name,       // view_item
    $post_type_name.s,     // search_items
    $post_type_name.s,     // not_found
    $post_type_name.s,     // not_found_in_trash
    prepend("add_metaboxes"),
    prepend("add_xmlrpc_methods")
);

fwrite($fh, $add_action_method);



$dak_add_metaboxes_method = <<<'EOD'
/* Adds a box to the main column on the Post and Page edit screens */
function %s() { 
EOD;


foreach ($metaboxes as $metabox_id => $metabox_title) {
    
    $add_meta_box_function = "
    add_meta_box( 
        \"{$metabox_id}\",
        __(\"{$metabox_title}\"), \"{$metabox_id}\",
        \$post_type_namespace
    );\n";
    $dak_add_metaboxes_method.=$add_meta_box_function;

}

$dak_add_metaboxes_method.="}\n\n";

// Dynamic function names
$dak_add_metaboxes_method = sprintf($dak_add_metaboxes_method, 
    prepend("add_metaboxes")
);

fwrite($fh, $dak_add_metaboxes_method);    



$dak_write_metaboxes_method = "";

foreach ($metaboxes as $metabox_id => $metabox_title) {

$dak_write_metaboxes_method .= "function {$metabox_id}() {
    global \$post;
    \$nonce = wp_create_nonce( plugin_basename(__FILE__) );
    \$meta = get_post_meta(\$post->ID, {$metabox_id}, true);
    echo '<input type=\"hidden\" name=\"meta_noncename\" value=\"'.\$nonce.'\" />';
    echo '<input type=\"text\" name=\"{$metabox_id}\" value=\"'.\$meta.'\" />';
   
}\n\n";     

} 

fwrite($fh, $dak_write_metaboxes_method);

$dak_write_save_metaboxes_method = <<< 'EOD'
// Method hijacked from Devin @ http://wptheming.com/2010/08/custom-metabox-for-post-type/ 
function %s($post_id, $post) {
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['meta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }
    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;
    // OK, we're authenticated: we need to find and save the data
    // We'll put it into an array to make it easier to loop though.
    $%s['_location'] = $_POST['_location'];
    // Add values of $events_meta as custom fields
    foreach ($%s as $key => $value) { // Cycle through the $events_meta array!
        if( $post->post_type == 'revision' ) return; // Don't store custom data twice
        $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
        if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
            update_post_meta($post->ID, $key, $value);
        } else { // If the custom field doesn't have a value
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
    }
}
add_action('save_post', '%s', 1, 2); // save the custom fields


EOD;

// Dynamic function names
$dak_write_save_metaboxes_method = sprintf($dak_write_save_metaboxes_method, 
    prepend("save_post_meta"), 
    prepend("meta"),
    prepend("meta"),
    prepend("save_post_meta")
);

fwrite($fh, $dak_write_save_metaboxes_method);

fwrite($fh, "?>");
fclose($fh);


/****************************************************************************** 
 *
 *      XML-RPC methods generator
 */
echo    "Generating XML-RPC methods";

$xmlrpc_description = <<< "EOD"
/*
 * xmlrpc methods to create custom post types remotely
 *
 * Author: Lisa Halvorsen, Snorre Davøen, Robin G. Aaberg
 * Version: 0.0000003
 */

EOD;

$xmlrpcfh = fopen($myXmlrpcFile, 'w') or die ('Cannot open file');
fwrite($xmlrpcfh,"<?php\n");
fwrite($xmlrpcfh, $xmlrpc_description);


//  Prepends the method names with the post_type_name
$tmp_xmlrpc_methods = array();
foreach ($xmlrpc_methods as $key => $value) {
    $tmp_xmlrpc_methods[prepend($key)] = $value;
}
$xmlrpc_methods = $tmp_xmlrpc_methods;






$xmlrpc_array = <<< 'EOD'
    // Add method names here
    $%s = array( 
EOD;

foreach ($xmlrpc_methods as $methodname) {
    $xmlrpc_array .= sprintf("'%s' => '%s',\n", prepend($methodname, '.'), prepend($methodname));
}

$xmlrpc_array .= <<< 'EOD'
    );


EOD;

$xmlrpc_array = sprintf($xmlrpc_array, 
    prepend('xmlrpc_methods')
);

fwrite($xmlrpcfh, $xmlrpc_array);



$add_xmlrpc_methods = <<< 'EOD'
/*
 *  Adding xml-rpc methods
 */     

function %s($methods) {
    global $%s;

    foreach ($%s as $xmlrpc_method => $php_method) {
        $methods[$xmlrpc_method] = $php_method;
    }

    return $methods;

}


EOD;


// Dynamic function names
$add_xmlrpc_methods = sprintf($add_xmlrpc_methods, 
    prepend("add_xmlrpc_methods"), 
    prepend('xmlrpc_methods'),
    prepend('xmlrpc_methods')
    
);

fwrite($xmlrpcfh, $add_xmlrpc_methods);


$xmlrpc_method_declaration = <<< 'EOD'
    
    function %s($args) {
        global $wp_xmlrpc_server;
        $wp_xmlrpc_server->escape( $args );
        # xmlrp
        
        $blog_id  = $args[0];
        $username = $args[1];
        $password = $args[2];

        if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
            return $wp_xmlrpc_server->error;

        /*
         *  Functionality if authenticated and authorized:
         */
        
        #Send me the Codez here

    }


EOD;

$xmlrpc_method_declarations = ""; 
foreach ($xmlrpc_methods as $method) {
    $xmlrpc_method_declarations .= sprintf($xmlrpc_method_declaration, prepend($method));
}

fwrite($xmlrpcfh, $xmlrpc_method_declarations);

fwrite($xmlrpcfh, "\n?>");


fclose($xmlrpcfh);





?>