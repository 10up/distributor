import gulp from 'gulp';
import del from 'del';
import replace from 'gulp-replace';
import filter from 'gulp-filter';

gulp.task( 'copy', (done) => {
	const f = filter( 'distributor.php', { restore: true } );

	del.sync( ['./release/**/*'] );

	gulp.src(
		[
			'readme.txt',
			'README.md',
			'CHANGELOG.md',
			'composer.json',
			'distributor.php',
			'assets/img/*',
			'dist/**/*',
			'includes/**/*',
			'lang/**/*',
			'templates/**/*',
			'vendor/georgestephanis/application-passwords/**/*',
			'vendor/yahnis-elsts/plugin-update-checker/**/*',
		],
		{ base: '.' } )
		.pipe( f )
		.pipe( replace( /-dev' \);/, '\' );' ) )
		.pipe( f.restore )
		.pipe( gulp.dest( 'release' ) );

	done();
} );
