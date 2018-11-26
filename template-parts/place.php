<?php

global $post;
$title = $post->post_title;
$email = get_post_meta($post->ID, EmersonThis\Roadshow\Roadshow::$email_field_name, true);
$email = ($email) ? $email : get_bloginfo('admin_email');
$subject = __('COME TO: ', 'roadshow') . $title;
$body = __("Please notify me when you come to {$title}.", 'roadshow');

echo "<a class='place' href='mailto:{$email}?subject={$subject}&body={$body}'>" . $post->post_title . '</a>';
