import gulp from 'gulp';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import sourcemaps from 'gulp-sourcemaps';
import pump from 'pump';
import livereload from 'gulp-livereload';
import filter from 'gulp-filter';
import cssnano from 'cssnano';

/**
 * Gulp task to run the cssnano task.
 *
 * @method
 * @author Dominic Magnifico, 10up
 * @example gulp cssnano
 * @param   {Function} cb the pipe sequence that gulp should run.
 * @returns {void}
*/
gulp.task( 'cssnano', ( cb ) => {
	const fileDest = './dist/css',
		plugins = [
			cssnano()
		],
		fileSrc = [
			'./dist/*.css'
		];

	pump( [
		gulp.src( fileSrc ),
		postcss(plugins),
		rename( function( path ) {
			path.extname = '.min.css';
		} ),
		sourcemaps.init( {
			loadMaps: true
		} ),
		sourcemaps.write( './' ),
		gulp.dest( fileDest ),
		filter( '**/*.css' ),
		livereload()
	], cb );
} );
