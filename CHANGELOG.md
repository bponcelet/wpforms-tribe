# Changelog
All notable changes to this project will be documented in this file and formatted via [this recommendation](https://keepachangelog.com/).

## [1.4.0] - 2024-08-13
### Added
- More descriptive error messages for Tribe API.

### Changed
- The minimum WPForms version supported is 1.9.0.

### Fixed
- Compatibility with WPForms 1.9.0.
- An error was displayed on the Tribe panel in Builder if the Tribe integration was removed on the Settings > Integrations page.

## [1.3.0] - 2024-03-20
### IMPORTANT
- Support for PHP 5.6 has been discontinued. If you are running PHP 5.6, you MUST upgrade PHP before installing WPForms Tribe 1.3.0. Failure to do that will disable WPForms Tribe functionality.
- Support for WordPress 5.4 and below has been discontinued. If you are running any of those outdated versions, you MUST upgrade WordPress before installing WPForms Tribe 1.3.0. Failure to do that will disable WPForms Tribe functionality.

### Changed
- The minimum WPForms version supported is 1.8.4.
- Improved PHP 8.2 support.
- Improved Tribe error handling during connection creation.

### Fixed
- There was a conflict with 3rd-party plugins that use the Guzzle library.
- Various RTL problems in the admin dashboard.

## [1.2.0] - 2022-09-20
### Added
- Tribe "Currency" custom field can now be used when mapping form fields.

### Fixed
- Do not display errors and properly handle situations when no Tribe account was selected but the form still saved and reloaded.

### Changed
- Minimum WPForms version is now 1.7.5.

## [1.1.0] - 2022-05-26
### IMPORTANT
- Support for WordPress 5.1 has been discontinued. If you are running WordPress 5.1, you MUST upgrade WordPress before installing the new WPForms Tribe. Failure to do that will disable the new WPForms Tribe functionality.

### Added
- Compatibility with WPForms 1.6.7 and the updated Form Builder in 1.6.8.

### Changed
- Minimum WPForms version supported is 1.7.3.

### Fixed
- Compatibility with WordPress Multisite installations.
- Properly handle the situation when trying to change the template for the same form multiple times.
- Send to Tribe form submission data even when the "Entry storage" option is disabled in the Form Builder.
- Various typos in the translatable strings reported by translators.

## [1.0.3] - 2020-12-24
### Fixed
- Incorrectly formatted mapped entry data values before sending them to Tribe (`Cannot deserialize instance of *field_type*` error).

## [1.0.2] - 2020-12-10
### Fixed
- Access token not always refreshing when expired.
- Entries not sending to Tribe in some edge cases.

## [1.0.1] - 2020-09-30
### Fixed
- Incorrect plugin build.

## [1.0.0] - 2020-09-24
- Initial release.
