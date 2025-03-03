# Upgrading Instructions for Yii Session

This file contains the upgrade notes for the Yii Session.
These notes highlight changes that could break your application when you upgrade it from one major version to another.

## 3.0.0

### Remove deprecated options

`sid_length` and `sid_bits_per_character` settings are removed from default session options, because they are marked as
deprecated in PHP 8.4. In PHP default values of these settings are different. So, if you don't set these settings
explicitly, values will change after Yii Session update:

- `sid_length` from `48` to `32`;
- `sid_bits_per_character` from `5` to `4`.

### Other

- `SessionException` is marked as final, so you can't extend it anymore.
