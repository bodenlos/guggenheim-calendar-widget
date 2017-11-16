<?php
echo '<div>
        <h3>' . sanitize_text_field( $current_event->titles->en ) . '</h3>
        <span>' . $event_date . '</span>
        <img src="' . esc_url ($current_event->media[0]->assets->thumbnail->_links->_self->href ) . '">
        <p>' . $this->word_count( sanitize_text_field($current_event->descriptions->en), 30 ) . '</p>
        <a href="' . esc_url( $current_event->_links->web->href ) . '">More Information</a> >
      </div>';
?>
