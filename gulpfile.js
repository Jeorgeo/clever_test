//Подлючаем модули галпа
const gulp = require('gulp');
//Склеивание файлов
const concat = require('gulp-concat');
//Автоматическое добавление префиксов
const autoprefixer = require('gulp-autoprefixer');
//Минификация стилей
const cleanCSS = require('gulp-clean-css');
//Минификация JS
const babel = require('gulp-babel');
const uglify = require('gulp-uglify');
//Удаление файлов
const del = require('del');
//Синхронизация с браузером
const browserSync = require('browser-sync').create();
//Для препроцессоров
const sourcemaps = require('gulp-sourcemaps');
//Модуль компиляции SAAS
 const sass = require('gulp-sass');
// const sass = require('gulp-sass')(require('sass'));

// sass.compiler = require('node-sass');

//Отслеживаем изменения
const watch = require('gulp-watch');

//Проект :
const project = '.';
const projectDest = './local/components/clever/form/templates/.default';

//Порядок подключения css файлов
const styleFiles = [
    project + '/src/scss/normalize.scss',
    project + '/src/scss/typography.scss',
    project + '/src/scss/main.scss',
    project + '/src/scss/media.scss'
]

//Порядок подключения js файлов

const scriptFiles = [
  project + '/src/js/main.js',
]

// Таск для обработки стилей
gulp.task('styles', () => {
  //Шаблон для поиска файлов CSS
  //Всей файлы по шаблону './src/css/**/*.css'
  return gulp.src(styleFiles)
  //Создаем карту для навигации по таблице стилей
  .pipe(sourcemaps.init())
  //Компиляция в *.css Указать stylus() , sass() или less()
  .pipe(sass().on('error', sass.logError))
  //Объединение файлов в один
  .pipe(concat('style.css'))
  //Добавить префиксы
  .pipe(autoprefixer({
    cascade: true
  }))
  //Минификация CSS
  // .pipe(cleanCSS({
  //   level: 2
  // }))
  .pipe(sourcemaps.write('./'))
  //Выходная папка для стилей
  .pipe(gulp.dest(projectDest))
  .pipe(browserSync.stream());
});

//Таск для обработки скриптов

gulp.task('scripts', () => {
   //Шаблон для поиска файлов JS
   //Всей файлы по шаблону './src/js/**/*.js'
   return gulp.src(scriptFiles)
      //Объединение файлов в один
      .pipe(concat('script.js'))
      //Preset

      // .pipe(babel({
      //   presets: ['@babel/env']
      // }))

      //Минификация JS

      // .pipe(uglify({
      //    toplevel: true
      // }))

      //Выходная папка для скриптов
      .pipe(gulp.dest(projectDest))
      .pipe(browserSync.stream());
});

//Таск для очистки папки build
gulp.task('del', () => {
   return del([projectDest + '*.css'])
   return del([projectDest + '*.js'])
});

//Таск для отслеживания изменений в файлах
gulp.task('watch', () => {
   browserSync.init({
      server: {
         baseDir: project
      }
   });
   //Следить за файлами со стилями с нужным расширением
   gulp.watch(project +'/src/scss/**/*.scss', gulp.series('styles'))
   //Следить за JS файлами
   gulp.watch(project +'/src/js/**/*.js', gulp.series('scripts'))
   //При изменении HTML запустить синхронизацию
   // gulp.watch(project + "/*.html").on('change', browserSync.reload);
});

//Таск по умолчанию, Запускает del, styles, scripts и watch

gulp.task('default', gulp.series('del', gulp.parallel('styles', 'scripts'), 'watch'));
