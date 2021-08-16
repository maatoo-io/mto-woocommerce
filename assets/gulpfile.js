const gulp = require('gulp'),
  browsersync = require('browser-sync'),
  concat = require('gulp-concat'),
  uglify = require('gulp-uglify'),
  babel = require('gulp-babel'),
  del = require('del'),
  sass = require('gulp-sass'),
  plumber = require('gulp-plumber'),
  notify = require('gulp-notify'),
  autoprefixer = require('gulp-autoprefixer'),
  sourcemaps = require('gulp-sourcemaps'),
  gulpif = require('gulp-if'),
  argv = require('yargs').argv,
  cleanCSS = require('gulp-clean-css')

/** styles */

gulp.task('styles', function () {
  return gulp.src(['dev/scss/styles.scss', 'dev/scss/admin-styles.scss'])
    .pipe(plumber())
    .pipe(gulpif(argv.dev, sourcemaps.init()))
    .pipe(sass())
    .on('error', notify.onError(function (error) {
      return 'Something happened: ' + error.message
    }))
    .pipe(autoprefixer(['last 2 version']))
    .pipe(cleanCSS())
    .pipe(gulpif(argv.dev, sourcemaps.write()))
    .pipe(gulp.dest('dist/css'))
    .pipe(gulpif(argv.dev, browsersync.reload({
      stream: true
    })))
})

/** scripts */

gulp.task('scripts', function (done) {
  const scripts = {
    index: [
      'dev/js/scripts.js',
      'dev/js/admin.js'
    ]
  }
  Object.keys(scripts).forEach(name => {
    gulp.src(scripts[name])
      .pipe(babel({
        presets: ['@babel/env']
      }))
      .pipe(gulpif(argv.prod, uglify()))
      .pipe(gulp.dest('dist/js/'))
      .pipe(gulpif(argv.dev, browsersync.reload({
        stream: true
      })))
  })
  done()
})

gulp.task('libs', function (done) {
  const libs = {
  }
  Object.keys(libs).forEach(name => {
    gulp.src(libs[name])
      .pipe(concat('libs.js'))
      .pipe(gulp.dest('dist/js/'))
  })
  done()
})

gulp.task('repaint', (done) => {
  browsersync({
    server: {
      baseDir: 'dist',
      directory: true
    }
  })
  done()
})

gulp.task('clean', function (done) {
  del.sync(['dist/js', 'dist/css'])
  done()
})

gulp.task('watch', () => {
  gulp.watch('dev/scss/**/*.scss', gulp.series('styles'))
  gulp.watch('dev/js/**/*.js', gulp.series('scripts'))
})

gulp.task('production', (done) => {
  gulp.series('clean', gulp.parallel('styles', 'libs', 'scripts'))(done)
})
gulp.task('dev', (done) => {
  gulp.series('watch')(done)
})