const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  mode: 'production',
  entry: './src/index.js',
  output: {
    path: path.resolve(__dirname, '../build'),
    filename: 'index.js',
    clean: true,
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: [
              ['@babel/preset-env', { targets: { node: 'current' } }],
              '@babel/preset-react'
            ]
          }
        }
      },
      {
        test: /\.css$/,
        use: [MiniCssExtractPlugin.loader, 'css-loader']
      },{
        test: /\.svg$/,
        use: ['@svgr/webpack']
      }
    ]
  },
  plugins: [new MiniCssExtractPlugin({ filename: 'index.css' })]
};