const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'smart-search': path.resolve(__dirname, 'inc/custom/search-ai/assets/js/src/index.js')
    },
    output: {
        path: path.resolve(__dirname, 'inc/custom/search-ai/assets/js/build'),
        filename: '[name].js'
    }
};