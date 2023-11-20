const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

// Define JavaScript entry points

// const entryPoints = getWebpackEntryPoints();
// Object.keys(entryPoints).forEach((entryPoint) => {
// 	const newName = entryPoint.replace('assets/blocks/', '');
// 	entryPoints[newName] = entryPoints[entryPoint];
// 	delete entryPoints[entryPoint];
// 	delete defaultConfig.entry[entryPoint];
// });

module.exports = {
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
};
