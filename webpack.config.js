const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const TsconfigPathsPlugin = require( 'tsconfig-paths-webpack-plugin' );

// Define JavaScript entry points

// const entryPoints = getWebpackEntryPoints();
// Object.keys(entryPoints).forEach((entryPoint) => {
// 	const newName = entryPoint.replace('assets/blocks/', '');
// 	entryPoints[newName] = entryPoints[entryPoint];
// 	delete entryPoints[entryPoint];
// 	delete defaultConfig.entry[entryPoint];
// });

const mainConfig = {
	...defaultConfig,
	// context: path.resolve(__dirname, 'src', 'assets'),
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.svg$/,
				use: [ '@svgr/webpack', 'url-loader' ],
			},
		],
	},
	entry: {
		admin: path.resolve( process.cwd(), 'packages/admin', 'index.tsx' ),
	},
	resolve: {
		...defaultConfig.resolve,
		plugins: [
			...( defaultConfig.resolve.plugins || [] ),
			new TsconfigPathsPlugin( { configFile: './tsconfig.json' } ),
		],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) => plugin.constructor.name !== 'CleanWebpackPlugin'
		),
	],
};

const reactJSXRuntimePolyfill = {
	entry: {
		'react-jsx-runtime': {
			import: 'react/jsx-runtime',
		},
	},
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: 'react-jsx-runtime.js',
		library: {
			name: 'ReactJSXRuntime',
			type: 'window',
		},
	},
	externals: {
		react: 'React',
	},
	plugins: [],
};

module.exports = [ mainConfig, reactJSXRuntimePolyfill ];
