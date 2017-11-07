<!-- Public-facing event calendar widget content -->

<h2>Upcoming Events at the Guggenheim</h2>
<?php
foreach ( $events->instances as $current_event ) {
    $event_date = sanitize_text_field( $current_event->start_date ) . ' ' . sanitize_text_field($current_event->start_time );
    $event_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $event_date )->format('l F j, Y \a\t g:ia');
    echo '<div>
            <h3>' . sanitize_text_field( $current_event->titles->en ) . '</h3>
            <span>' . $event_date . '</span>
            <img src="' . esc_url ($current_event->media[0]->assets->thumbnail->_links->_self->href ) . '">
            <p>' . $this->word_count( sanitize_text_field($current_event->descriptions->en), 30 ) . '</p>
            <a href="' . esc_url( $current_event->_links->web->href ) . '">More Information</a> >
            </div>';
}
?>
