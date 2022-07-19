import gulp from 'gulp';
import del from 'del';

gulp.task( 'copy', ( done ) => {
	del.sync( ['./release/**/*'] );

	gulp.src(
		[
			'readme.txt',
			'README.md',
			'CHANGELOG.md',
			'composer.json',
			'distributor.php',
			'.github/workflows/*',
			'.gitattributes',
			'assets/img/*',
			'dist/**/*',
			'includes/**/*',
			'lang/**/*',
			'templates/**/*',
			'vendor/georgestephanis/application-passwords/**/*',
			'vendor/yahnis-elsts/plugin-update-checker/**/*',
		],
		{ base: '.' } )
		.pipe( gulp.dest( 'release' ) );

	done();
} );
