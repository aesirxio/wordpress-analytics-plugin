const zip = require("gulp-zip")
const gulp = require('gulp')

async function cleanTask() {
    const del = await import("del")
    return del.deleteAsync('./dist/plugin/**', {force:true});
}

function movePluginFolderTask() {
    return gulp.src([
        './wp-content/plugins/aesirx-analytics/**',
        '!./wp-content/plugins/aesirx-analytics/assets/src/**'
    ]).pipe(gulp.dest('./dist/plugin/aesirx-analytics'))
}

function compressTask() {
    return gulp.src('./dist/plugin/**')
        .pipe(zip('plg_aesirx_analytics.zip'))
        .pipe(gulp.dest('./dist'));
}

exports.zip = gulp.series(
    cleanTask,
    movePluginFolderTask,
    compressTask,
    cleanTask
);
