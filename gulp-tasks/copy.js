import gulp from 'gulp';
import del from 'del';
import replace from 'gulp-replace';
import filter from 'gulp-filter';

gulp.task( 'copy', () => {
	const f = filter( 'distributor.php', { restore: true } );

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
		.pipe( f )
		.pipe( replace( /-dev' \);/, '\' );' ) )
		.pipe( f.restore )
		.pipe( gulp.dest( 'release' ) );
} );
