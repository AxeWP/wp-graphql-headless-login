/**
 * @see https://prettier.io/docs/configuration
 * @type {import("prettier").Config}
 */

const defaultConfig = require( '@axepress/plugin-infra/prettier' );

const config = {
	...defaultConfig.default,
};

/**
 * @see https://prettier.io/docs/configuration
 * @type {import("prettier").Config}
 */
module.exports = config;
