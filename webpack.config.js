const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const { ProvidePlugin } = require('webpack');
const FileManagerPlugin = require('filemanager-webpack-plugin');

module.exports = {
  mode: 'development',
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-react', '@babel/preset-env'],
          },
        },
      },
      {
        test: /\.(sa|sc|c)ss$/,
        use: ['style-loader', 'css-loader', 'sass-loader'],
      },
      {
        test: /\.(gif|png|jpe?g|svg)$/i,
        type: 'asset/resource',
        generator: {
          filename: 'image/[contenthash][ext][query]',
        },
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/,
        type: 'asset/resource',
        generator: {
          filename: 'fonts/[contenthash][ext][query]',
        },
      },
    ],
  },

  plugins: [
    new ProvidePlugin({
      process: 'process/browser',
    }),
    new HtmlWebpackPlugin({
      inject: false,
      filename: 'includes/settings.php',
      template: './wp-content/plugins/aesirx-analytics/includes/settings.php',
      minify: false,
    }),

    new FileManagerPlugin({
      events: {
        onEnd: {
          copy: [
            {
              source: path.resolve(__dirname, './node_modules/aesirx-bi-app/public/assets/images/'),
              destination: path.resolve(__dirname, './dist/plugin/aesirx-analytics/assets/images/'),
            },
            {
              source: path.resolve(__dirname, './node_modules/aesirx-bi-app/public/assets/data/'),
              destination: path.resolve(__dirname, './dist/plugin/aesirx-analytics/assets/data/'),
            },
          ],
        },
      },
    }),
  ],

  output: {
    filename: 'assets/bi/js/[name].[contenthash].js',
    publicPath: '/wp-content/plugins/aesirx-analytics/',
    clean: true,
  },

  optimization: {
    splitChunks: {
      chunks: 'all',
    },
  },
  resolve: {
    alias: {
      react$: require.resolve(path.resolve(__dirname, './node_modules/react')),
    },
    extensions: ['.tsx', '.ts', '.js'],
  },
};
