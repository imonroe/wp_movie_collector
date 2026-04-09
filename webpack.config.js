const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );

module.exports = ( env, argv ) => {
	const isProduction = argv.mode === 'production';

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
					// Map JS entry names to CSS output paths
					const name = pathData.chunk.name
						.replace( '/js/', '/css/' );
					return name + ( isProduction ? '.min.css' : '.css' );
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
