/**
 * @file defines the webpack plugin configuration for specific CKEditor plugins.
 */
// cSpell:ignore svgs

import path from 'path';
import CopyPlugin from 'copy-webpack-plugin';
import type { WebpackPluginInstance } from 'webpack';

/**
 * Contains the webpack plugins to be used when building specific CKEditor plugins.
 */
const webpackPluginConfig: { [key: string]: WebpackPluginInstance[]; } = {
  icon: [
    new CopyPlugin({ // "to" paths are relative to the build folder – in this case `./js/build`.
      patterns: [
        {
          from: path.resolve(__dirname, 'node_modules/fontawesome6/metadata/(categories|icons).yml'),
          to: '../../libraries/fontawesome6/metadata/[name][ext]'
        },
        {
          from: path.resolve(__dirname, 'node_modules/fontawesome5/metadata/(categories|icons).yml'),
          to: '../../libraries/fontawesome5/metadata/[name][ext]'
        },
        {
          from: path.resolve(__dirname, 'node_modules/fontawesome(6|5)/package.json'),
          to: '../../libraries/versions.yml',
          transformAll: (assets) =>
            assets.reduce((accumulator, asset) => `${accumulator}${path.basename(path.dirname(asset.sourceFilename))}: '${JSON.parse(asset.data.toString())['version'] as string}'\n`, '')
        },
        {
          from: path.resolve(__dirname, 'node_modules/fontawesome6/svgs/solid/icons.svg'),
          to: '../../icons/icon.svg'
        }
      ]
    })
  ]
};

export default webpackPluginConfig;
