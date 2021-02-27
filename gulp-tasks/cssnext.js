import gulp from 'gulp';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import pump from 'pump';

/**
 * Gulp task to run the sass task.
 *
 * @method
 * @author Dominic Magnifico, 10up
 * @example gulp sass
 * @param   {Function} cb the pipe sequence that gulp should run.
 * @returns {void}
*/
gulp.task( 'cssnext', ( cb ) => {
	const fileSrc = [
		'./assets/css/admin-external-connection.css',
		'./assets/css/admin-external-connections.css',
		'./assets/css/admin-distributed-post.css',
		'./assets/css/admin-pull-table.css',
		'./assets/css/admin-edit-table.css',
		'./assets/css/admin-syndicated-post.css',
		'./assets/css/admin-settings.css',
		'./assets/css/admin-site-health.css',
		'./assets/css/gutenberg-syndicated-post.css',
		'./assets/css/push.css',
		'./assets/css/admin.css'
	];
	const fileDest = './dist';
	const cssNextOpts = {
		features: {
			autoprefixer: {
				browsers: ['last 2 versions']
			}
		}
	};
	const taskOpts = [
		require( 'postcss-import' ),
		require( 'postcss-cssnext' )( cssNextOpts )
	];

	pump( [
		gulp.src( fileSrc ),
		postcss( taskOpts ),
		gulp.dest( fileDest )
	], cb );

} );
