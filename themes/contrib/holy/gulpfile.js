import gulp from 'gulp';
import gulpSass from 'gulp-sass';
import * as sass from 'sass';
import sassGlob from 'gulp-sass-glob';
import autoprefixer from 'gulp-autoprefixer';
import pxtorem from 'gulp-pxtorem';
import postcss from 'gulp-postcss';
import gulpPrettier from 'gulp-prettier';

// pxToRemOptions
const pxToRemOptions = {
  rootValue: 16, // Set the base font size (1rem = 16px)
  unitPrecision: 5, // The decimal numbers to allow in rem units
  propList: ['*'], // Convert all properties
  selectorBlackList: [], // Ignore selectors
  replace: true, // Replace px values with rem values
  mediaQuery: false, // Ignore media queries
};

const sassCompiler = gulpSass(sass);

// scss task
export async function scss() {
  return gulp.src('./sass/components/**/*.scss')
    .pipe(sassGlob())
    .pipe(gulpPrettier())
    .pipe(postcss())
    .pipe(gulp.dest('./sass/components'));
}

// sass task
export function sassTask() {
  return gulp.src('./sass/*.scss')
    .pipe(sassGlob())
    .pipe(sassCompiler().on('error', sassCompiler.logError))
    .pipe(pxtorem(pxToRemOptions))
    .pipe(autoprefixer())
    .pipe(postcss())
    .pipe(gulpPrettier())
    .pipe(gulp.dest('./css'));
}

// Watch changes
export function watchFiles() {
  gulp.watch('./sass/**/*.scss', sassTask);
}

// Default task
gulp.task('default', gulp.series(sassTask, watchFiles));

// Individual tasks
gulp.task('scss', scss);
gulp.task('sass', sassTask);
