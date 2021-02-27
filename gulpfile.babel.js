import gulp from 'gulp';
import requireDir from 'require-dir';
import runSequence from 'gulp4-run-sequence';
import livereload from 'gulp-livereload';

requireDir( './gulp-tasks' );

/**
 * Gulp task to run all JS processes in a sequential order.
*/
gulp.task( 'js', ( callback ) => {
	return runSequence(
		'jsclean',
		'webpack',
		callback()
	);
} );

/**
 * Gulp task to run all Sass/CSS processes in a sequential order.
*/
gulp.task( 'css', ( callback ) => {
	return runSequence(
		'cssclean',
		'cssnext',
		'cssnano',
		'csscomplete',
		callback()
	);
} );

/**
 * Gulp task to watch for file changes and run the associated processes.
 */
gulp.task( 'watch', () => {
	livereload.listen( { basePath: 'dist' } );
	gulp.watch( './assets/css/*.css', gulp.series( 'css' ) );
	gulp.watch( './assets/js/*.js', gulp.series( 'js' ) );
} );

/**
 * Gulp task to run the default release processes in a sequential order.
 */
gulp.task( 'release', ( callback ) => {
	return runSequence(
		'css',
		'js',
		'copy',
		callback()
	);
} );

/**
 * Gulp task to run the default build processes in a sequential order.
 */
gulp.task( 'default', ( callback ) => {
	return runSequence(
		'css',
		'js',
		callback()
	);
} );
