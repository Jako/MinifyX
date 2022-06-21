# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2022-06-21

### Changed

- Use Assetic instead of Munee
- Minify HTML with pfaciana/tiny-html-minifier

### Removed

- Complete code refactoring removing internal methods and system settings
- Removed global php method "minify"
- Removed the ability to use versions for assets
- Removed "preHooks" and "hooks"
- Removed image compression with Munee

## [1.7.1] - 2019-03-25

### Fixed

- Fixed bug with startup js register

## [1.7.0] - 2018-12-30

### Added

- Added php function "minify" to manage assets from the php code
- Added the ability to minify content
- Added the ability to use version for assets

### Changed

- Updated composer dependencies

## [1.6.0] - 2018-01-22

### Added

- Added the method "getFileUrl" to the MinifyX class
- Added the method "getFilePath" to the MinifyX class
- Added the "forceUpdate" snippet parameters and the same system setting
- Added the "forceDelete" system setting to delete all files in the cache directory
- Added the "cssTpl" and "jsTpl" snippet parameters
- Added new register type "print" for immediate output
- Added the file hook "fixVm.php" which fixes the "vmax" and "vmin" units after css compilation

### Changed

- Updated all Munee dependencies. You can do it anytime via composer
- Php version must be >= 5.6

### Fixed

- Some bugfixes

## [1.5.0] - 2018-01-19

### Added

- Added config file for groups (core/components/minifyx/config/groups.php)
- Added the "cssGroup" snippet parameter
- Added the "jsGroup" snippet parameter
- Added the "preHooks" snippet parameter. A hook can be either a snippet or a file, specified in the core/components/minifyx/hooks folder
- Added the "hooks" snippet parameter. A hook can be either a snippet or a file, specified in the core/components/minifyx/hooks folder
- Added the ability to run the snippet multiple times
- Added file hook "cssToPage.php" (&hooks=`cssToPage.php`)

## [1.4.4] - 2016-05-16

### Changed

- [#22] Updated sabberworm/php-css-parser to version 7.0.3

## [1.4.3] - 2016-04-28

### Changed

- [#21] Updated sabberworm/php-css-parser to version 7.0.2

## [1.4.2] - 2015-08-22

### Changed

- Updated Munee with all subpackages

### Fixed

- Fixed fatal error on PHP 5.3.3

## [1.4.1] - 2015-06-01

### Changed

- Updated Munee with all subpackages
- [#18] Improved work of plugin

## [1.3.1] - 2014-07-21

### Changed

- [#12] Support of MODX 2.3
- [#11] Improved set of "munee_cache" variable on Windows

## [1.3.0-pl1] - 2014-05-12

### Changed

- [#2] Added cut of comments in raw styles and scripts
- [#9] Changed lessphp to https://github.com/oyejorge/less.php
- Updated scssphp to version 0.0.10

### Fixed

- [#8] Fixed determining of cache dir for advanced site configuration
- [#7] Fixed clearing cache

## [1.3.0-b] - 2013-12-26

### Added

- Added plugin MinifyX that can process scripts, styles and images of web page

### Changed

- Refactored main class
- Rewrited snippet MinifyX
- Changed cached files format
- Improved connector in assets for image processing

## [1.2.2-pl3] - 2013-12-16

### Removed

- Removed phar file

### Fixed

- Fixed url of cache dir in subfolder installations
- Ability of commenting files by prefixing them by the dash

## [1.2.1] - 2013-12-03

### Fixed

- [#1] Replaced constant DIRECTORY_SEPARATOR to '/'

## [1.2.0] - 2013-11-23

### Fixed

- Fixed clean of parameter &cacheFolder

## [1.2.0-rc] - 2013-11-20

### Changed

- Updated leafo/lessphp to v0.4.0
- Updated leafo/scssphp to v0.0.8
- Improved error logging

## [1.2.0-b] - 2013-11-12

### Added

- Integrated Munee library from http://mun.ee
- Auto creation of cache dir
- More options to register files on frontend

## [1.1.3] - 2013-01-07

### Changed

- Absolute path in the URL attribute of all compressed files

## [1.1.2] - 2012-09-11

### Changed

- Improved caching of minified files

## [1.1.1] - 2012-09-10

### Fixed

- Removed E_WARNING on line 94 of minifyx.class.php

## [1.1.0] - 2012-09-09

### Added

- Added Douglas Crockford's JSMin https://github.com/rgrove/jsmin-php/
- Added properties to snippet
- Added parameters jsFilename && cssFilename

### Changed

- Changed css minifier to Minify_CSS_Compressor from https://code.google.com/p/minify/
- Serious refactor of code
- Improved caching of files

### Removed

- Removed plugin

## [1.0.0] - 2012-03-26

### Added

- Initial Version
