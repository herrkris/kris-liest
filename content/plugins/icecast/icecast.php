<?php
/**
 * @package Icecast
 * @author Kristof Dreier
 * @version 1.0.0
Plugin Name: Icecast
Plugin URI: http://wordpress.org/extend/plugins/icecast/
Description: Displays the status of the Icecast server and a web player
Version: 1.0.0
Author: Kristof Dreier
Author URI: http://kris-liest.de
License: This code is (un)licensed under the kopimi (copyme) non-license; http://www.kopimi.com. In other words you are free to copy it, taunt it, share it, fork it or whatever. :)
 */

class Icecast_Widget extends WP_Widget {

    function __construct() {
        $widget_ops = array( 'classname' => 'widget_icastglobal', 'description' => 'Contains an audio player for streaming.' );
        parent::__construct( 'icecastglobalwidget', 'Icecast Stream Player', $widget_ops );

        wp_register_script( 'icecastscriptplayer', get_bloginfo('wpurl') . '/wp-content/plugins/icecast/mediaplayer.js');
        wp_register_script( 'icecastscript', get_bloginfo('wpurl') . '/wp-content/plugins/icecast/icecast.js');
        wp_register_style( 'icecaststyle', get_bloginfo('wpurl') . '/wp-content/plugins/icecast/icecast.css');
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'icecastscriptplayer' );
        wp_enqueue_script( 'icecastscript' );
        wp_enqueue_style( 'icecaststyle' );
    }

    function widget( $args, $instance ) {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);

        echo $before_widget;

        if ($title) {
            echo $before_title . $title . $after_title;
        }

        $xml = simplexml_load_file("http://live.kris-liest.de:8000/xml.xsl");
        if ($xml->SHOUTCASTSERVER) {echo '<p>Sollte der Player nicht funktionieren, nehmt bitte die <a href="http://kris-liest.de:63389/stream.m3u">direkte URL zum Stream</a>.</p>';
            echo '<audio id="stream-player" src="http://live.kris-liest.de:8000/stream" type="audio/mp3" controls="controls">';
        } else {
            echo '<p>Leider läuft gerade keine Live-Sendung. Sonntag um 22 Uhr ist es wieder soweit!</p>';
        }

        echo $after_widget;
    }

    public function insert_script() {
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);

        return $instance;
    }

    function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
        $title = strip_tags($instance['title']);
        ?><p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
<?php
    }
}

function register_icecase_widget() {
    register_widget( 'Icecast_Widget' );
}

add_action( 'widgets_init', 'register_icecase_widget' );
