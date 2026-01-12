const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const fs = require('fs');

// Find flux-plugins-common directory
// It could be in vendor-prefixed or at the repo root
function findFluxPluginsCommonDir() {
  const possiblePaths = [
    path.resolve(__dirname, '../../flux-plugins-common'),
    path.resolve(__dirname, 'vendor-prefixed/stratease/flux-plugins-common'),
    path.resolve(__dirname, '../../../flux-plugins-common'),
  ];

  for (const possiblePath of possiblePaths) {
    if (fs.existsSync(possiblePath) && fs.existsSync(path.join(possiblePath, 'webpack.config.helpers.js'))) {
      return possiblePath;
    }
  }

  // Fallback: assume repo root
  return path.resolve(__dirname, '../../flux-plugins-common');
}

const commonLibDir = findFluxPluginsCommonDir();
const { createBaseWebpackConfig } = require(path.join(commonLibDir, 'webpack.config.helpers'));

// Get base config from flux-plugins-common
const baseConfig = createBaseWebpackConfig({
  pluginDir: __dirname,
  pluginSlug: 'flux-media-optimizer',
});

// Merge with plugin-specific config
module.exports = {
  ...baseConfig,
  entry: {
    ...baseConfig.entry,
    admin: './assets/js/src/admin/index.js',
    attachment: './assets/js/src/admin/attachment.js',
    'compatibility-dismiss': './assets/js/src/admin/compatibility-dismiss.js',
    // Note: license-page is built separately by flux-plugins-common
    // and loaded via MenuService enqueue
  },
  output: {
    ...baseConfig.output,
    path: path.resolve(__dirname, 'assets/js/dist'),
    filename: '[name].bundle.js',
    clean: true,
  },
  resolve: {
    ...baseConfig.resolve,
    extensions: ['.js', '.jsx'],
    // Add common library's node_modules to module resolution
    // This allows the plugin to import and compile shared React components from common library
    // Order matters: check plugin's node_modules first, then common library's
    modules: [
      path.resolve(__dirname, 'node_modules'), // Plugin's node_modules first
      path.join(commonLibDir, 'node_modules'),   // Common library's node_modules
      'node_modules',                             // Fallback
    ],
    alias: {
      ...baseConfig.resolve.alias,
      '@flux-media-optimizer': path.resolve(__dirname, 'assets/js/src'),
      // For importing shared components (like PageLayout) from common library
      // Use the source path - these will be compiled by plugin's webpack using React from plugin's node_modules
      '@flux-plugins-common': path.join(commonLibDir, 'assets/js/src'),
    },
  },
  module: {
    // Module rules are inherited from baseConfig
    // Additional plugin-specific rules can be added here if needed
    rules: [
      ...baseConfig.module.rules,
    ],
  },
  plugins: [
    new HtmlWebpackPlugin({
      template: './assets/js/src/admin/index.html',
      filename: 'admin.html',
      chunks: ['admin'],
    }),
  ],
  devServer: {
    static: {
      directory: path.join(__dirname, 'assets/js/dist'),
    },
    compress: true,
    port: 3000,
    hot: true,
  },
  externals: {
    ...baseConfig.externals,
  },
};
