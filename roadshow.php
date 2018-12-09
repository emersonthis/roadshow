<?php

namespace EmersonThis\RoadShow;

/**
 * @version 0.1
 */
/*
Plugin Name: Road Show
Description: List places you want to go / work and allow users to "vote" on them by joining your mailing list
Author: Emerson This
Version: 0.1
Author URI: https://emersonthis.com
*/

class RoadShow {

    /**
     * Static property to hold our singleton instance
     *
     */
    static $instance = false;
    private $post_type = 'roadshow_place';
    private $meta_nonce_name = 'roadshow_place_meta_box_nonce';
    private $email_input_name_attr = 'roadshow_place_email';
    public static $email_field_name = '_roadshow_place_email';
    /**
     * This is our constructor
     *
     * @return void
     */
    private function __construct() {

        // back end
        add_action      ( 'init',                               [$this, 'create_post_types']);
        // add_action      ( 'plugins_loaded',                     array( $this, 'textdomain'              )           );
        // add_action      ( 'admin_enqueue_scripts',              array( $this, 'admin_scripts'           )           );
        add_action      ( 'do_meta_boxes',                      array( $this, 'create_metaboxes'        ),  10, 2   );
        add_action      ( "save_post_{$this->post_type}",       array( $this, 'save_custom_meta'        ),  1       );
        // front end
        add_action      ( 'wp_enqueue_scripts',                 array( $this, 'front_scripts'           ),  10      );
        // add_filter      ( 'comment_form_defaults',              array( $this, 'custom_notes_filter'     )           );

        add_shortcode( 'PLACES_TO_GO', [$this, 'placesShortcode']);

    }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return WP_Comment_Notes
     */
    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }

    function placesShortcode($args) {

        $query =  new \WP_Query(
        [
          'post_type'=>[$this->post_type],
          'order' => 'ASC',
          'orderby'   => 'title',
          'posts_per_page' => -1,
        ]
        );

        $return = '<ul class="roadshow-places">';

        if ( $query->have_posts() ) :

            /* Start the Loop */
            while ( $query->have_posts() ) :
                $query->the_post();

                global $post;
                $title = $post->post_title;
                $email = get_post_meta($post->ID, self::$email_field_name, true);
                $email = ($email) ? $email : get_bloginfo('admin_email');
                $subject = __('COME TO: ', 'roadshow') . $title;
                $body = __("Please notify me when you come to {$title}.", 'roadshow');

                $return .= "<li class='roadshow-place'><a href='mailto:{$email}?subject={$subject}&body={$body}'>" . $post->post_title . '</a></li>';

            endwhile;

        else :

            $return .= "<emph>No places to show.</emph>";

        endif;

        $return .= '</ul>';

        return $return;

    }


    function create_post_types() {
        register_post_type( $this->post_type,
            array(
                'labels' => array(
                    'name' => __( 'Places to Go', 'roadshow' ),
                    'singular_name' => __( 'Place', 'roadshow' )
                ),
                'public' => true,
                'has_archive' => true,
                'supports'            => [ 'title' ],
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => true,
                'show_in_admin_bar'   => true,
                'menu_position'       => 50,
                // 'can_export'          => true,
                'exclude_from_search' => true,
                'publicly_queryable'  => false,
                'capability_type'     => 'page',
            )
        );
    }

    function create_metaboxes() {
        add_meta_box(   'roadshow_place_email_meta_box',
                        __( 'Settings', 'roadshow' ),
                        [$this, 'roadshow_place_settings_build_meta_box'],
                        $this->post_type,
                        'normal',
                        'low'
                    );
    }

    function save_custom_meta($postId) {

        // verify meta box nonce
        if ( !isset( $_POST[$this->meta_nonce_name] ) || !wp_verify_nonce( $_POST[$this->meta_nonce_name], basename( __FILE__ ) ) ){
            return;
        }

        // // return if autosave
        // if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
        //     return;
        // }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $postId ) ){
            return;
        }

        if ( isset( $_REQUEST[$this->email_input_name_attr] ) ) {

            $updated = update_post_meta(
                $postId,
                self::$email_field_name,
                sanitize_text_field( $_POST[$this->email_input_name_attr] )
            );
        }

    }

    function front_scripts() {
        // check for killswitch first
        $killswitch = apply_filters( 'roadshow_css_killswitch', false );
        if ( $killswitch )
            return false;
        wp_enqueue_style( 'roadshow', plugins_url( 'css/roadshow.css', __FILE__ ));
    }

    function roadshow_place_settings_build_meta_box($post) {
        wp_nonce_field( basename( __FILE__ ), $this->meta_nonce_name );

        $current_email = get_post_meta( $post->ID, self::$email_field_name, true );

        ?>
        <div class='inside'>
            <h3><?php _e( 'Email Responses To', 'roadshow' ); ?></h3>
            <p>
                <input type="email" name="<?= $this->email_input_name_attr ?>" value="<?php echo $current_email; ?>" />
            </p>
        </div>
        <?php
    }
}

// Instantiate our class
$RoadShow = RoadShow::getInstance();

