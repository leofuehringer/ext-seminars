# Change log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).

## x.y.z

### Added
- Add an SVG extension icon (#34)

### Changed

### Deprecated

### Removed

### Fixed
- Adapt the calls to cObj->IMAGE to TYPO3 8.7 (#28)
- Increase the maximum file sizes for images (#27)

## 1.4.1

### Fixed
- fix the sorting in the daily registration digest (#23)
- require mkforms >= 3.0.14 (#22)

## 1.4.0

### Added
- configurable default value for the "register myself" checkbox (#19)
- Composer script for PHP linting (#7)
- add TravisCI builds

### Changed
- disable the legacy BE module in TYPO3 8LTS (#15)
- require mkforms >= 3.0.0 (#6)
- require static_info_tables >= 6.3.7 (#4)
- move the extension to GitHub

### Fixed
- automatically create the uploads folder on install (#20)
- add missing localized label for organizer in notification email (#18)
- require Scheduler in development mode (#17)
- skip the Scheduler-related tests if that extension is not installed (#16)
- fix autoloading when running the tests in the BE module in non-composer mode (#11)

## 1.3.0

The [change log up to version 1.3.0](Documentation/changelog-archive.txt)
has been archived.