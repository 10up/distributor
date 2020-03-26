import gulp from 'gulp';
import del from 'del';

gulp.task( 'jsclean', () => {
	return del( ['./dist/**/*.js*'] );
} );
