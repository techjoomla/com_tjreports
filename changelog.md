# Changelog

#### Legend

- Bug Fix (-)
- Feature Addition (+)
- Improvement (^)

---

## com_tjreports v1.1.8

### ^ Improvements:

- #173952 Adding default values to the Tables for Joomla 4

## com_tjreports v1.1.6

### ^ Improvements:

- #172689 Allow overriding frontend report view's JS


## com_tjreports v1.1.5

### + Features Additions:

- #156044 Bulk Email sending
- #155499 Add a function in tjreports to get the reports that support google data studio connector
- #153710 Show summary report for Feedback

### ^ Improvements:

- #168311 Remove duplicates Language constant from language files.
- #160788 Added index in #__tjreports_com_users_user table
- #148927 Add reports API plugin in TJReport package

### - Bug Fixes:

- #169471 Reports > In report data not showing count with link, it's showing href string
- #169429 Reports > Frontend > Reports > Radio button and Export CSV alignment messed up
- #169428 Reports > Frontend > Reports > Language constant missing
- #169363 CSV export not working when report name has special characters
- #169215 Reports > Reports having dynamic column shows different data with / without loading params
- #165179 Search tools on Reports not working on mobile devices
- #165071 Fix height for column level filter in report
- #164411 Getting Unknown column 'Array' in 'field list' error
- #164232 Single quotes and double quotes are getting removed
- #163952 Users com_fields are saving by converting some characters to HTML entities instead of the original value
- #163509 After Block the user from users list then 'user field' not showing in the user reports
- #160788 fix: TjReports Performance Issue in reports model as it fetches all the record from the database without pagination/limit
- #180 Added missing language constants for column wise sorting
- #176 Compatible date field filter for any date format and modified in query
- #172 Notice: Undefined index in reports model & reports default view
