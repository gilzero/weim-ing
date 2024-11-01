const path = require('path');
const autoprefixer = require('autoprefixer');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const SuppressChunksPlugin = require('suppress-chunks-webpack-plugin').default;
const SpriteLoaderPlugin = require('svg-sprite-loader/plugin');

function tryResolve_(url, sourceFilename) {
  try {
    return require.resolve(url, {paths: [path.dirname(sourceFilename)]});
  } catch (e) {
    return '';
  }
}

function tryResolveScss(url, sourceFilename) {
  const normalizedUrl = url.endsWith('.scss') ? url : `${url}.scss`;
  return tryResolve_(normalizedUrl, sourceFilename) ||
    tryResolve_(path.join(path.dirname(normalizedUrl), `_${path.basename(normalizedUrl)}`),
      sourceFilename);
}

function materialImporter(url, prev) {
  if (url.startsWith('@material')) {
    const resolved = tryResolveScss(url, prev);
    return {file: resolved || url};
  }
  return {file: url};
}

// Determine webpack build mode.
const mode = process.env.NODE_ENV || 'development';

module.exports = [{
  mode,
  entry: {
    base: ['./js/base.js', './scss/base.scss'],
    mdc: ['./js/mdc.js', './scss/mdc.scss'],
    // Adjusted version of Drupal Core's user.js.
    user: './js/user.js',
    // Imports all images and icons for handling by webpack.
    assets: './js/assets.js',
    // CSS only entries (without JS part).
    // Should be also added to 'SuppressChunksPlugin' config below.
    grid: './scss/grid.scss',
    fonts: './scss/fonts.scss',
    'icons-font': './scss/icons-font.scss',
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'js/[name].js',
    sourceMapFilename: '[file].map',
    clean: true,
  },
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader
          },
          { loader: 'css-loader',
            options: {
              sourceMap: true,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: () => [autoprefixer()],
                sourceMap: true,
              }
            }
          },
          {
            loader: 'sass-loader',
            options: {
              // Prefer Dart Sass
              implementation: require('sass'),
              sassOptions: {
                includePaths: ['./node_modules'],
                importer: materialImporter
              },
              sourceMap: true,
            }
          },
        ]
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        options: {
          presets: ['@babel/preset-env'],
        },
      },
      {
        test: /\.(png|jpg|jpeg|webp|svg)$/,
        exclude: /icons\/.*\.svg$/,
        type: 'asset/resource',
        generator: {
          filename: './[path][name][ext]'
        },
        use: [
          {
            loader: 'image-webpack-loader',
            options: {
              emitFile: false,
              mozjpeg: {
                progressive: true,
                quality: 90
              },
              optipng: {
                enabled: false,
              },
              pngquant: {
                quality: [0.85, 0.90],
                speed: 4
              },
              svgo: {
                enabled: true,
              },
              gifsicle: {
                enabled: false,
              },
              webp: {
                quality: 90
              }
            }
          },
        ]
      },
      {
        test: /icons\/.*\.svg$/,
        loader: 'svg-sprite-loader',
        options: {
          extract: true,
          spriteFilename: './images/icons.svg',
          runtimeCompat: true
        }
      },
    ]
  },
  devtool: mode === 'development' ? 'source-map' : undefined,
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/[name].css',
      chunkFilename: 'css/[id].css',
    }),
    new SuppressChunksPlugin([
      'assets',
      { name: 'fonts', match: /\.js$|\.js\.map$/ },
      { name: 'grid', match: /\.js$|\.js\.map$/ },
      { name: 'icons-font', match: /\.js$|\.js\.map$/ },
    ]),
    new SpriteLoaderPlugin({
      plainSprite: true
    }),
  ],
  stats: {
    children: false
  },
}];
