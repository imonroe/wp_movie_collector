module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
		jquery: true,
	},
	globals: {
		wp: 'readonly',
		wp_movie_collector_admin: 'readonly',
		wp_movie_collector_public: 'readonly',
	},
};
