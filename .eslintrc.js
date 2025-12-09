const eslintConfig = {
    root: true,
    extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
    settings: {
        'import/resolver': {
            'eslint-import-resolver-custom-alias': {
                alias: {
                    '#shared': './shared/ts'
                },
                extensions: ['.ts', '.tsx'],
            },
        }
    }
};

module.exports = eslintConfig;
