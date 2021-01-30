# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2021-01-31
### Fixed
- Clear the Firefox window handle cache when starting a session

## [1.0.0] - 2021-01-16
### Added
- Initial implementation as a hard fork from https://github.com/minkphp/MinkSelenium2Driver
- Using .editorconfig
- Using GitHub Action instead of Travis CI
- Using Dependabot
- Using own fork of driver-testsuite
- Added Screenshot listener (for unit tests)
- Added LICENCE file
- Prompt support
- Workaround for https://github.com/mozilla/geckodriver/issues/149
- Workaround for https://github.com/mozilla/geckodriver/issues/653
- Workaround for https://github.com/mozilla/geckodriver/issues/1816
- Handling `input[type=time]`, `input[type=date]` and `input[type=color]`

[Unreleased]: https://github.com/oleg-andreyev/MinkPhpWebDriver/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/oleg-andreyev/MinkPhpWebDriver/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/oleg-andreyev/MinkPhpWebDriver/compare/07b0f6be5c4ec82b041b62b99bd48786a4373ad0...v1.0.0

