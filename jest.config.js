module.exports = {
	testEnvironment: 'jsdom',
	testMatch: [ '**/tests/js/**/*.test.js' ],
	// Transform ES modules with babel-jest (scoped to Jest; webpack keeps its
	// own babel-loader config). Targets the running Node so import/export work.
	transform: {
		'^.+\\.js$': [
			'babel-jest',
			{ presets: [ [ '@babel/preset-env', { targets: { node: 'current' } } ] ] },
		],
	},
};
