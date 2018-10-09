import gulp from 'gulp';
import del from 'del';

gulp.task( 'csscomplete', () => {
	del( ['./dist/*.css*'] );
} );