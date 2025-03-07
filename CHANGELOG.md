# Yii Session Change Log

## 3.0.1 under development

- no changes in this release.

## 3.0.0 March 04, 2025

- Chg #66: Remove `sid_length` and `sid_bits_per_character` options (@vjik)
- Chg #67: Mark `SessionException` as final (@vjik)
- Chg #69: Change PHP constraint in `composer.json` to `8.0 - 8.4` (@vjik)
- Bug #69: Explicitly mark nullable parameters (@vjik)

## 2.1.0 May 02, 2024

- Chg #37: Raise the minimum version of PHP to 8.0 (@xepozz, @rustamwin)
- Enh #59: Add support for `psr/http-message` version `^2.0` (@vjik)

## 2.0.0 February 13, 2023

- Chg #47: Adapt configuration group names to Yii conventions (@vjik)
- Bug #45: Returns correct session name when used custom name and call `Session::getName()` before session open (@vjik)

## 1.1.0 October 28, 2022

- New #39: Add `NullSession` (@xepozz)

## 1.0.4 February 05, 2022

- Bug #32: Fix not sending cookie when session is closed manually (@vjik)

## 1.0.3 January 28, 2022

- Bug #28: Add missing state resetter config (@rustamwin)

## 1.0.2 April 13, 2021

- Chg: Adjust config for `yiisoft/factory` changes (@vjik, @samdark)

## 1.0.1 March 23, 2021

- Chg: Adjust config for new config plugin (@samdark)

## 1.0.0 December 26, 2020

- Initial release.
