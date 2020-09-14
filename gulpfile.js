var gulp  = require('gulp');
var chmod = require('gulp-chmod');

gulp.task('dev', function(){
    return gulp.src(['*.php', '*.js', '*.ini', '*.txt', 'version 3/*.php', 'languages/*.json', 'test/*.js', '!gulpfile.js', '!test/unit-test-*.js'], {"base":"."})
        .pipe(chmod(0644))
        .pipe(gulp.dest('/var/www/html/wp-content/plugins/w3tc-minify-helper'))
});

