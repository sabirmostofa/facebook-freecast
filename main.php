<?php
/*
  Plugin Name: WP_facebook_app
  Plugin URI: http://sabirul-mostofa.blogspot.com
  Description: Integrate freecasttv with facebook
  Version: 1.0
  Author: Sabirul Mostofa
  Author URI: http://sabirul-mostofa.blogspot.com
 */

class wp_facebook_app {

    function __construct() {
        add_action('plugins_loaded', array($this, 'trigger'));
        add_action('admin_menu', array($this, 'CreateMenu'), 50);
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }

    function create_tables() {
        global $wpdb;

        $sql_db = "CREATE TABLE `" . $wpdb->prefix . "freecast_facebook_users` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 `firstname` varchar(20) not null default '', 
 `lastname` varchar(20) not null default '',
 `location` varchar(20) not null default '',
 `email` varchar(40) not null default '',
    key `email`(`email`)
) ";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql_db);
    }

    function trigger() {
		 if( isset($_GET['code']) && isset($_GET['state'])){
		 wp_redirect('http://apps.facebook.com/freecasttv');
		 exit;
	 }
        if (stripos($_SERVER['HTTP_REFERER'], 'facebook.com') !== FALSE)
            $this->process();
    }

    function process() {
        require_once 'src/facebook.php';
        $facebook = new Facebook(array(
                    'appId' =>'' ,
                    'secret' =>''
                ));

// Get User ID
        $user = $facebook->getUser();

        if ($user) {
            try {
                // If the user has been authenticated then proceed
                $user_profile = $facebook->api('/me');
                if($user_profile){
					//var_dump($user_profile);
					//exit;
                $this -> save_data($user_profile);
			}
            } catch (FacebookApiException $e) {
                error_log($e);
                $user = null;
            }
        }

        if ($user) {
            $logoutUrl = $facebook->getLogoutUrl();
        } else {
            $loginUrl = $facebook->getLoginUrl(array('scope' => 'email,user_location'));
            ?>
            <html>
				<head>
					<fb:redirect url="http://apps.facebook.com/freecasttv/ />
				</head>
				</head>
				<body>
            <!--Resize Iframe-->
            <script src="http://connect.facebook.net/en_US/all.js"></script>
            <script>
				
                         
                FB.Canvas.setAutoResize(7);
                         
            </script>
          
            <!-- End Resize Iframe-->
           

            <?php
            echo "<script type=\"text/javascript\">\ntop.location.href = \"$loginUrl\";\n</script>";
            ?>
            </body>
            </html>
            <?php
exit;
          
        }
    }
    
    
    function save_data($user){
        global $wpdb;
        $table =$wpdb->prefix.'freecast_facebook_users';
        $email = mysql_escape_string($user['email']);
        
        if($wpdb->get_var("select firstname from $table where email='$email'"))return;
        $wpdb->insert( $table,
                array(
                    'firstname' => mysql_escape_string($user['first_name']),
                    'lastname' => mysql_escape_string($user['last_name']),                   
                    'location'=> mysql_escape_string($user['location']['name']),
                    'email'=> mysql_escape_string($user['email'])
                    ));      
    }
    
    
    
    function CreateMenu(){
                add_submenu_page('options-general.php', 'Facebook Users', 'Facebook Users', 'activate_plugins', 'wpFreecastFb', array($this, 'OptionsPage'));
    }
    
    function OptionsPage(){
        global $wpdb;
        $table =$wpdb->prefix.'freecast_facebook_users';
       $results = $wpdb->get_results("select * from $table");
        
        ?>
            <div class="wrap">
                <table class="widefat">
                    <thead>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Location</th>
                    <th>Email</th>
                </thead>
                <tbody>
                    <?php 
                    if($results)
                        foreach($results as $res):
                    ?>
                    <tr>
                        <td><?php echo $res->id ?></td>
                        <td><?php echo $res->firstname ?></td>
                        <td><?php echo $res->lastname ?></td>
                        <td><?php echo $res->location ?></td>
                        <td><?php echo $res->email ?></td>
                    
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
            <?php
        
    }
    
    

}

$wp_facebook_app = new wp_facebook_app();

