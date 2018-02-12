import gulp from 'gulp';
import cssnano from 'gulp-cssnano';
import rename from 'gulp-rename';
import sourcemaps from 'gulp-sourcemaps';
import pump from 'pump';
import livereload from 'gulp-livereload';
import filter from 'gulp-filter';

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
		fileSrc = [
			'./dist/*.css'
		],
		taskOpts = {
			autoprefixer: false,
			calc: {
				precision: 8
			},
			zindex: false,
			convertValues: true
		};

	pump( [
		gulp.src( fileSrc ),
		cssnano( taskOpts ),
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
