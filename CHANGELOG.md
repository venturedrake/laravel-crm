# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Products
- Notes
- Tasks
- Files / Documents
- Calendar (Calls, Meetings, Reminders)
- Dashboard
- Custom Fields
- Activity Feed / Timelines
- CSV Import / Export

<!--- ## 1.0.0 - 2021-XX-XX
### Added
### Changed
### Fixed
### Removed --->

## 0.2.1 - 2021-04-22
### Changed
 - Moved lead, deal, person, organisation, users & team views to partials & components
### Fixed
 - Bug with LeadPolicy
 - Bug with checking user on team

## 0.2.0 - 2021-04-15
### Added
- Roles / Permissions
- Traits HasCrmAccess & HasCrmTeams for App\User model

### Changed
- Contacts created when adding leads
- Use the App\User model by default

### Fixed
- New contact badge
- Role not required when editing user
- Check if settings table exists and create if not
- Version check bug
- btn hover style on table rows
- form group for crm access toggle

### Removed
- VentureDrake\LaravelCrm\Models\User model

## 0.1.6 - 2021-04-07
### Changed

- Updated crm middleware group

## 0.1.5 - 2021-04-01
### Fixed

- Bug with seeder not working after assets published

## 0.1.4 - 2021-04-01
### Changed

- Support for Laravel 7/8
- Livewire support for Laravel 7/8

## 0.1.3 - 2021-04-01
### Removed

- Livewire dependency

## 0.1.2 - 2021-03-19
### Added

- Version checking

## 0.1.1 - 2021-03-19
### Removed

- Disabled seeding sample data

## 0.1.0 - 2021-03-18
### Added

- Leads
- Deals
- People
- Organisations
- Users
- Teams