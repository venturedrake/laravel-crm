# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Documents
- Calendar
- Custom Fields
- CSV Import / Export
- SMS
- Payments
- Kanban boards

<!--- ## 1.0.0 - 2023-XX-XX
### Added
### Changed
### Fixed
### Removed --->

## 0.19.10 - 2023-09-25
### Added
- Phone, emails & address to settings and users
- VAT/ABN to settings
- Added setting to disable update notifications
- Address multiple lines helper
### Changed
- No longer adds zero quantity items to deliveries
### Fixed
- Adding new organisation or person bug when creating invoice
- Produce code required to post products to Xero api
### Removed

## 0.19.9 - 2023-09-04
### Fixed
- Database update for tax amounts

## 0.19.8 - 2023-09-04
### Added
- Tax rates and tax amount added to quote products, order products & invoice lines
- Indicate on products whether exist in Xero items
### Fixed
- Bug with create new products setting when creating quotes, orders, invoices
- Tax rate show view
### Removed
- Save/Cancel buttons on product category show view

## 0.19.7 - 2023-09-01
### Changed
- Switched cs from laravel to prs12
### Fixed
- Related contacts bug when deleting peron or organisation
- Bug when allowing null value on custom field
- Fixed bug by disabling create new labels when adding leads, contacts, etc

## 0.19.6 - 2023-08-29
### Fixed
- Error when sending quotes and missing organization name setting
- Error when deleting custom fields and attached models
- Error when showing deliveries when order has been deleted
- Ensure address isset on pdf before displaying
- Error on PDF when person not set

## 0.19.5 - 2023-08-29
### Fixed
- Consider soft deleted models when incrementing numbers

## 0.19.4 - 2023-08-28
### Added
- Delivery number
### Fixed
- Copying billing & shipping address from quote to order
- Copying shipping address from order to delivery

## 0.19.3 - 2023-08-28
### Added
- Show quote orders
- Show order invoices
- Show order deliveries
### Changed
- Update command for order related deliveries

## 0.19.2 - 2023-08-28
### Fixed
- Version 0.19.1 database update check

## 0.19.1 - 2023-08-28
### Changed
- Now using Laravel Pint and Laravel preset for code style
- Create multiple invoices from an order
### Fixed
- Typo on quote show view
### Removed
- Travis config
- cs fixer config

## 0.19.0 - 2023-08-25
### Added
- Update command for updating database
- Make some of the models optional with config setting
- Show related contact activity setting
- Client search
- Using Pint and Laravel preset for code style
- Tax rates setting
- Invoice contact details setting
- Check app is running on correct subdomain setting
- Added setting default config
- Show users with update permissions update alerts
- Send task, call, meeting and lunch reminder emails
### Changed
- Add related contacts to person and use contacts relation
- Update some dependencies for Laravel 10 support
- PDF download filenames updated
- Default invoice, order & quote number set to 1000
- Allowing products to be added during order, quote, invoice create
### Fixed
- Disabled settings no longer throws error
- Bug when no activity
- Support for removed Jetstream personal team
- Invoice title fixed
- Missing product on invoice
- Invoice due badge
- Timezone global view share
- Tax amount on invoice lines
- Validation on phone number & email type
### Removed

## 0.18.1 - 2023-06-04
### Added
- Laravel 10 support

## 0.18.0 - 2023-06-02
### Added
- Show product code on quote, order & invoice lines
- Default sales account for xero integration
- Purchase & sales account codes on products
- Quote prefix setting
- Order prefix setting
- Indicate related invoice order
- Indicate related quote on order
- Split quote into multiple orders
- Split order into multiple deliveries
- Added checks on totals and indicted when errors
### Changed
- Activate select2 when adding quote, order & invoice items
- Improved PDF formatting
### Fixed
- Copy reference to invoice created from order
- Bug with deleting notes & related activity
- Bug with issue & due dates on xero invoices
- Fixed error when creating order without a quote
- Don't show unordered list when zero notes, removes extra padding above tabs

## 0.17.1 - 2023-04-23
### Added
- Date & time format setting
- Option to show specific addresses on orders
### Fixed
- Missing invoice number, issue and due date on PDF
- Missing delivery date on delivery PDF
- Bug with non numeric values in price & quantity on quote, order, invoice items
- Bug when missing address and creating or editing orders
- Bug when settings have no value

## 0.17.0 - 2023-04-12
### Added
- Number formatting on quotes, orders & invoice items
- Add products to xero when adding to crm
- Add reference to xero invoice
- Row delete on quote, order & invoice items
- Added received by on deliveries
- Added delivery contact to delivery pdf
- Added pdf attachment to send quotes email
- Added pdf attachment to send invoices email
- Indicate if a contact is in xero
- Indicate required fields
- Customer on orders
- Added expected and actual delivery dates
- Added customer to leads, deals, quotes & orders
- Create leads, deals, quotes & orders from customers, organisations & people
### Changed
- Load select2 options from data array
- Quote, Orders & Invoice PDF formatting
- Improved title generation on leads & orders
- Client now called customer
- Invoice number not required when using xero integration
### Fixed
- Fixed quote to order error
- Fixed error on pdf when contact person not set
- Fixed organisation name on invoice pdf
- Fixed support for db seeder when using teams
- Fixed bug showing delivery when order is deleted
- Fixed bug on lead form fields

## 0.16.0 - 2023-03-12
### Added
- Menu icons
- User model relations trait
- Client model
- Invoice generation in xero integration
- Billing & shipping addresses on orders
- Shipping address on Deliveries
### Changed
- No text wrapping on responsive tables
- Quote items now using Select2
- Order items now using Select2
- Invoice lines now using Select2
- Improved layout for quote items, order items & invoice lines
### Fixed
- Typo in delivery products migration
- Bug when retrieving related contacts by type
- Bug with decimal missing from product pricing in xero integration
- Layout issues fixed on smaller screens

## 0.15.0 - 2023-02-24
### Added
- Usage request logging
- Custom fields and field groups
- Disable UI setting
- Deliveries
- Quote, Order, Invoice & Delivery PDF downloads
- Default Quote & Invoice Terms setting
### Changed
- Use mail template for outgoing emails
### Fixed
- Validate signed urls for quotes and invoices
- Only run xero schedule commands with integration enabled
- Fixed multi-tenant xero connection
- Increase url fields size on usage requests table

## 0.14.1 - 2023-01-20
### Added
- Laravel Breeze profile section support
### Changed
- Completed the CLI installer

## 0.14.0 - 2023-01-17
### Added
- Invoicing

## 0.13.0 - 2023-01-06
### Added
- Orders
- Calls, meetings and lunches
- Logo and favicon
### Changed
- Merged notes and tasks into activities
### Fixed
- Button background colors
- Zero tasks, notes and orders

## 0.12.2 - 2022-12-10
### Added
- Lead source observer
- Invoices permissions
- Retain filters when searching
- Return to search results
### Changed
- Only run xero scheduled tasks when relevant setting is true
- Moved model filters to a modal for better UX
- Set multi-select max height
### Fixed
- Xero middleware check if auth user before setting tenant id
- Sorting working with encrypted table fields
- Default owner user filter
- Support for browser back button with search

## 0.12.1 - 2022-12-03
### Added
- Aggregated notes
- Support for xero integration multi-tenancy
### Changed
- Quote items in a table
- Disable autofill on noted_at field
### Fixed
- Xero integration when using teams
- Deleting of activity when notes, tasks or files deleted

## 0.12.0 - 2022-11-19
### Added
- Quote builder
- Send quotes
- Accept/Reject quotes
- Tasks
- Files upload
- Xero integration
- Noted at field on notes
- Pin notes
- Toast notifications
- Timezone setting
- Logo setting
### Fixed
 - Support for country domains when using subdomain
 - Issue with spatie permissions when conflicting tables exist
 - Various minor bugs and typos

## 0.11.0 - 2022-09-03
### Added
- Laravel 9 support
- Default settings in config
- Better subdomain support so not to conflict with other routes
- Noted at datetime on notes
### Changed
- Replaced countries package for Laravel 9 support
### Fixed
- Laravel 6 support
- PHP 7 support
- Team settings
- Bug when not using teams support causing issue with permissions and seeder

## 0.10.1 - 2022-03-22
### Fixed
- Issue with middleware affecting access to non-crm API

## 0.10.0 - 2022-03-11
### Added
- Link to owner profile on contacts
- Clear filters button
- Sort functionality on filters where available
- Auto build lead title
### Fixed
- Remove organisation from a person
- Problem when query has joins and using teams 
- https://github.com/venturedrake/laravel-crm/issues/33
- https://github.com/venturedrake/laravel-crm/issues/34

## 0.9.9 - 2021-12-15
### Added
- Show related notes from related contacts
### Fixed
- Notes when using teams

## 0.9.8 - 2021-12-08
### Fixed
- Issue with loading team roles, settings when not using teams mode

## 0.9.7 - 2021-11-27
### Fixed
- Issue with adding owner role when creating new team

## 0.9.6 - 2021-11-24
### Added
- Related organisations and people
- AU & GP language variables

## 0.9.5 - 2021-11-16
### Fixed
- Missing command from service provider

## 0.9.4 - 2021-11-15
### Added
- Ability to add notes to leads, deals & contacts
- Auth logging
- Organisation types
- Search option on multiselect search filters
### Fixed
- Incorrectly names morph fields on notes table
- Typo in lang file
- Formatting of delete button on phone, email and addresses

## 0.9.3 - 2021-11-05
### Changed
- Filters now use post request and stored in session

## 0.9.2 - 2021-11-03
### Fixed
- Address types migration command

## 0.9.1 - 2021-11-03
### Fixed
- Missing command in service provider

## 0.9.0 - 2021-11-02
### Added
- Model audit logging
- Config for spatie permissions
### Fixed
- Address types teams support

## 0.8.1 - 2021-10-28
### Fixed
- Editing roles in teams mode

## 0.8.0 - 2021-10-27
### Added
- Spatie permissions team support
- allTeams scope
### Fixed
- Search filter scope
- Problem with team permissions cache

## 0.7.2 - 2021-10-20
### Added
- Added owner and label browsing filters
### Changed
- Use owner rather than assigned to field
### Fixed
- Issue with address and contact types migrations
- Search leads and deals
- Settings menu active main menu issue
### Removed

## 0.7.1 - 2021-10-08
### Added
- Name field on address
### Fixed
- Issue with copying labels to teams
### Removed
- Don't set a team if not using teams

## 0.7.0 - 2021-09-24
### Added
- Labels admin
- Labels description
- Multiple contact addresses
- Mutliple contact phone numbers
- Mutliple contact emails
- Fax number type
- Select2 for labels

## 0.6.8 - 2021-09-12
### Fixed
- Roles & Permissions team owner role missing

## 0.6.7 - 2021-09-12
### Added
- Roles & Permissions team support

## 0.6.6 - 2021-09-03
### Changed
- Default field level encryption security setting to false
### Fixed
- Issue with table field size when using field level encryption

## 0.6.5 - 2021-08-29
### Fixed
- Issue with user policy when user in models directory

## 0.6.4 - 2021-08-13
### Changed
- Dual listbox for managing crm team users
### Fixed
- Show crm team users only

## 0.6.3 - 2021-07-29
### Fixed
- Issue with publishing migrations

## 0.6.2 - 2021-07-28
### Added
- blade directives to show/hide CRUD buttons
### Fixed
- Missing lang keys
- Bug where converted leads were still showing
- Hide on team users from latest online users dashboard widget

## 0.6.1 - 2021-07-15
### Fixed
- Users in teams

## 0.6.0 - 2021-07-14
### Added
- Support for Jetstream/Spark teams
### Fixed
- Model observers - https://github.com/venturedrake/laravel-crm/issues/29
- Assign role instead of sync - https://github.com/venturedrake/laravel-crm/pull/28

## 0.5.1 - 2021-05-31
### Fixed
- Missing key in lang file

## 0.5.0 - 2021-05-31
### Added
- Language support
### Fixed
- Bug when converting lead to deal

## 0.4.0 - 2021-05-21
### Added
- Products & product categories
- Product & product category permissions
### Fixed
- Issue with editing a role name
- Issue with dashboard chart

 ## 0.3.1 - 2021-05-12
### Added
- Version check on updates route

## 0.3.0 - 2021-05-11
### Added
- Dashboard
- team_id to models
### Removed
- User model class

## 0.2.7 - 2021-04-04
### Fixed
- Conflict with Laravel 8 Jetstream teams route

## 0.2.6 - 2021-04-26
### Fixed
- Conflict with Laravel 8 default routes

## 0.2.5 - 2021-04-25
### Added
- Support for Laravel 8 App\Models\User
- Config file comments and updated readme
### Fixed
- Conflict with default Laravel auth routes

## 0.2.4 - 2021-04-23
### Fixed
- Issue with timestamp on published migrations

## 0.2.3 - 2021-04-23
### Fixed
- Issue with migrations being before spatie permissions

## 0.2.2 - 2021-04-23
### Fixed
 - Typo in readme
 - Issue with order of published migrations

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