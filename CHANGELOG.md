# Changelog

## [2.6.2] - 2023-06-02

### Added

- API version and number of items for pagination options.
- Pagination compatibility when reading from Sales Layer API.

### Changed

- Modified datetime for auto-sync option label.
- SalesLayer-Conn class version updated to 1.36.
- Minor fixes.

### Tested

- Tested on Magento 2.4.5-p1 / PHP 8.1 

## [2.6.1] - 2023-01-25

### Changed

- Compatibility with MG version 2.4.5-p1 (PHP8).
- Attributes synchronization optimization.

## [2.6.0] - 2022-12-02

### Added

- Option to process product_website y format_website fields.

## [2.5.9] - 2022-11-11

### Changed

- Fix Manage Stock value when quantity is defined.

## [2.5.8] - 2022-10-04

### Changed

- Optimized index names when processing variants as products.

## [2.5.7] - 2022-07-12

### Changed

- Optimized category name assignation process.
- Optimized json unserialize function for empty values.

## [2.5.6] - 2022-07-01

### Changed

- Eliminated direct queries.
- Connection class to process bbdd modifications.
- Code cleaning and minor changes.

## [2.5.5] - 2022-06-22

### Changed

- Code cleaning on most files and delete of unused files.
- Helper class adapted with new debugger class.
- Minor changes.

## [2.5.4] - 2022-06-02

### Changed

- Changed json decode and encode functions through MG class.
- Moved debug messages to a Helper class.
- Minor fixes and code cleaning.

## [2.5.3] - 2022-04-12

### Changed

- PID interactions removed.
- Show table queries modified.
- Optimization for product link synchronization.

## [2.5.2] - 2022-03-28

### Added

- Is Anchor category field to process.