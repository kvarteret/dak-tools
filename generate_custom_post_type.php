<?php
/*
A php script to generate the  generate_dak_smugmug_album_post_type plugin.
Based on generate_dak_events_post_type by Snorre Davøen and Lisa Halvorsen.
Author: Snorre Davøen, Lisa Halvorsen Robin G. Aaberg
Version: 0.0002
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




// Function to prepend every penguin with ze post_type_name
function prepend($item) {
    global $post_type_name;
    return $post_type_name . "_" . $item;
}

//  Prepends the metaboxes-ids with the post_type_name
$tmp_metaboxes = array();
foreach ($metaboxes as $key => $value) {
    $tmp_metaboxes[prepend($key)] = $value;
}
$metaboxes = $tmp_metaboxes;




// Enter id of post type

$myFile = prepend("post_type.php");
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, "<?php");
fwrite($fh, $plugin_description);

fwrite($fh, "\$post_type_name = {$post_type_name};");



$add_action_method = <<<'EOD'
// Set up hooks
add_action('init', '%s');
//add_action('add_meta_boxes', 'dak_add_meta_boxes');

function %s() {
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
}
EOD;

// Dynamic function names
$add_action_method = sprintf($add_action_method, 
    prepend("create_post_type"), 
    prepend("create_post_type"),
    prepend("add_metaboxes")
);

fwrite($fh, $add_action_method);



$dak_add_metaboxes_method = <<<'EOD'
/* Adds a box to the main column on the Post and Page edit screens */
function %s() { 
EOD;


foreach ($metaboxes as $metabox_id => $metabox_title) {
    
    $add_meta_box_function = "add_meta_box( 
            \"{$metabox_id}\",
            __(\"{$metabox_title}\"), \"{$metabox_id}\",
            \$post_type_name
        );\n";
    $dak_add_metaboxes_method.=$add_meta_box_function;

}

$dak_add_metaboxes_method.="}\n\n";

// Dynamic function names
$dak_add_metaboxes_method = sprintf($add_action_method, 
    prepend("add_metaboxes")
);

fwrite($fh, $dak_add_metaboxes_method);    



$dak_write_metaboxes_method = "";

foreach ($metaboxes as $metabox_id => $metabox_title) {

$dak_write_metaboxes_method .= "function {$metabox_id}() {
    global \$post;
    \$meta = get_post_meta(\$post->ID, {$metabox_id}, true);
    echo '<input type=\"text\" name=\"{$metabox_id}\" value=\"\{\$meta}\" />';
   
}\n\n";     

} 

fwrite($fh, $dak_write_metaboxes_method);

fwrite($fh, "?>");

fclose($fh);







?>