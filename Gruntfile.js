module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				compress: {
					global_defs: {
						"EO_SCRIPT_DEBUG": false
					},
					dead_code: true
				},
				banner: '/*! <%= pkg.name %> <%= pkg.version %> */\n'
			},
			build: {
				files: [{
					expand: true, // Enable dynamic expansion.
					src: [
					], // Actual pattern(s) to match.
					ext: '.min.js', // Dest filepaths will have this extension.
				}, ]
			}
		},

		cssmin: {
			admin: {
				files: [{
					expand: true,
					cwd: 'assets/css/admin',
					src: [ '*.css', '!*-min.css' ],
					dest: 'assets/css/admin',
					ext: '.min.css'
				}]
			},
		},


		// Generate git readme from readme.txt
		wp_readme_to_markdown: {
			convert: {
				files: {
					'readme.md': 'readme.txt'
				},
			},
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: [
				'assets/css/admin/*.scss',
				'assets/css/frontend/*.scss'
				],
				tasks: ['sass', 'rtlcss', 'postcss', 'cssmin']
			},
			js: {
				files: [
				'assets/js/admin/*.js',
				'assets/js/frontend/*.js',
				'!assets/js/admin/*.min.js',
				'!assets/js/frontend/*.min.js'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// # Build and release 

		// Remove any files in zip destination and build folder
		clean: {
			main: ['build/**']
		},

		// Copy the plugin into the build directory
		copy: {
			main: {
				src: [
					'**',
					'!node_modules/**',
					'!build/**',
					'!deploy/**',
					'!svn/**',
					'!**/*.zip',
					'!**/*.bak',
					'!wp-assets/**',
					'!package-lock.json',
					'!nyp-logo.png',
					'!screenshots/**',
					'!.git/**',
					'!**.md',
					'!Gruntfile.js',
					'!package.json',
					'!gitcreds.json',
					'!.gitcreds',
					'!.gitignore',
					'!.gitmodules',
					'!sftp-config.json',
					'!**.sublime-workspace',
					'!**.sublime-project',
					'!deploy.sh',
					'!**/*~',
					'!phpcs.xml',
					'!composer.json',
					'!composer.lock',
					'!vendor/**'
				],
				dest: 'build/'
			}
		},

		// Make a zipfile.
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: 'deploy/<%= pkg.version %>/<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: 'build/',
				src: ['**/*'],
				dest: '/<%= pkg.name %>'
			}
		},

		'github-release': {
			options: {
				repository: 'woocommerce/<%= pkg.name %>',
				release: {
					tag_name: '<%= pkg.version %>',
					name: '<%= pkg.version %>',
					body: 'Description of the release'
				}
			},
			files: {
				src: ['deploy/<%= pkg.version %>/<%= pkg.name %>.zip']
			}
		},

		// # Internationalization 

		// Add text domain
		addtextdomain: {
			options: {
				textdomain: '<%= pkg.domain %>',    // Project text domain.
				updateDomains: [ '<%= pkg.domain %>', '<%= pkg.name %>', 'woocommerce' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: ['*.php', '**/*.php', '!node_modules/**', '!build/**']
				}
			}
		},

		// Generate .pot file
		makepot: {
			target: {
				options: {
					domainPath: '/languages', // Where to save the POT file.
					exclude: ['build/.*', 'svn/.*'], // List of files or directories to ignore.
					mainFile: '<%= pkg.name %>.php', // Main project file.
					potFilename: '<%= pkg.domain %>.pot', // Name of the POT file.
					type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
					potHeaders: {
						'Report-Msgid-Bugs-To': ''
					}
				}
			}
		},

		// bump version numbers
		replace: {
			Version: {
				src: [
					'readme.txt',
					'<%= pkg.name %>.php',
					],
				overwrite: true,
				replacements: [
					{ 
						from: /\*\*Stable tag:\*\* '.*.'/m,
						to: "*Stable tag:* '<%= pkg.version %>'"
				},
					{
						from: /Stable tag:.*$/m,
						to: "Stable tag: <%= pkg.version %>"
				},
					{ 
						from: /Version:.*$/m,
						to: "Version: <%= pkg.version %>"
				},
					{ 
						from: /public \$version = \'.*.'/m,
						to: "public $version = '<%= pkg.version %>'"
				},
					{ 
						from: /public static \$version = \'.*.'/m,
						to: "public static $version = '<%= pkg.version %>'"
				}
				]
			}
		}

	});

	// makepot and addtextdomain tasks
	grunt.loadNpmTasks('grunt-wp-i18n');

	// ES6 compat
	grunt.loadNpmTasks('grunt-contrib-uglify-es');

	// Default task(s).
	grunt.registerTask('default', ['jshint', 'uglify']);

	grunt.registerTask('docs', ['wp_readme_to_markdown']);

	grunt.registerTask('test', ['jshint']);

	grunt.registerTask('zip', ['clean', 'copy', 'compress']);

	grunt.registerTask('assets', ['test', 'replace', 'uglify', 'cssmin']);

	grunt.registerTask('build', ['assets', 'addtextdomain', 'makepot', 'wp_readme_to_markdown']);

	grunt.registerTask('release', ['build',  'zip', 'clean']);

};
