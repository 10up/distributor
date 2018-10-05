import gulp from 'gulp';
import del from 'del';

gulp.task( 'copy', () => {
	del( ['./release/**/*'] );
	gulp.src(
		[
			'composer.json',
			'distributor.php',
			'assets/img/*',
			'dist/**/*',
			'includes/**/*',
			'lang/**/*',
			'vendor/yahnis-elsts/plugin-update-checker/**/*',
		],
		{ base: '.' } )
		.pipe( gulp.dest( 'release' ) );
} );
