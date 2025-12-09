const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
};

const modulesAssets = {
    'config': [
        'backend/ts/main.ts',
    ],
    'uninstall': [
        'backend/ts/main.ts',
    ],
    'checkout': [
        'frontend/ts/main.ts',
    ],
    'return-page': [
        'frontend/ts/main.ts',
    ],
    'hosted-tokenization-gateway': [
        'frontend/ts/main.ts',
        'frontend/ts/blocks.tsx',
    ],
};

const entries = {};
for (const [moduleId, assets] of Object.entries(modulesAssets)) {
    for (let relativePath of assets) {
        const name = moduleId + '-' + relativePath
            .replace(/\.[tj]sx?$/g, '')
            .split('/')
            .filter(p => !['ts', 'js'].includes(p))
            .join('-');
        let fullModuleId = moduleId;
        if (!fullModuleId.startsWith('worldline-')) {
            fullModuleId = 'worldline-' + fullModuleId;
        }
        const path = `./modules/inpsyde/${fullModuleId}/resources/${relativePath}`;

        entries[name] = path;
    }
}


module.exports = {
    ...config,
    entry: entries,
    output: {
        publicPath: './',
        path: __dirname + '/assets',
        filename: '[name].js',
    }
};
