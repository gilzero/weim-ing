// --------------------------------------------------
// Load Plugins
// --------------------------------------------------

var gulp = require('gulp'),
    sass = require('gulp-dart-scss'),
    postcss = require("gulp-postcss"),
    autoprefixer = require("autoprefixer"),
    cssnano = require("cssnano"),
    notify = require('gulp-notify'),
    sassUnicode = require('gulp-sass-unicode');


var config = {
    // main scss files that import partials
    scssSrc: 'scss/*.scss',
    // all scss files in the scss directory
    allScss: 'scss/**/*.scss',
    // the destination directory for our css
    cssDest: 'css/',
    // all js files the js directory
    allJs: 'assets/js/**/*.js',
    // all img files
    allImgs: 'assets/img/**/*'
};


// Define tasks after requiring dependencies
function style() {

  return gulp.src(config.allScss)
    .pipe(sass())
    .pipe(sassUnicode())
    .pipe(postcss([autoprefixer()]))
    .pipe(gulp.dest(config.cssDest));

  gulp.task('sass:watch', function () {
    gulp.watch('./scss/**/*.scss', ['sass']);
  });
}

// Expose the task by exporting it
// This allows you to run it from the commandline using
// $ gulp style
exports.style = style;

function watch(){
    // gulp.watch takes in the location of the files to watch for changes
    // and the name of the function we want to run on change
    gulp.watch('scss/**/*.scss', style)
}

// Don't forget to expose the task!
exports.watch = watch
