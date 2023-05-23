const zip = require('gulp-zip');
const gulp = require('gulp');
const composer = require('gulp-composer');
const webpack = require('webpack-stream');
const { watch, series } = require('gulp');
const _ = require('lodash');

require('dotenv').config();

const dist = process.env.DIST;

async function cleanTask() {
  const del = await import('del');
  return del.deleteAsync(`${dist}/plugins/aesirx-analytics/**`, { force: true });
}

function movePluginFolderTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-analytics/**'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-analytics`));
}

function moveAnalyticJSTask() {
  return gulp
    .src(['./node_modules/aesirx-analytics/dist/analytics.js'])
    .pipe(gulp.dest(`${dist}/plugins/aesirx-analytics/assets/js`));
}

function webpackBIApp() {
  return gulp
    .src('./assets/bi/index.tsx')
    .pipe(webpack(require('./webpack.config.js')))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-analytics`));
}

function webpackBIAppWatch() {
  return gulp
    .src('./assets/bi/index.tsx')
    .pipe(webpack(_.merge(require('./webpack.config.js'), { watch: true })))
    .pipe(gulp.dest(`${dist}/plugins/aesirx-analytics`));
}

function compressTask() {
  return gulp
    .src(`${dist}/plugins/**`)
    .pipe(zip('plg_aesirx_analytics.zip'))
    .pipe(gulp.dest('./dist'));
}

function composerTask() {
  return composer({
    'working-dir': `${dist}/plugins/aesirx-analytics`,
    'no-dev': true,
  });
}

async function cleanComposerTask() {
  const del = await import('del');
  return del.deleteAsync(`${dist}/plugins/aesirx-analytics/composer.*`, {
    force: true,
  });
}

exports.zip = series(
  cleanTask,
  movePluginFolderTask,
  moveAnalyticJSTask,
  webpackBIApp,
  composerTask,
  cleanComposerTask,
  compressTask,
  cleanTask
);

exports.watch = function () {
  watch('./assets/**', series(webpackBIAppWatch));
  watch('./wp-content/plugins/aesirx-analytics/**', series(movePluginFolderTask, composerTask));
};
