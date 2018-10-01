import gulp from 'gulp';
import del from 'del';

gulp.task( 'copy', () => {
	gulp.src(
		[
			'composer.json',
			'distributor.php',
			'assets/img/*',
			'dist/**/*',
			'includes/**/*',
			'lang/**/*',
			'vendor/yanhis-elsts/**/*',
		],
		{ base: '.' } )
		.pipe( gulp.dest( 'release' ) );
} );
