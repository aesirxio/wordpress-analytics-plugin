const zip = require('gulp-zip');
const gulp = require('gulp');
const composer = require('gulp-composer');
const webpack = require('webpack-stream');

async function cleanTask() {
  const del = await import('del');
  return del.deleteAsync('./dist/plugin/**', { force: true });
}

function movePluginFolderTask() {
  return gulp
    .src(['./wp-content/plugins/aesirx-analytics/**'])
    .pipe(gulp.dest('./dist/plugin/aesirx-analytics'));
}

function moveAnalyticJSTask() {
  return gulp
    .src(['./node_modules/aesirx-analytics/dist/analytics.js'])
    .pipe(gulp.dest('./dist/plugin/aesirx-analytics/assets/js'));
}

function webpackBIApp() {
  return gulp
    .src('./assets/bi/index.tsx')
    .pipe(webpack(require('./webpack.config.js')))
    .pipe(gulp.dest('./dist/plugin/aesirx-analytics'));
}

function compressTask() {
  return gulp
    .src('./dist/plugin/**')
    .pipe(zip('plg_aesirx_analytics.zip'))
    .pipe(gulp.dest('./dist'));
}

function composerTask() {
  return composer({
    'working-dir': './dist/plugin/aesirx-analytics',
  });
}

async function cleanComposerTask() {
  const del = await import('del');
  return del.deleteAsync('./dist/plugin/aesirx-analytics/composer.*', {
    force: true,
  });
}

exports.zip = gulp.series(
  cleanTask,
  movePluginFolderTask,
  moveAnalyticJSTask,
  webpackBIApp,
  composerTask,
  cleanComposerTask,
  compressTask,
  cleanTask
);
