const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.tsx?$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
};

module.exports = {
    ...config,
    entry: {
        'frontend-main': './resources/frontend/ts/main.ts'
    },
    output: {
        publicPath: './',
        path: __dirname + '/assets',
        filename: '[name].js',
    }
};
