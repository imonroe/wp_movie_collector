const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );

module.exports = ( env, argv ) => {
	const isProduction = argv.mode === 'production';

	// Explicit CSS output path per JS entry, rather than deriving it by string-
	// replacing '/js/' → '/css/' (which silently assumed every entry name
	// contains '/js/'). Keep this in sync with `entry` below.
	const cssOutputByEntry = {
		'admin/js/wp-movie-collector-admin': 'admin/css/wp-movie-collector-admin',
		'public/js/wp-movie-collector-public': 'public/css/wp-movie-collector-public',
	};

	return {
		entry: {
			'admin/js/wp-movie-collector-admin': './src/admin/js/admin.js',
			'public/js/wp-movie-collector-public': './src/public/js/public.js',
		},
		output: {
			path: path.resolve( __dirname, 'dist' ),
			filename: '[name]' + ( isProduction ? '.min.js' : '.js' ),
			clean: true,
		},
		module: {
			rules: [
				{
					test: /\.js$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader',
						options: {
							presets: [ '@babel/preset-env' ],
						},
					},
				},
				{
					test: /\.css$/,
					use: [
						MiniCssExtractPlugin.loader,
						'css-loader',
					],
				},
			],
		},
		plugins: [
			new MiniCssExtractPlugin( {
				filename: ( pathData ) => {
					const base = cssOutputByEntry[ pathData.chunk.name ] || pathData.chunk.name;
					return base + ( isProduction ? '.min.css' : '.css' );
				},
			} ),
		],
		optimization: {
			minimizer: [
				'...',
				new CssMinimizerPlugin(),
			],
		},
		devtool: isProduction ? false : 'source-map',
	};
};
