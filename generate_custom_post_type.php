<?php
/*
A php script to generate a custom_post_type plugin.
Based on generate_dak_events_post_type by Snorre Davøen and Lisa Halvorsen.
Author: Snorre Davøen, Lisa Halvorsen Robin G. Aaberg
Version: 0.0003
*/


// Enter metadata id and title for metabox in array:
$post_type_name = "dak_smugmug_album";

$plugin_description = "/*
Plugin Name: DAK Smugmug Album Post Type
Description: Add penguins to your smugmug album posts!
Author: Snorre Davøen, Lisa Halvorsen, Robin Garen Aaberg.
Version: 0.0002
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
    global $post_type_name;
    return $post_type_name . $delimiter . $item;
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
fwrite($fh, sprintf("require_once(%s);", $myXmlrpcFile));

fwrite($fh, "\$post_type_name = {$post_type_name};");



$add_action_method = <<<'EOD'
// Set up hooks
add_action('init', '%s');
//add_action('add_meta_boxes', 'dak_add_meta_boxes');

function %s() {
    global $post_type_name;

    register_post_type(
        $post_type_name,
         array(
            'labels' => array(
                'name' => __( 'Events' ),
                'singular_name' => __( 'Event' ),
                'add_new' => __( 'Add New Event' ),
                'add_new_item' => __( 'Add New Event' ),
                'edit_item' => __( 'Edit Event' ),
                'new_item' => __( 'Add New Event' ),
                'view_item' => __( 'View Event' ),
                'search_items' => __( 'Search Event' ),
                'not_found' => __( 'No events found' ),
                'not_found_in_trash' => __( 'No events found in trash' )
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
        \$post_type_name
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
    \$meta = get_post_meta(\$post->ID, {$metabox_id}, true);
    echo '<input type=\"text\" name=\"{$metabox_id}\" value=\"'.\$meta.'\" />';
   
}\n\n";     

} 

fwrite($fh, $dak_write_metaboxes_method);


fwrite($fh, "?>");
fclose($fh);


/****************************************************************************** 
 *
 *      XML-RPC methods generator
 */
echo    "Generating XML-RPC methods";

$xmlrpcfh = fopen($myXmlrpcFile, 'w') or die ('Cannot open file');
fwrite($xmlrpcfh,"<?php\n");
fwrite($xmlrpcfh, $plugin_description);


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
    $xmlrpc_array .= sprintf('"\n%s" => "%s",\n', prepend($methodname, '.'), prepend($methodname));
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