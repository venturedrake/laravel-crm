<?php

namespace VentureDrake\LaravelCrm;

use Dcblogdev\Xero\Models\XeroToken;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Console\LaravelCrmAddressTypes;
use VentureDrake\LaravelCrm\Console\LaravelCrmInstall;
use VentureDrake\LaravelCrm\Console\LaravelCrmLabels;
use VentureDrake\LaravelCrm\Console\LaravelCrmOrganisationTypes;
use VentureDrake\LaravelCrm\Console\LaravelCrmPermissions;
use VentureDrake\LaravelCrm\Console\LaravelCrmReminders;
use VentureDrake\LaravelCrm\Console\LaravelCrmUpdate;
use VentureDrake\LaravelCrm\Console\LaravelCrmXero;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveCall;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveFile;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveLunch;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveMeeting;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveNote;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveTask;
use VentureDrake\LaravelCrm\Http\Livewire\Integrations\Xero\XeroConnect;
use VentureDrake\LaravelCrm\Http\Livewire\LiveActivities;
use VentureDrake\LaravelCrm\Http\Livewire\LiveActivityMenu;
use VentureDrake\LaravelCrm\Http\Livewire\LiveAddressEdit;
use VentureDrake\LaravelCrm\Http\Livewire\LiveCalls;
use VentureDrake\LaravelCrm\Http\Livewire\LiveDealForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveDeliveryItems;
use VentureDrake\LaravelCrm\Http\Livewire\LiveEmailEdit;
use VentureDrake\LaravelCrm\Http\Livewire\LiveFiles;
use VentureDrake\LaravelCrm\Http\Livewire\LiveInvoiceLines;
use VentureDrake\LaravelCrm\Http\Livewire\LiveLeadForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveLunches;
use VentureDrake\LaravelCrm\Http\Livewire\LiveMeetings;
use VentureDrake\LaravelCrm\Http\Livewire\LiveNotes;
use VentureDrake\LaravelCrm\Http\Livewire\LiveOrderForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveOrderItems;
use VentureDrake\LaravelCrm\Http\Livewire\LivePhoneEdit;
use VentureDrake\LaravelCrm\Http\Livewire\LiveProductForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveQuoteForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveQuoteItems;
use VentureDrake\LaravelCrm\Http\Livewire\LiveRelatedContactOrganisation;
use VentureDrake\LaravelCrm\Http\Livewire\LiveRelatedContactPerson;
use VentureDrake\LaravelCrm\Http\Livewire\LiveRelatedPerson;
use VentureDrake\LaravelCrm\Http\Livewire\LiveTasks;
use VentureDrake\LaravelCrm\Http\Livewire\NotifyToast;
use VentureDrake\LaravelCrm\Http\Livewire\PayInvoice;
use VentureDrake\LaravelCrm\Http\Livewire\SendInvoice;
use VentureDrake\LaravelCrm\Http\Livewire\SendQuote;
use VentureDrake\LaravelCrm\Http\Middleware\Authenticate;
use VentureDrake\LaravelCrm\Http\Middleware\FormComponentsConfig;
use VentureDrake\LaravelCrm\Http\Middleware\HasCrmAccess;
use VentureDrake\LaravelCrm\Http\Middleware\LastOnlineAt;
use VentureDrake\LaravelCrm\Http\Middleware\LogUsage;
use VentureDrake\LaravelCrm\Http\Middleware\RouteSubdomain;
use VentureDrake\LaravelCrm\Http\Middleware\Settings;
use VentureDrake\LaravelCrm\Http\Middleware\SystemCheck;
use VentureDrake\LaravelCrm\Http\Middleware\TeamsPermission;
use VentureDrake\LaravelCrm\Http\Middleware\XeroTenant;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldValue;
use VentureDrake\LaravelCrm\Models\File;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\InvoiceLine;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\LeadSource;
use VentureDrake\LaravelCrm\Models\Lunch;
use VentureDrake\LaravelCrm\Models\Meeting;
use VentureDrake\LaravelCrm\Models\Note;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\OrderProduct;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrm\Models\XeroInvoice;
use VentureDrake\LaravelCrm\Models\XeroItem;
use VentureDrake\LaravelCrm\Models\XeroPerson;
use VentureDrake\LaravelCrm\Observers\ActivityObserver;
use VentureDrake\LaravelCrm\Observers\CallObserver;
use VentureDrake\LaravelCrm\Observers\ClientObserver;
use VentureDrake\LaravelCrm\Observers\ContactObserver;
use VentureDrake\LaravelCrm\Observers\DealObserver;
use VentureDrake\LaravelCrm\Observers\DeliveryObserver;
use VentureDrake\LaravelCrm\Observers\DeliveryProductObserver;
use VentureDrake\LaravelCrm\Observers\EmailObserver;
use VentureDrake\LaravelCrm\Observers\FieldGroupObserver;
use VentureDrake\LaravelCrm\Observers\FieldModelObserver;
use VentureDrake\LaravelCrm\Observers\FieldObserver;
use VentureDrake\LaravelCrm\Observers\FieldValueObserver;
use VentureDrake\LaravelCrm\Observers\FileObserver;
use VentureDrake\LaravelCrm\Observers\InvoiceLineObserver;
use VentureDrake\LaravelCrm\Observers\InvoiceObserver;
use VentureDrake\LaravelCrm\Observers\LeadObserver;
use VentureDrake\LaravelCrm\Observers\LeadSourceObserver;
use VentureDrake\LaravelCrm\Observers\LunchObserver;
use VentureDrake\LaravelCrm\Observers\MeetingObserver;
use VentureDrake\LaravelCrm\Observers\NoteObserver;
use VentureDrake\LaravelCrm\Observers\OrderObserver;
use VentureDrake\LaravelCrm\Observers\OrderProductObserver;
use VentureDrake\LaravelCrm\Observers\OrganisationObserver;
use VentureDrake\LaravelCrm\Observers\PersonObserver;
use VentureDrake\LaravelCrm\Observers\PhoneObserver;
use VentureDrake\LaravelCrm\Observers\ProductObserver;
use VentureDrake\LaravelCrm\Observers\ProductPriceObserver;
use VentureDrake\LaravelCrm\Observers\QuoteObserver;
use VentureDrake\LaravelCrm\Observers\QuoteProductObserver;
use VentureDrake\LaravelCrm\Observers\SettingObserver;
use VentureDrake\LaravelCrm\Observers\TaskObserver;
use VentureDrake\LaravelCrm\Observers\TeamObserver;
use VentureDrake\LaravelCrm\Observers\UserObserver;
use VentureDrake\LaravelCrm\Observers\XeroContactObserver;
use VentureDrake\LaravelCrm\Observers\XeroInvoiceObserver;
use VentureDrake\LaravelCrm\Observers\XeroItemObserver;
use VentureDrake\LaravelCrm\Observers\XeroPersonObserver;
use VentureDrake\LaravelCrm\Observers\XeroTokenObserver;

class LaravelCrmServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\User' => \VentureDrake\LaravelCrm\Policies\UserPolicy::class,
        'App\Models\User' => \VentureDrake\LaravelCrm\Policies\UserPolicy::class,
        'VentureDrake\LaravelCrm\Models\Team' => \VentureDrake\LaravelCrm\Policies\TeamPolicy::class,
        'VentureDrake\LaravelCrm\Models\Setting' => \VentureDrake\LaravelCrm\Policies\SettingPolicy::class,
        'VentureDrake\LaravelCrm\Models\Role' => \VentureDrake\LaravelCrm\Policies\RolePolicy::class,
        'VentureDrake\LaravelCrm\Models\Permission' => \VentureDrake\LaravelCrm\Policies\PermissionPolicy::class,
        'VentureDrake\LaravelCrm\Models\Lead' => \VentureDrake\LaravelCrm\Policies\LeadPolicy::class,
        'VentureDrake\LaravelCrm\Models\Deal' => \VentureDrake\LaravelCrm\Policies\DealPolicy::class,
        'VentureDrake\LaravelCrm\Models\Quote' => \VentureDrake\LaravelCrm\Policies\QuotePolicy::class,
        'VentureDrake\LaravelCrm\Models\Order' => \VentureDrake\LaravelCrm\Policies\OrderPolicy::class,
        'VentureDrake\LaravelCrm\Models\Invoice' => \VentureDrake\LaravelCrm\Policies\InvoicePolicy::class,
        'VentureDrake\LaravelCrm\Models\Client' => \VentureDrake\LaravelCrm\Policies\ClientPolicy::class,
        'VentureDrake\LaravelCrm\Models\Person' => \VentureDrake\LaravelCrm\Policies\PersonPolicy::class,
        'VentureDrake\LaravelCrm\Models\Organisation' => \VentureDrake\LaravelCrm\Policies\OrganisationPolicy::class,
        'VentureDrake\LaravelCrm\Models\Contact' => \VentureDrake\LaravelCrm\Policies\ContactPolicy::class,
        'VentureDrake\LaravelCrm\Models\Product' => \VentureDrake\LaravelCrm\Policies\ProductPolicy::class,
        'VentureDrake\LaravelCrm\Models\ProductCategory' => \VentureDrake\LaravelCrm\Policies\ProductCategoryPolicy::class,
        'VentureDrake\LaravelCrm\Models\TaxRate' => \VentureDrake\LaravelCrm\Policies\TaxRatePolicy::class,
        'VentureDrake\LaravelCrm\Models\Label' => \VentureDrake\LaravelCrm\Policies\LabelPolicy::class,
        'VentureDrake\LaravelCrm\Models\Task' => \VentureDrake\LaravelCrm\Policies\TaskPolicy::class,
        'VentureDrake\LaravelCrm\Models\Note' => \VentureDrake\LaravelCrm\Policies\NotePolicy::class,
        'VentureDrake\LaravelCrm\Models\Call' => \VentureDrake\LaravelCrm\Policies\CallPolicy::class,
        'VentureDrake\LaravelCrm\Models\Meeting' => \VentureDrake\LaravelCrm\Policies\MeetingPolicy::class,
        'VentureDrake\LaravelCrm\Models\Lunch' => \VentureDrake\LaravelCrm\Policies\LunchPolicy::class,
        'VentureDrake\LaravelCrm\Models\File' => \VentureDrake\LaravelCrm\Policies\FilePolicy::class,
        'VentureDrake\LaravelCrm\Models\Field' => \VentureDrake\LaravelCrm\Policies\FieldPolicy::class,
        'VentureDrake\LaravelCrm\Models\FieldGroup' => \VentureDrake\LaravelCrm\Policies\FieldGroupPolicy::class,
        'VentureDrake\LaravelCrm\Models\Delivery' => \VentureDrake\LaravelCrm\Policies\DeliveryPolicy::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot(Router $router, Filesystem $filesystem)
    {
        Paginator::useBootstrap();

        if ((app()->version() >= 8 && class_exists('App\Models\User')) || (class_exists('App\Models\User') && ! class_exists('App\User'))) {
            class_alias(config("auth.providers.users.model"), 'App\User');
            if (class_exists('App\Models\Team')) {
                class_alias('App\Models\Team', 'App\Team');
            }
        }

        $this->registerPolicies();

        /*
         * Optional methods to load your package assets
         */
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-crm');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-crm');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Middleware
        $router->aliasMiddleware('auth.laravel-crm', Authenticate::class);

        if (config('laravel-crm.teams')) {
            $router->pushMiddlewareToGroup('web', TeamsPermission::class);
            $router->pushMiddlewareToGroup('crm-api', TeamsPermission::class);
            $router->pushMiddlewareToGroup('web', XeroTenant::class);
            $router->pushMiddlewareToGroup('crm-api', XeroTenant::class);
        }

        if(config('laravel-crm.route_subdomain')) {
            $router->pushMiddlewareToGroup('crm', RouteSubdomain::class);
        }

        $router->pushMiddlewareToGroup('crm', Settings::class);
        $router->pushMiddlewareToGroup('crm-api', Settings::class);
        $router->pushMiddlewareToGroup('crm', HasCrmAccess::class);
        $router->pushMiddlewareToGroup('crm-api', HasCrmAccess::class);
        $router->pushMiddlewareToGroup('crm', LastOnlineAt::class);
        $router->pushMiddlewareToGroup('crm', SystemCheck::class);
        $router->pushMiddlewareToGroup('crm', LogUsage::class);
        $router->pushMiddlewareToGroup('crm-api', LogUsage::class);
        $router->pushMiddlewareToGroup('crm', FormComponentsConfig::class);
        $router->pushMiddlewareToGroup('web', FormComponentsConfig::class);

        $this->registerRoutes();

        // Register Observers
        Lead::observe(LeadObserver::class);
        LeadSource::observe(LeadSourceObserver::class);
        Deal::observe(DealObserver::class);
        Quote::observe(QuoteObserver::class);
        QuoteProduct::observe(QuoteProductObserver::class);
        Order::observe(OrderObserver::class);
        OrderProduct::observe(OrderProductObserver::class);
        Invoice::observe(InvoiceObserver::class);
        InvoiceLine::observe(InvoiceLineObserver::class);
        Client::observe(ClientObserver::class);
        Person::observe(PersonObserver::class);
        Organisation::observe(OrganisationObserver::class);
        Phone::observe(PhoneObserver::class);
        Email::observe(EmailObserver::class);
        Product::observe(ProductObserver::class);
        ProductPrice::observe(ProductPriceObserver::class);
        Setting::observe(SettingObserver::class);
        Note::observe(NoteObserver::class);
        File::observe(FileObserver::class);
        Contact::observe(ContactObserver::class);
        XeroItem::observe(XeroItemObserver::class);
        XeroContact::observe(XeroContactObserver::class);
        XeroPerson::observe(XeroPersonObserver::class);
        XeroInvoice::observe(XeroInvoiceObserver::class);
        Task::observe(TaskObserver::class);
        Activity::observe(ActivityObserver::class);
        XeroToken::observe(XeroTokenObserver::class);
        Call::observe(CallObserver::class);
        Meeting::observe(MeetingObserver::class);
        Lunch::observe(LunchObserver::class);
        Field::observe(FieldObserver::class);
        FieldGroup::observe(FieldGroupObserver::class);
        FieldModel::observe(FieldModelObserver::class);
        FieldValue::observe(FieldValueObserver::class);
        Delivery::observe(DeliveryObserver::class);
        DeliveryProduct::observe(DeliveryProductObserver::class);

        if (class_exists('App\Models\User')) {
            \App\Models\User::observe(UserObserver::class);
        } else {
            \App\User::observe(UserObserver::class);
        }

        if (class_exists('App\Models\Team')) {
            \App\Models\Team::observe(TeamObserver::class);
        } elseif (class_exists('App\Team')) {
            \App\Team::observe(TeamObserver::class);
        }

        // Paginate on Collection
        if (! Collection::hasMacro('paginate')) {
            Collection::macro(
                'paginate',
                function ($perPage = 30, $page = null, $options = []) {
                    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

                    return (new LengthAwarePaginator(
                        $this->forPage($page, $perPage),
                        $this->count(),
                        $perPage,
                        $page,
                        $options
                    ))
                        ->withPath('');
                }
            );
        }

        if ($this->app->runningInConsole()) {
            if (app()->version() >= 8.6) {
                $auditConfig = '/../config/audit-sanctum.php';
            } else {
                $auditConfig = '/../config/audit.php';
            }

            $this->publishes([
                __DIR__ . '/../config/laravel-crm.php' => config_path('laravel-crm.php'),
                __DIR__ . '/../config/permission.php' => config_path('permission.php'),
                __DIR__ . $auditConfig => config_path('audit.php'),
                __DIR__ . '/../config/columnsortable.php' => config_path('columnsortable.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crm'),
            ], 'views');

            // Publishing assets.
            $this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-crm'),
            ], 'assets');

            // Publishing the translation files.
            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-crm'),
            ], 'lang');

            // Publishing the migrations.
            $this->publishes([
                __DIR__ . '/../database/migrations/create_permission_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_permission_tables.php', 1), // Spatie Permissions
                __DIR__ . '/../database/migrations/add_teams_fields.php.stub' => $this->getMigrationFileName($filesystem, 'add_teams_fields.php', 2), // Spatie Permissions
                __DIR__ . '/../database/migrations/create_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tables.php', 3),
                __DIR__ . '/../database/migrations/create_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_settings_table.php', 4),
                __DIR__ . '/../database/migrations/add_fields_to_roles_permissions_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_fields_to_roles_permissions_tables.php', 5),
                __DIR__ . '/../database/migrations/add_label_editable_fields_to_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_label_editable_fields_to_laravel_crm_settings_table.php', 6),
                __DIR__ . '/../database/migrations/add_team_id_to_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_team_id_to_laravel_crm_tables.php', 7),
                __DIR__ . '/../database/migrations/create_laravel_crm_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_products_table.php', 8),
                __DIR__ . '/../database/migrations/create_laravel_crm_product_categories_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_product_categories_table.php', 9),
                __DIR__ . '/../database/migrations/create_laravel_crm_product_prices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_product_prices_table.php', 10),
                __DIR__ . '/../database/migrations/create_laravel_crm_product_variations_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_product_variations_table.php', 11),
                __DIR__ . '/../database/migrations/create_laravel_crm_deal_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_deal_products_table.php', 12),
                __DIR__ . '/../database/migrations/add_global_to_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_global_to_laravel_crm_settings_table.php', 13),
                __DIR__ . '/../database/migrations/alter_fields_for_encryption_on_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'alter_fields_for_encryption_on_laravel_crm_tables.php', 14),
                __DIR__ . '/../database/migrations/create_laravel_crm_address_types_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_address_types_table.php', 15),
                __DIR__ . '/../database/migrations/alter_type_on_laravel_crm_phones_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_type_on_laravel_crm_phones_table.php', 16),
                __DIR__ . '/../database/migrations/add_description_to_laravel_crm_labels_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_description_to_laravel_crm_labels_table.php', 17),
                __DIR__ . '/../database/migrations/add_name_to_laravel_crm_addresses_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_name_to_laravel_crm_addresses_table.php', 18),
                __DIR__ . '/../database/migrations/create_laravel_crm_contacts_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_contacts_table.php', 19),
                __DIR__ . '/../database/migrations/create_laravel_crm_contact_types_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_contact_types_table.php', 20),
                __DIR__ . '/../database/migrations/create_laravel_crm_contact_contact_type_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_contact_contact_type_table.php', 21),
                __DIR__ . '/../database/migrations/create_audits_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_audits_table.php', 22), // Laravel auditing
                __DIR__ . '/../database/migrations/create_devices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_devices_table.php', 23), // Laravel Auth Checker
                __DIR__ . '/../database/migrations/create_logins_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_logins_table.php', 24), // Laravel Auth Checker
                __DIR__ . '/../database/migrations/update_logins_and_devices_table_user_relation.php.stub' => $this->getMigrationFileName($filesystem, 'update_logins_and_devices_table_user_relation.php', 25), // Laravel Auth Checker
                __DIR__ . '/../database/migrations/create_laravel_crm_organisation_types_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_organisation_types_table.php', 26),
                __DIR__ . '/../database/migrations/change_morph_col_names_on_laravel_crm_notes_table.php.stub' => $this->getMigrationFileName($filesystem, 'change_morph_col_names_on_laravel_crm_notes_table.php', 27),
                __DIR__ . '/../database/migrations/add_related_note_to_laravel_crm_notes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_related_note_to_laravel_crm_notes_table.php', 28),
                __DIR__ . '/../database/migrations/add_noted_at_to_laravel_crm_notes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_noted_at_to_laravel_crm_notes_table.php', 29),
                __DIR__ . '/../database/migrations/create_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_quotes_table.php', 30),
                __DIR__ . '/../database/migrations/create_laravel_crm_quote_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_quote_products_table.php', 31),
                __DIR__ . '/../database/migrations/create_laravel_crm_files_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_files_table.php', 32),
                __DIR__ . '/../database/migrations/add_mime_to_laravel_crm_files_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_mime_to_laravel_crm_files_table.php', 33),
                __DIR__ . '/../database/migrations/create_xero_tokens_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_xero_tokens_table.php', 34),
                __DIR__ . '/../database/migrations/create_laravel_crm_xero_items_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_items_table.php', 35),
                __DIR__ . '/../database/migrations/create_laravel_crm_xero_contacts_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_contacts_table.php', 36),
                __DIR__ . '/../database/migrations/create_laravel_crm_xero_people_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_people_table.php', 37),
                __DIR__ . '/../database/migrations/add_reference_to_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_reference_to_laravel_crm_quotes_table.php', 38),
                __DIR__ . '/../database/migrations/create_laravel_crm_tasks_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tasks_table.php', 39),
                __DIR__ . '/../database/migrations/add_deleted_at_to_laravel_crm_activities_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_deleted_at_to_laravel_crm_activities_table.php', 40),
                __DIR__ . '/../database/migrations/create_laravel_crm_timezones_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_timezones_table.php', 41),
                __DIR__ . '/../database/migrations/add_team_id_to_xero_tokens_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_team_id_to_xero_tokens_table.php', 42),
                __DIR__ . '/../database/migrations/create_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_orders_table.php', 43),
                __DIR__ . '/../database/migrations/create_laravel_crm_order_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_order_products_table.php', 44),
                __DIR__ . '/../database/migrations/create_laravel_crm_invoices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_invoices_table.php', 45),
                __DIR__ . '/../database/migrations/create_laravel_crm_invoice_lines_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_invoice_lines_table.php', 46),
                __DIR__ . '/../database/migrations/add_reference_to_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_reference_to_laravel_crm_orders_table.php', 47),
                __DIR__ . '/../database/migrations/create_laravel_crm_calls_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_calls_table.php', 48),
                __DIR__ . '/../database/migrations/create_laravel_crm_meetings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_meetings_table.php', 49),
                __DIR__ . '/../database/migrations/create_laravel_crm_lunches_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_lunches_table.php', 50),
                __DIR__ . '/../database/migrations/add_location_to_laravel_crm_activities_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_location_to_laravel_crm_activities_table.php', 51),
                __DIR__ . '/../database/migrations/add_prefix_to_laravel_crm_invoices_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_invoices_table.php', 52),
                __DIR__ . '/../database/migrations/create_laravel_crm_usage_requests_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_usage_requests_table.php', 53),
                __DIR__ . '/../database/migrations/add_label_type_to_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_label_type_to_laravel_crm_fields_table.php', 54),
                __DIR__ . '/../database/migrations/create_laravel_crm_field_models_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_field_models_table.php', 55),
                __DIR__ . '/../database/migrations/create_laravel_crm_field_groups_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_field_groups_table.php', 56),
                __DIR__ . '/../database/migrations/add_team_id_to_laravel_crm_usage_requests_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_team_id_to_laravel_crm_usage_requests_table.php', 57),
                __DIR__ . '/../database/migrations/alter_field_group_id_on_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_field_group_id_on_laravel_crm_fields_table.php', 58),
                __DIR__ . '/../database/migrations/add_system_to_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_system_to_laravel_crm_fields_table.php', 59),
                __DIR__ . '/../database/migrations/add_comments_to_laravel_crm_quote_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_comments_to_laravel_crm_quote_products_table.php', 60),
                __DIR__ . '/../database/migrations/add_comments_to_laravel_crm_order_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_comments_to_laravel_crm_order_products_table.php', 61),
                __DIR__ . '/../database/migrations/create_laravel_crm_deliveries_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_deliveries_table.php', 62),
                __DIR__ . '/../database/migrations/create_laravel_crm_delivery_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_delivery_products_table.php', 63),
                __DIR__ . '/../database/migrations/alter_url_on_laravel_crm_usage_requests_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_url_on_laravel_crm_usage_requests_table.php', 64),
                __DIR__ . '/../database/migrations/create_laravel_crm_clients_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_clients_table.php', 65),
                __DIR__ . '/../database/migrations/create_laravel_crm_xero_invoices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_invoices_table.php', 66),
                __DIR__ . '/../database/migrations/add_contact_to_laravel_crm_addresses_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_contact_to_laravel_crm_addresses_table.php', 67),
                __DIR__ . '/../database/migrations/add_phone_to_laravel_crm_addresses_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_phone_to_laravel_crm_addresses_table.php', 68),
                __DIR__ . '/../database/migrations/add_name_to_laravel_crm_clients_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_name_to_laravel_crm_clients_table.php', 69),
                __DIR__ . '/../database/migrations/add_delivery_dates_to_laravel_crm_deliveries_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_delivery_dates_to_laravel_crm_deliveries_table.php', 70),
                __DIR__ . '/../database/migrations/add_client_to_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_orders_table.php', 71),
                __DIR__ . '/../database/migrations/add_client_to_laravel_crm_leads_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_leads_table.php', 72),
                __DIR__ . '/../database/migrations/add_client_to_laravel_crm_deals_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_deals_table.php', 73),
                __DIR__ . '/../database/migrations/add_client_to_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_quotes_table.php', 74),
                __DIR__ . '/../database/migrations/add_account_codes_to_laravel_crm_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_account_codes_to_laravel_crm_products_table.php', 75),
                __DIR__ . '/../database/migrations/add_prefix_to_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_quotes_table.php', 76),
                __DIR__ . '/../database/migrations/add_prefix_to_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_orders_table.php', 77),
                __DIR__ . '/../database/migrations/add_quote_product_id_to_laravel_crm_order_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_quote_product_id_to_laravel_crm_order_products_table.php', 78),
                __DIR__ . '/../database/migrations/add_quantity_to_laravel_crm_delivery_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_quantity_to_laravel_crm_delivery_products_table.php', 79),
                __DIR__ . '/../database/migrations/create_laravel_crm_tax_rates_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tax_rates_table.php', 80),
                __DIR__ . '/../database/migrations/add_order_product_id_to_laravel_crm_invoice_lines_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_order_product_id_to_laravel_crm_invoice_lines_table.php', 81),
                __DIR__ . '/../database/migrations/add_prefix_to_laravel_crm_deliveries_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_deliveries_table.php', 82),
                __DIR__ . '/../database/migrations/alter_value_on_laravel_crm_field_values_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_value_on_laravel_crm_field_values_table.php', 83),
            ], 'migrations');

            // Publishing the seeders
            if (! class_exists('LaravelCrmTablesSeeder')) {
                if (app()->version() >= 8) {
                    $this->publishes([
                        __DIR__ . '/../database/seeders/LaravelCrmTablesSeeder.php' => database_path(
                            'seeders/LaravelCrmTablesSeeder.php'
                        ),
                    ], 'seeders');
                } else {
                    $this->publishes([
                        __DIR__ . '/../database/seeders/LaravelCrmTablesSeeder.php' => database_path(
                            'seeds/LaravelCrmTablesSeeder.php'
                        ),
                    ], 'seeders');
                }
            }

            // Registering package commands.
            $this->commands([
                LaravelCrmInstall::class,
                LaravelCrmUpdate::class,
                LaravelCrmPermissions::class,
                LaravelCrmLabels::class,
                LaravelCrmAddressTypes::class,
                LaravelCrmOrganisationTypes::class,
                LaravelCrmXero::class,
                LaravelCrmReminders::class
            ]);

            // Register the model factories
            if (app()->version() < 8) {
                $this->app->make('Illuminate\Database\Eloquent\Factory')
                     ->load(__DIR__.'/../database/factories');
            }
        }

        // Livewire components
        Livewire::component('phone-edit', LivePhoneEdit::class);
        Livewire::component('email-edit', LiveEmailEdit::class);
        Livewire::component('address-edit', LiveAddressEdit::class);
        Livewire::component('notes', LiveNotes::class);
        Livewire::component('note', LiveNote::class);
        Livewire::component('tasks', LiveTasks::class);
        Livewire::component('task', LiveTask::class);
        Livewire::component('calls', LiveCalls::class);
        Livewire::component('call', LiveCall::class);
        Livewire::component('meetings', LiveMeetings::class);
        Livewire::component('meeting', LiveMeeting::class);
        Livewire::component('lunches', LiveLunches::class);
        Livewire::component('lunch', LiveLunch::class);
        Livewire::component('files', LiveFiles::class);
        Livewire::component('file', LiveFile::class);
        Livewire::component('related-contact-organisations', LiveRelatedContactOrganisation::class);
        Livewire::component('related-contact-people', LiveRelatedContactPerson::class);
        Livewire::component('related-people', LiveRelatedPerson::class);
        Livewire::component('live-lead-form', LiveLeadForm::class);
        Livewire::component('deal-form', LiveDealForm::class);
        Livewire::component('quote-form', LiveQuoteForm::class);
        Livewire::component('notify-toast', NotifyToast::class);
        Livewire::component('quote-items', LiveQuoteItems::class);
        Livewire::component('order-form', LiveOrderForm::class);
        Livewire::component('order-items', LiveOrderItems::class);
        Livewire::component('delivery-items', LiveDeliveryItems::class);
        Livewire::component('activity-menu', LiveActivityMenu::class);
        Livewire::component('xero-connect', XeroConnect::class);
        Livewire::component('activities', LiveActivities::class);
        Livewire::component('send-quote', SendQuote::class);
        Livewire::component('invoice-lines', LiveInvoiceLines::class);
        Livewire::component('send-invoice', SendInvoice::class);
        Livewire::component('pay-invoice', PayInvoice::class);
        Livewire::component('product-form', LiveProductForm::class);

        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);

                $schedule->command('laravelcrm:reminders')
                    ->name('laravelCrmReminders')
                    ->everyMinute()
                    ->withoutOverlapping();

                if (config('xero.clientId') && config('xero.clientSecret')) {
                    $schedule->command('xero:keep-alive')
                        ->name('laravelCrmXeroKeepAlive')
                        ->everyFiveMinutes();
                    $schedule->command('laravelcrm:xero contacts')
                        ->name('laravelCrmXeroContacts')
                        ->everyTenMinutes()
                        ->withoutOverlapping();
                    $schedule->command('laravelcrm:xero products')
                        ->name('laravelCrmXeroProducts')
                        ->everyTenMinutes()
                        ->withoutOverlapping();
                }
            });
        }

        // This was causing composer install post dump autoload to fail when no DB connected
        if (! $this->app->runningInConsole()) {
            if (Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
                view()->share('dateFormat', Setting::where('name', 'date_format')->first()->value ?? 'Y/m/d');
                view()->share('timeFormat', Setting::where('name', 'time_format')->first()->value ?? 'H:i');
                view()->share('timezone', Setting::where('name', 'timezone')->first()->value ?? 'UTC');
                view()->share('taxName', Setting::where('name', 'tax_name')->first()->value ?? 'Tax');

                if($setting = Setting::where('name', 'dynamic_products')->first()) {
                    if($setting->value == 1) {
                        view()->share('dynamicProducts', 'true');
                    } else {
                        view()->share('dynamicProducts', 'false');
                    }
                } else {
                    view()->share('dynamicProducts', 'true');
                }
            } else {
                view()->share('dateFormat', 'Y/m/d');
                view()->share('timeFormat', 'H:i');
                view()->share('timezone', 'UTC');
                view()->share('taxName', 'Tax');
            }
        }

        Blade::if('hasleadsenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('leads', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasdealsenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('deals', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasquotesenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('quotes', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasordersenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('orders', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasinvoicesenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('invoices', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasdeliveriesenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('deliveries', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasteamsenabled', function () {
            if(is_array(config('laravel-crm.modules')) && in_array('teams', config('laravel-crm.modules'))) {
                return true;
            } elseif(! config('laravel-crm.modules')) {
                return true;
            }
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/package.php', 'laravel-crm');
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-crm.php', 'laravel-crm');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-crm', function () {
            return new LaravelCrm();
        });

        $this->app->register(LaravelCrmEventServiceProvider::class);
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            if (config('laravel-crm.user_interface')) {
                $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
            }
        });
    }

    protected function routeConfiguration()
    {
        if (config('laravel-crm.route_subdomain')) {
            $host = explode(".", request()->getHost());
            if (count($host) == 3) { // .com
                $domain = config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 2)].'.'.end($host);
            } elseif (count($host) == 4) { // .com.au
                $domain = config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 3)].'.'.$host[(count($host) - 2)].'.'.end($host);
            }
        }

        return [
            'domain' => $domain ?? null,
            'prefix' => (config('laravel-crm.route_prefix')) ? config('laravel-crm.route_prefix') : null,
            'middleware' => array_unique(array_merge(['web','crm','crm-api'], config('laravel-crm.route_middleware') ?? [])),
        ];
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem, $filename, $order): string
    {
        $timestamp = date('Y_m_d_His', strtotime("+$order sec"));

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $filename) {
                return $filesystem->glob($path.'*_'.$filename);
            })->push($this->app->databasePath()."/migrations/{$timestamp}_".$filename)
            ->first();
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies() as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        return $this->policies;
    }
}
