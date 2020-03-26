import gulp from 'gulp';
import del from 'del';

gulp.task( 'csscomplete', () => {
	return del( ['./dist/*.css*'] );
} );
