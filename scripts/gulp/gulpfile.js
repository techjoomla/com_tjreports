var gulp = require('gulp');
var gulpif = require('gulp-if');
var uglify = require('gulp-uglify');
var symlink = require('gulp-symlink');
var gutil = require('gulp-util');
var fs = require('fs');
var path = require('path');
var del = require('del');
var chug = require('gulp-chug');

var minimist = require('minimist');
var extensions = JSON.parse(fs.readFileSync('./config.json', 'utf8'));

var knownOptions = {
  string: ['path', 'tjlibpath', 'strapperpath']
};

var options = minimist(process.argv.slice(2), knownOptions);

gulp.task('log', function() {
  gutil.log(options)
});

// Set up sources and destinations to process in symlinks
var sources = [];
var destinations = [];
var linkpath = options.path;
var tjlibpath = options.tjlibpath;
var strapperpath = options.strapperpath;

for (var i = extensions.plugins.length - 1; i >= 0; i--) {
	var plugin = extensions.plugins[i];
	var pluginPath = '../../plugins/'+plugin.type+'/'+plugin.name;
	if (fs.existsSync(pluginPath)) {
		sources.push(pluginPath);
		destinations.push(linkpath + '/plugins/' + plugin.type + '/' + plugin.name);
	}
}

for (var i = extensions.other.length - 1; i >= 0; i--) {
	var other = extensions.other[i];
	var otherPath = '../../'+other.src;
	if (fs.existsSync(otherPath)) {
		sources.push(otherPath);
		destinations.push(linkpath + '/' + other.folder + '/' + other.dest);
	}
}

for (var i = extensions.components.length - 1; i >= 0; i--) {
	var component = extensions.components[i];

	// Admin
	if (fs.existsSync('../../'+component.admin)) {
		sources.push('../../'+component.admin);
		destinations.push(linkpath + '/administrator/components/'+component.name);
	}

	// Frontend
	if (fs.existsSync('../../'+component.site)) {
		sources.push('../../'+component.site);
		destinations.push(linkpath + '/components/'+component.name);
	}
	
	// Manifest
	if (fs.existsSync('../../'+component.manifest)) {
		sources.push('../../'+component.manifest);
		destinations.push(linkpath + '/administrator/components/'+component.name+'/'+ path.basename(component.manifest));
	}

	// Frontend Language
	if (fs.existsSync('../../'+component.site+'/language/en-GB/en-GB.'+component.name+'.ini')) {
		sources.push('../../'+component.site+'/language/en-GB/en-GB.'+component.name+'.ini');
		destinations.push(linkpath + '/language/en-GB/en-GB.'+component.name+'.ini');
	}

	// Admin language
	if (fs.existsSync('../../'+component.site+'/language/en-GB/en-GB.'+component.name+'.ini')) {
		sources.push('../../'+component.admin+'/language/en-GB/en-GB.'+component.name+'.ini');
		destinations.push(linkpath + '/administrator/language/en-GB/en-GB.'+component.name+'.ini');
	}

	if (fs.existsSync('../../'+component.site+'/language/en-GB/en-GB.'+component.name+'.menu.ini')) {
		sources.push('../../'+component.admin+'/language/en-GB/en-GB.'+component.name+'.menu.ini');
		destinations.push(linkpath + '/administrator/language/en-GB/en-GB.'+component.name+'.menu.ini');
	}

	if (fs.existsSync('../../'+component.site+'/language/en-GB/en-GB.'+component.name+'.menu.ini')) {
		sources.push('../../'+component.admin+'/language/en-GB/en-GB.'+component.name+'.sys.ini');
		destinations.push(linkpath + '/administrator/language/en-GB/en-GB.'+component.name+'.sys.ini');
	}
}

gutil.log(sources);
gutil.log(destinations);

// Dependencies
chug_args = [];
chug_args.push('--tjlibpath='+tjlibpath);
chug_args.push('--strapperpath='+strapperpath);
chug_args.push('--path='+linkpath);
chug_paths = [tjlibpath + '/scripts/gulp/gulpfile.js', strapperpath + '/scripts/gulp/gulpfile.js'];

gulp.task('createlinks', function () {
    gulp.src(chug_paths).pipe(chug({args: chug_args, tasks: ["createlinks"]}));
	return gulp.src(sources).pipe(symlink(destinations));	
});

gulp.task('deletelinks', function () {
    gulp.src(chug_paths).pipe(chug({args: chug_args, tasks: ["deletelinks"]}));
	return del(destinations.reverse(), {force: true});
});

gulp.task('links', ['deletelinks','createlinks']);
