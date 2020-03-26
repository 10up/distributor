import gulp from 'gulp';
import del from 'del';

gulp.task( 'cssclean', () => {
	return del( ['./dist/**/*.css*'] );
} );
