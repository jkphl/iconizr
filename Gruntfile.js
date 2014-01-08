module.exports = function(grunt) {
	grunt.initConfig({
		pkg : grunt.file.readJSON('package.json'),
		uglify : {
			dist : {
				src : 'src/iconizr.js',
				dest : 'src/iconizr.min.js'
			}
		},
		watch : {
			javascript : {
				files : ['src/iconizr.js'],
				tasks : ['uglify'],
				options : {
					spawn : false
				}
			}
		}
	});
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.registerTask('default', ['uglify']);
}