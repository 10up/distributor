module.exports = function (grunt) {

	require('load-grunt-tasks')(grunt);

	grunt.initConfig ( {
		uglify : {
			production : {
				options : {
					beautify         : false,
					preserveComments : false,
					mangle           : {
						except : ['jQuery']
					}
				},
				
				files : {
					'assets/js/push.min.js' : [
						'assets/js/src/push.js',
					],
					'assets/js/admin-pull.min.js' : [
						'assets/js/src/admin-pull.js',
					],
					'assets/js/admin-external-connection.min.js' : [
						'assets/js/src/admin-external-connection.js',
					]
				}
			}
		},

		sass : {
			dist : {
				files : {
					'assets/css/admin-external-connection.css' : 'assets/css/sass/admin-external-connection.scss',
					'assets/css/admin-external-connections.css' : 'assets/css/sass/admin-external-connections.scss',
					'assets/css/admin-syndicated-post.css' : 'assets/css/sass/admin-syndicated-post.scss',
					'assets/css/push.css' : 'assets/css/sass/push.scss'
				}
			}
		},

		cssmin : {
			target : {
				files : [{
					expand : true,
					cwd    : 'assets/css',
					src    : ['admin-external-connection.css', 'admin-external-connections.css', 'admin-syndicated-post.css', 'push.css'],
					dest   : 'assets/css',
					ext    : '.min.css'
				}]
			}
		},

		postcss: {
			options: {
				map: true,

				processors: [
					require('autoprefixer')({browsers: 'last 6 versions'})
				]
			},
			dist: {
				src: 'assets/css/*.css'
			}
		},

		makepot: {
			main: {
				options: {
					domainPath: 'lang',
					mainFile: 'syndicate.php',
					potFilename: 'syndicate.pot',
					type: 'wp-plugin',
					potHeaders: true
				}
			}
		},

		watch : {
			options : {
				livereload : true
			},

			scripts : {
				files : [
					'assets/js/src/*'
				],
				tasks : ['uglify:production']
			},

			styles : {
				files : [
					'assets/css/sass/*.scss'
				],
				tasks : ['sass', 'postcss', 'cssmin']
			}

		}
	} );

	grunt.registerTask ('default', ['uglify:production', 'sass', 'postcss', 'cssmin']);

};
