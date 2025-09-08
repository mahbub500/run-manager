<?php
use Codexpert\CoSchool\Certificate\Data;

global $post;

$certificate_data	= new Data( $post->ID );
$certificate_course	= $certificate_data->get( 'course_id' );
?>

<p>
	<label for="certificate-course"><?php _e( 'Course: ', 'coschool' ); ?></label>
	<?php
	if( $certificate_course != '' ) {
		printf( '<a href="%s">%s</a>', get_edit_post_link( $certificate_course ), get_the_title( $certificate_course ) );
	}
	else {
		_e( '[Not assigned]', 'coschool' );
	}
	?>
</p>