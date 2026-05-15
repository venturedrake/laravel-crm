<?php

namespace VentureDrake\LaravelCrm;

use App\Team;
use App\User;
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
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Console\LaravelCrmAddressTypes;
use VentureDrake\LaravelCrm\Console\LaravelCrmAddUser;
use VentureDrake\LaravelCrm\Console\LaravelCrmArchive;
use VentureDrake\LaravelCrm\Console\LaravelCrmContactTypes;
use VentureDrake\LaravelCrm\Console\LaravelCrmDecrypt;
use VentureDrake\LaravelCrm\Console\LaravelCrmEmailCampaignsDispatch;
use VentureDrake\LaravelCrm\Console\LaravelCrmEncrypt;
use VentureDrake\LaravelCrm\Console\LaravelCrmFields;
use VentureDrake\LaravelCrm\Console\LaravelCrmInstall;
use VentureDrake\LaravelCrm\Console\LaravelCrmLabels;
use VentureDrake\LaravelCrm\Console\LaravelCrmLeadSources;
use VentureDrake\LaravelCrm\Console\LaravelCrmOrganizationTypes;
use VentureDrake\LaravelCrm\Console\LaravelCrmPermissions;
use VentureDrake\LaravelCrm\Console\LaravelCrmReminders;
use VentureDrake\LaravelCrm\Console\LaravelCrmSampleData;
use VentureDrake\LaravelCrm\Console\LaravelCrmSmsCampaignsDispatch;
use VentureDrake\LaravelCrm\Console\LaravelCrmUpdate;
use VentureDrake\LaravelCrm\Console\LaravelCrmV2;
use VentureDrake\LaravelCrm\Console\LaravelCrmXero;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveCall;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveFile;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveLunch;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveMeeting;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveNote;
use VentureDrake\LaravelCrm\Http\Livewire\Components\LiveTask;
use VentureDrake\LaravelCrm\Http\Livewire\Fields\CreateOrEdit;
use VentureDrake\LaravelCrm\Http\Livewire\Integrations\Xero\XeroConnect;
use VentureDrake\LaravelCrm\Http\Livewire\LiveActivities;
use VentureDrake\LaravelCrm\Http\Livewire\LiveActivityMenu;
use VentureDrake\LaravelCrm\Http\Livewire\LiveAddressEdit;
use VentureDrake\LaravelCrm\Http\Livewire\LiveCalls;
use VentureDrake\LaravelCrm\Http\Livewire\LiveDealBoard;
use VentureDrake\LaravelCrm\Http\Livewire\LiveDealForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveDeliveryDetails;
use VentureDrake\LaravelCrm\Http\Livewire\LiveDeliveryItems;
use VentureDrake\LaravelCrm\Http\Livewire\LiveEmailEdit;
use VentureDrake\LaravelCrm\Http\Livewire\LiveFiles;
use VentureDrake\LaravelCrm\Http\Livewire\LiveInvoiceForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveInvoiceLines;
use VentureDrake\LaravelCrm\Http\Livewire\LiveLeadBoard;
use VentureDrake\LaravelCrm\Http\Livewire\LiveLeadForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveLunches;
use VentureDrake\LaravelCrm\Http\Livewire\LiveMeetings;
use VentureDrake\LaravelCrm\Http\Livewire\LiveNotes;
use VentureDrake\LaravelCrm\Http\Livewire\LiveOrderForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveOrderItems;
use VentureDrake\LaravelCrm\Http\Livewire\LivePhoneEdit;
use VentureDrake\LaravelCrm\Http\Livewire\LiveProductForm;
use VentureDrake\LaravelCrm\Http\Livewire\LivePurchaseOrderForm;
use VentureDrake\LaravelCrm\Http\Livewire\LivePurchaseOrderLines;
use VentureDrake\LaravelCrm\Http\Livewire\LiveQuoteBoard;
use VentureDrake\LaravelCrm\Http\Livewire\LiveQuoteForm;
use VentureDrake\LaravelCrm\Http\Livewire\LiveQuoteItems;
use VentureDrake\LaravelCrm\Http\Livewire\LiveRelatedContactOrganization;
use VentureDrake\LaravelCrm\Http\Livewire\LiveRelatedContactPerson;
use VentureDrake\LaravelCrm\Http\Livewire\LiveRelatedPerson;
use VentureDrake\LaravelCrm\Http\Livewire\LiveTasks;
use VentureDrake\LaravelCrm\Http\Livewire\NotifyToast;
use VentureDrake\LaravelCrm\Http\Livewire\PayInvoice;
use VentureDrake\LaravelCrm\Http\Livewire\SendInvoice;
use VentureDrake\LaravelCrm\Http\Livewire\SendPurchaseOrder;
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
use VentureDrake\LaravelCrm\Livewire\Activities\ActivityFeed;
use VentureDrake\LaravelCrm\Livewire\Activities\ActivityIndex;
use VentureDrake\LaravelCrm\Livewire\ActivityMenu;
use VentureDrake\LaravelCrm\Livewire\ActivityTabs;
use VentureDrake\LaravelCrm\Livewire\Auth\ForgotPassword;
use VentureDrake\LaravelCrm\Livewire\Auth\Login;
use VentureDrake\LaravelCrm\Livewire\Auth\ResetPassword;
use VentureDrake\LaravelCrm\Livewire\Calls\CallItem;
use VentureDrake\LaravelCrm\Livewire\Calls\CallRelated;
use VentureDrake\LaravelCrm\Livewire\Chat\ChatIndex;
use VentureDrake\LaravelCrm\Livewire\Chat\ChatShow;
use VentureDrake\LaravelCrm\Livewire\Dashboard;
use VentureDrake\LaravelCrm\Livewire\Deals\DealBoard;
use VentureDrake\LaravelCrm\Livewire\Deals\DealCreate;
use VentureDrake\LaravelCrm\Livewire\Deals\DealEdit;
use VentureDrake\LaravelCrm\Livewire\Deals\DealIndex;
use VentureDrake\LaravelCrm\Livewire\Deals\DealShow;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryCreate;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryEdit;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Deliveries\DeliveryShow;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\EmailCampaignCreate;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\EmailCampaignEdit;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\EmailCampaignIndex;
use VentureDrake\LaravelCrm\Livewire\EmailCampaigns\EmailCampaignShow;
use VentureDrake\LaravelCrm\Livewire\EmailTemplates\EmailTemplateCreate;
use VentureDrake\LaravelCrm\Livewire\EmailTemplates\EmailTemplateEdit;
use VentureDrake\LaravelCrm\Livewire\EmailTemplates\EmailTemplateIndex;
use VentureDrake\LaravelCrm\Livewire\EmailTemplates\EmailTemplateShow;
use VentureDrake\LaravelCrm\Livewire\Files\FileItem;
use VentureDrake\LaravelCrm\Livewire\Files\FileRelated;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceCreate;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceEdit;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoicePay;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceSend;
use VentureDrake\LaravelCrm\Livewire\Invoices\InvoiceShow;
use VentureDrake\LaravelCrm\Livewire\KanbanBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadBoard;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadCreate;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadEdit;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadIndex;
use VentureDrake\LaravelCrm\Livewire\Leads\LeadShow;
use VentureDrake\LaravelCrm\Livewire\Lunches\LunchItem;
use VentureDrake\LaravelCrm\Livewire\Lunches\LunchRelated;
use VentureDrake\LaravelCrm\Livewire\Meetings\MeetingItem;
use VentureDrake\LaravelCrm\Livewire\Meetings\MeetingRelated;
use VentureDrake\LaravelCrm\Livewire\ModelAddresses;
use VentureDrake\LaravelCrm\Livewire\ModelEmails;
use VentureDrake\LaravelCrm\Livewire\ModelPhones;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Livewire\Notes\NoteItem;
use VentureDrake\LaravelCrm\Livewire\Notes\NoteRelated;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderCreate;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderEdit;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\Orders\OrderShow;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationCreate;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationEdit;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationImport;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationIndex;
use VentureDrake\LaravelCrm\Livewire\Organizations\OrganizationShow;
use VentureDrake\LaravelCrm\Livewire\People\PersonCreate;
use VentureDrake\LaravelCrm\Livewire\People\PersonEdit;
use VentureDrake\LaravelCrm\Livewire\People\PersonImport;
use VentureDrake\LaravelCrm\Livewire\People\PersonIndex;
use VentureDrake\LaravelCrm\Livewire\People\PersonShow;
use VentureDrake\LaravelCrm\Livewire\Products\ProductCreate;
use VentureDrake\LaravelCrm\Livewire\Products\ProductEdit;
use VentureDrake\LaravelCrm\Livewire\Products\ProductIndex;
use VentureDrake\LaravelCrm\Livewire\Products\ProductShow;
use VentureDrake\LaravelCrm\Livewire\Profile\DeleteUserForm;
use VentureDrake\LaravelCrm\Livewire\Profile\LogoutOtherBrowserSessionsForm;
use VentureDrake\LaravelCrm\Livewire\Profile\TwoFactorAuthenticationForm;
use VentureDrake\LaravelCrm\Livewire\Profile\UpdatePasswordForm;
use VentureDrake\LaravelCrm\Livewire\Profile\UpdateProfileInformationForm;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderCreate;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderEdit;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderRelatedIndex;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderSend;
use VentureDrake\LaravelCrm\Livewire\PurchaseOrders\PurchaseOrderShow;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteBoard;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteCreate;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteEdit;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteIndex;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteSend;
use VentureDrake\LaravelCrm\Livewire\Quotes\QuoteShow;
use VentureDrake\LaravelCrm\Livewire\RelatedDeals;
use VentureDrake\LaravelCrm\Livewire\RelatedOrganizations;
use VentureDrake\LaravelCrm\Livewire\RelatedPeople;
use VentureDrake\LaravelCrm\Livewire\Settings\ChatWidgets\ChatWidgetEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\ChatWidgets\ChatWidgetIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\ChatWidgets\ChatWidgetShow;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups\CustomFieldGroupCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups\CustomFieldGroupEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups\CustomFieldGroupIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFieldGroups\CustomFieldGroupShow;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\CustomFieldCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\CustomFieldEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\CustomFieldIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\CustomFields\CustomFieldShow;
use VentureDrake\LaravelCrm\Livewire\Settings\Integrations\ClickSend\ClickSendConnect;
use VentureDrake\LaravelCrm\Livewire\Settings\Labels\LabelCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\Labels\LabelEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\Labels\LabelIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\Labels\LabelShow;
use VentureDrake\LaravelCrm\Livewire\Settings\LeadSources\LeadSourceCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\LeadSources\LeadSourceEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\LeadSources\LeadSourceIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\LeadSources\LeadSourceShow;
use VentureDrake\LaravelCrm\Livewire\Settings\Permissions\RoleCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\Permissions\RoleEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\Permissions\RoleIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\Permissions\RoleShow;
use VentureDrake\LaravelCrm\Livewire\Settings\Pipelines\PipelineEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\Pipelines\PipelineIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\Pipelines\PipelineShow;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\PipelineStageEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\PipelineStageIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\PipelineStages\PipelineStageShow;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\ProductCategoryCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\ProductCategoryEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\ProductCategoryIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\ProductCategories\ProductCategoryShow;
use VentureDrake\LaravelCrm\Livewire\Settings\SettingEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateCreate;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateEdit;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateIndex;
use VentureDrake\LaravelCrm\Livewire\Settings\TaxRates\TaxRateShow;
use VentureDrake\LaravelCrm\Livewire\SmsCampaigns\SmsCampaignCreate;
use VentureDrake\LaravelCrm\Livewire\SmsCampaigns\SmsCampaignEdit;
use VentureDrake\LaravelCrm\Livewire\SmsCampaigns\SmsCampaignIndex;
use VentureDrake\LaravelCrm\Livewire\SmsCampaigns\SmsCampaignShow;
use VentureDrake\LaravelCrm\Livewire\SmsTemplates\SmsTemplateCreate;
use VentureDrake\LaravelCrm\Livewire\SmsTemplates\SmsTemplateEdit;
use VentureDrake\LaravelCrm\Livewire\SmsTemplates\SmsTemplateIndex;
use VentureDrake\LaravelCrm\Livewire\SmsTemplates\SmsTemplateShow;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskCreate;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskEdit;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskIndex;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskItem;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskRelated;
use VentureDrake\LaravelCrm\Livewire\Tasks\TaskShow;
use VentureDrake\LaravelCrm\Livewire\Teams\TeamCreate;
use VentureDrake\LaravelCrm\Livewire\Teams\TeamEdit;
use VentureDrake\LaravelCrm\Livewire\Teams\TeamIndex;
use VentureDrake\LaravelCrm\Livewire\Teams\TeamShow;
use VentureDrake\LaravelCrm\Livewire\Users\UserCreate;
use VentureDrake\LaravelCrm\Livewire\Users\UserEdit;
use VentureDrake\LaravelCrm\Livewire\Users\UserImport;
use VentureDrake\LaravelCrm\Livewire\Users\UserIndex;
use VentureDrake\LaravelCrm\Livewire\Users\UserShow;
use VentureDrake\LaravelCrm\Models\Activity;
use VentureDrake\LaravelCrm\Models\Call;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatMessage;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrm\Models\Contact;
use VentureDrake\LaravelCrm\Models\Customer;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\DeliveryProduct;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\EmailCampaign;
use VentureDrake\LaravelCrm\Models\EmailCampaignRecipient;
use VentureDrake\LaravelCrm\Models\EmailTemplate;
use VentureDrake\LaravelCrm\Models\Field;
use VentureDrake\LaravelCrm\Models\FieldGroup;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldOption;
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
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Models\PipelineStageProbability;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductPrice;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\PurchaseOrderLine;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\QuoteProduct;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Models\SmsCampaign;
use VentureDrake\LaravelCrm\Models\SmsCampaignRecipient;
use VentureDrake\LaravelCrm\Models\SmsTemplate;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Models\XeroContact;
use VentureDrake\LaravelCrm\Models\XeroInvoice;
use VentureDrake\LaravelCrm\Models\XeroItem;
use VentureDrake\LaravelCrm\Models\XeroPerson;
use VentureDrake\LaravelCrm\Models\XeroPurchaseOrder;
use VentureDrake\LaravelCrm\Observers\ActivityObserver;
use VentureDrake\LaravelCrm\Observers\CallObserver;
use VentureDrake\LaravelCrm\Observers\ChatConversationObserver;
use VentureDrake\LaravelCrm\Observers\ChatMessageObserver;
use VentureDrake\LaravelCrm\Observers\ChatVisitorObserver;
use VentureDrake\LaravelCrm\Observers\ChatWidgetObserver;
use VentureDrake\LaravelCrm\Observers\ContactObserver;
use VentureDrake\LaravelCrm\Observers\CustomerObserver;
use VentureDrake\LaravelCrm\Observers\DealObserver;
use VentureDrake\LaravelCrm\Observers\DeliveryObserver;
use VentureDrake\LaravelCrm\Observers\DeliveryProductObserver;
use VentureDrake\LaravelCrm\Observers\EmailCampaignObserver;
use VentureDrake\LaravelCrm\Observers\EmailCampaignRecipientObserver;
use VentureDrake\LaravelCrm\Observers\EmailObserver;
use VentureDrake\LaravelCrm\Observers\EmailTemplateObserver;
use VentureDrake\LaravelCrm\Observers\FieldGroupObserver;
use VentureDrake\LaravelCrm\Observers\FieldModelObserver;
use VentureDrake\LaravelCrm\Observers\FieldObserver;
use VentureDrake\LaravelCrm\Observers\FieldOptionObserver;
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
use VentureDrake\LaravelCrm\Observers\OrganizationObserver;
use VentureDrake\LaravelCrm\Observers\PersonObserver;
use VentureDrake\LaravelCrm\Observers\PhoneObserver;
use VentureDrake\LaravelCrm\Observers\PipelineObserver;
use VentureDrake\LaravelCrm\Observers\PipelineStageObserver;
use VentureDrake\LaravelCrm\Observers\PipelineStageProbabilityObserver;
use VentureDrake\LaravelCrm\Observers\ProductObserver;
use VentureDrake\LaravelCrm\Observers\ProductPriceObserver;
use VentureDrake\LaravelCrm\Observers\PurchaseOrderLineObserver;
use VentureDrake\LaravelCrm\Observers\PurchaseOrderObserver;
use VentureDrake\LaravelCrm\Observers\QuoteObserver;
use VentureDrake\LaravelCrm\Observers\QuoteProductObserver;
use VentureDrake\LaravelCrm\Observers\SettingObserver;
use VentureDrake\LaravelCrm\Observers\SmsCampaignObserver;
use VentureDrake\LaravelCrm\Observers\SmsCampaignRecipientObserver;
use VentureDrake\LaravelCrm\Observers\SmsTemplateObserver;
use VentureDrake\LaravelCrm\Observers\TaskObserver;
use VentureDrake\LaravelCrm\Observers\TeamObserver;
use VentureDrake\LaravelCrm\Observers\UserObserver;
use VentureDrake\LaravelCrm\Observers\XeroContactObserver;
use VentureDrake\LaravelCrm\Observers\XeroInvoiceObserver;
use VentureDrake\LaravelCrm\Observers\XeroItemObserver;
use VentureDrake\LaravelCrm\Observers\XeroPersonObserver;
use VentureDrake\LaravelCrm\Observers\XeroPurchaseOrderObserver;
use VentureDrake\LaravelCrm\Observers\XeroTokenObserver;
use VentureDrake\LaravelCrm\Policies\CallPolicy;
use VentureDrake\LaravelCrm\Policies\ChatConversationPolicy;
use VentureDrake\LaravelCrm\Policies\ChatWidgetPolicy;
use VentureDrake\LaravelCrm\Policies\ContactPolicy;
use VentureDrake\LaravelCrm\Policies\CustomerPolicy;
use VentureDrake\LaravelCrm\Policies\DealPolicy;
use VentureDrake\LaravelCrm\Policies\DeliveryPolicy;
use VentureDrake\LaravelCrm\Policies\EmailCampaignPolicy;
use VentureDrake\LaravelCrm\Policies\EmailTemplatePolicy;
use VentureDrake\LaravelCrm\Policies\FieldGroupPolicy;
use VentureDrake\LaravelCrm\Policies\FieldOptionPolicy;
use VentureDrake\LaravelCrm\Policies\FieldPolicy;
use VentureDrake\LaravelCrm\Policies\FilePolicy;
use VentureDrake\LaravelCrm\Policies\InvoicePolicy;
use VentureDrake\LaravelCrm\Policies\LabelPolicy;
use VentureDrake\LaravelCrm\Policies\LeadPolicy;
use VentureDrake\LaravelCrm\Policies\LeadSourcePolicy;
use VentureDrake\LaravelCrm\Policies\LunchPolicy;
use VentureDrake\LaravelCrm\Policies\MeetingPolicy;
use VentureDrake\LaravelCrm\Policies\NotePolicy;
use VentureDrake\LaravelCrm\Policies\OrderPolicy;
use VentureDrake\LaravelCrm\Policies\OrganizationPolicy;
use VentureDrake\LaravelCrm\Policies\PermissionPolicy;
use VentureDrake\LaravelCrm\Policies\PersonPolicy;
use VentureDrake\LaravelCrm\Policies\PipelinePolicy;
use VentureDrake\LaravelCrm\Policies\PipelineStagePolicy;
use VentureDrake\LaravelCrm\Policies\ProductCategoryPolicy;
use VentureDrake\LaravelCrm\Policies\ProductPolicy;
use VentureDrake\LaravelCrm\Policies\PurchaseOrderPolicy;
use VentureDrake\LaravelCrm\Policies\QuotePolicy;
use VentureDrake\LaravelCrm\Policies\RolePolicy;
use VentureDrake\LaravelCrm\Policies\SettingPolicy;
use VentureDrake\LaravelCrm\Policies\SmsCampaignPolicy;
use VentureDrake\LaravelCrm\Policies\SmsTemplatePolicy;
use VentureDrake\LaravelCrm\Policies\TaskPolicy;
use VentureDrake\LaravelCrm\Policies\TaxRatePolicy;
use VentureDrake\LaravelCrm\Policies\TeamPolicy;
use VentureDrake\LaravelCrm\Policies\UserPolicy;
use VentureDrake\LaravelCrm\Services\SettingService;
use VentureDrake\LaravelCrm\View\Components\Addresses;
use VentureDrake\LaravelCrm\View\Components\CustomFields;
use VentureDrake\LaravelCrm\View\Components\CustomFieldValues;
use VentureDrake\LaravelCrm\View\Components\DeleteConfirm;
use VentureDrake\LaravelCrm\View\Components\Emails;
use VentureDrake\LaravelCrm\View\Components\Header;
use VentureDrake\LaravelCrm\View\Components\IndexToggle;
use VentureDrake\LaravelCrm\View\Components\Phones;
use VentureDrake\LaravelCrm\View\Components\TimelineItem;
use VentureDrake\LaravelCrm\View\Composers\SettingsComposer;

class LaravelCrmServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\User' => UserPolicy::class,
        'App\Models\User' => UserPolicy::class,
        'VentureDrake\LaravelCrm\Models\Team' => TeamPolicy::class,
        'VentureDrake\LaravelCrm\Models\Setting' => SettingPolicy::class,
        'VentureDrake\LaravelCrm\Models\Role' => RolePolicy::class,
        'VentureDrake\LaravelCrm\Models\Permission' => PermissionPolicy::class,
        'VentureDrake\LaravelCrm\Models\Lead' => LeadPolicy::class,
        'VentureDrake\LaravelCrm\Models\Deal' => DealPolicy::class,
        'VentureDrake\LaravelCrm\Models\Quote' => QuotePolicy::class,
        'VentureDrake\LaravelCrm\Models\Order' => OrderPolicy::class,
        'VentureDrake\LaravelCrm\Models\Invoice' => InvoicePolicy::class,
        'VentureDrake\LaravelCrm\Models\Customer' => CustomerPolicy::class,
        'VentureDrake\LaravelCrm\Models\Person' => PersonPolicy::class,
        'VentureDrake\LaravelCrm\Models\Organization' => OrganizationPolicy::class,
        'VentureDrake\LaravelCrm\Models\Contact' => ContactPolicy::class,
        'VentureDrake\LaravelCrm\Models\Product' => ProductPolicy::class,
        'VentureDrake\LaravelCrm\Models\ProductCategory' => ProductCategoryPolicy::class,
        'VentureDrake\LaravelCrm\Models\TaxRate' => TaxRatePolicy::class,
        'VentureDrake\LaravelCrm\Models\Label' => LabelPolicy::class,
        'VentureDrake\LaravelCrm\Models\LeadSource' => LeadSourcePolicy::class,
        'VentureDrake\LaravelCrm\Models\Task' => TaskPolicy::class,
        'VentureDrake\LaravelCrm\Models\Note' => NotePolicy::class,
        'VentureDrake\LaravelCrm\Models\Call' => CallPolicy::class,
        'VentureDrake\LaravelCrm\Models\Meeting' => MeetingPolicy::class,
        'VentureDrake\LaravelCrm\Models\Lunch' => LunchPolicy::class,
        'VentureDrake\LaravelCrm\Models\File' => FilePolicy::class,
        'VentureDrake\LaravelCrm\Models\Field' => FieldPolicy::class,
        'VentureDrake\LaravelCrm\Models\FieldGroup' => FieldGroupPolicy::class,
        'VentureDrake\LaravelCrm\Models\FieldOption' => FieldOptionPolicy::class,
        'VentureDrake\LaravelCrm\Models\Delivery' => DeliveryPolicy::class,
        'VentureDrake\LaravelCrm\Models\PurchaseOrder' => PurchaseOrderPolicy::class,
        'VentureDrake\LaravelCrm\Models\Pipeline' => PipelinePolicy::class,
        'VentureDrake\LaravelCrm\Models\PipelineStage' => PipelineStagePolicy::class,
        'VentureDrake\LaravelCrm\Models\ChatConversation' => ChatConversationPolicy::class,
        'VentureDrake\LaravelCrm\Models\ChatWidget' => ChatWidgetPolicy::class,
        'VentureDrake\LaravelCrm\Models\EmailCampaign' => EmailCampaignPolicy::class,
        'VentureDrake\LaravelCrm\Models\EmailTemplate' => EmailTemplatePolicy::class,
        'VentureDrake\LaravelCrm\Models\SmsCampaign' => SmsCampaignPolicy::class,
        'VentureDrake\LaravelCrm\Models\SmsTemplate' => SmsTemplatePolicy::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot(Router $router, Filesystem $filesystem)
    {
        Paginator::useBootstrap();

        if (class_exists('App\Models\User') && ! class_exists('App\User')) {
            class_alias(config('auth.providers.users.model'), 'App\User');

            if (class_exists('App\Models\Team')) {
                class_alias('App\Models\Team', 'App\Team');
            }
        }

        $this->registerPolicies();

        /*
         * Optional methods to load your package assets
         */
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-crm');
        // TBC: BS or TW mode, setting on config
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

        if (config('laravel-crm.route_subdomain')) {
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
        $router->pushMiddlewareToGroup('web', middleware: FormComponentsConfig::class);

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
        Customer::observe(CustomerObserver::class);
        Person::observe(PersonObserver::class);
        Organization::observe(OrganizationObserver::class);
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
        FieldOption::observe(FieldOptionObserver::class);
        FieldModel::observe(FieldModelObserver::class);
        FieldValue::observe(FieldValueObserver::class);
        Delivery::observe(DeliveryObserver::class);
        DeliveryProduct::observe(DeliveryProductObserver::class);
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        PurchaseOrderLine::observe(PurchaseOrderLineObserver::class);
        XeroPurchaseOrder::observe(XeroPurchaseOrderObserver::class);
        Pipeline::observe(PipelineObserver::class);
        PipelineStage::observe(PipelineStageObserver::class);
        PipelineStageProbability::observe(PipelineStageProbabilityObserver::class);

        ChatWidget::observe(ChatWidgetObserver::class);
        ChatVisitor::observe(ChatVisitorObserver::class);
        ChatConversation::observe(ChatConversationObserver::class);
        ChatMessage::observe(ChatMessageObserver::class);

        EmailCampaign::observe(EmailCampaignObserver::class);
        EmailTemplate::observe(EmailTemplateObserver::class);
        EmailCampaignRecipient::observe(EmailCampaignRecipientObserver::class);
        SmsCampaign::observe(SmsCampaignObserver::class);
        SmsTemplate::observe(SmsTemplateObserver::class);
        SmsCampaignRecipient::observe(SmsCampaignRecipientObserver::class);

        if (class_exists('App\Models\User')) {
            \App\Models\User::observe(UserObserver::class);
        } elseif (class_exists('App\User')) {
            User::observe(UserObserver::class);
        }

        if (class_exists('App\Models\Team')) {
            \App\Models\Team::observe(TeamObserver::class);
        } elseif (class_exists('App\Team')) {
            Team::observe(TeamObserver::class);
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
            $this->publishes([
                __DIR__.'/../config/laravel-crm.php' => config_path('laravel-crm.php'),
                __DIR__.'/../config/permission.php' => config_path('permission.php'),
                __DIR__.'/../config/mary.php' => config_path('mary.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crm'),
            ], 'views');

            // Publishing assets.
            $this->publishes([
                __DIR__.'/../public/vendor/laravel-crm/' => public_path('vendor/laravel-crm'),
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-crm'),
            ], 'assets');

            // Publishing the translation files.
            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-crm'),
            ], 'lang');

            // Publishing the migrations.
            $this->publishes([
                __DIR__.'/../database/migrations/create_permission_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_permission_tables.php', 1), // Spatie Permissions
                __DIR__.'/../database/migrations/add_teams_fields.php.stub' => $this->getMigrationFileName($filesystem, 'add_teams_fields.php', 2), // Spatie Permissions
                __DIR__.'/../database/migrations/create_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tables.php', 3),
                __DIR__.'/../database/migrations/create_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_settings_table.php', 4),
                __DIR__.'/../database/migrations/add_fields_to_roles_permissions_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_fields_to_roles_permissions_tables.php', 5),
                __DIR__.'/../database/migrations/add_label_editable_fields_to_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_label_editable_fields_to_laravel_crm_settings_table.php', 6),
                __DIR__.'/../database/migrations/add_team_id_to_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_team_id_to_laravel_crm_tables.php', 7),
                __DIR__.'/../database/migrations/create_laravel_crm_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_products_table.php', 8),
                __DIR__.'/../database/migrations/create_laravel_crm_product_categories_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_product_categories_table.php', 9),
                __DIR__.'/../database/migrations/create_laravel_crm_product_prices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_product_prices_table.php', 10),
                __DIR__.'/../database/migrations/create_laravel_crm_product_variations_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_product_variations_table.php', 11),
                __DIR__.'/../database/migrations/create_laravel_crm_deal_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_deal_products_table.php', 12),
                __DIR__.'/../database/migrations/add_global_to_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_global_to_laravel_crm_settings_table.php', 13),
                __DIR__.'/../database/migrations/alter_fields_for_encryption_on_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'alter_fields_for_encryption_on_laravel_crm_tables.php', 14),
                __DIR__.'/../database/migrations/create_laravel_crm_address_types_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_address_types_table.php', 15),
                __DIR__.'/../database/migrations/alter_type_on_laravel_crm_phones_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_type_on_laravel_crm_phones_table.php', 16),
                __DIR__.'/../database/migrations/add_description_to_laravel_crm_labels_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_description_to_laravel_crm_labels_table.php', 17),
                __DIR__.'/../database/migrations/add_name_to_laravel_crm_addresses_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_name_to_laravel_crm_addresses_table.php', 18),
                __DIR__.'/../database/migrations/create_laravel_crm_contacts_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_contacts_table.php', 19),
                __DIR__.'/../database/migrations/create_laravel_crm_contact_types_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_contact_types_table.php', 20),
                __DIR__.'/../database/migrations/create_laravel_crm_contact_contact_type_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_contact_contact_type_table.php', 21),
                __DIR__.'/../database/migrations/create_devices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_devices_table.php', 22), // Laravel Auth Checker
                __DIR__.'/../database/migrations/create_logins_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_logins_table.php', 23), // Laravel Auth Checker
                __DIR__.'/../database/migrations/update_logins_and_devices_table_user_relation.php.stub' => $this->getMigrationFileName($filesystem, 'update_logins_and_devices_table_user_relation.php', 25), // Laravel Auth Checker
                __DIR__.'/../database/migrations/create_laravel_crm_organization_types_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_organization_types_table.php', 26),
                __DIR__.'/../database/migrations/change_morph_col_names_on_laravel_crm_notes_table.php.stub' => $this->getMigrationFileName($filesystem, 'change_morph_col_names_on_laravel_crm_notes_table.php', 27),
                __DIR__.'/../database/migrations/add_related_note_to_laravel_crm_notes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_related_note_to_laravel_crm_notes_table.php', 28),
                __DIR__.'/../database/migrations/add_noted_at_to_laravel_crm_notes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_noted_at_to_laravel_crm_notes_table.php', 29),
                __DIR__.'/../database/migrations/create_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_quotes_table.php', 30),
                __DIR__.'/../database/migrations/create_laravel_crm_quote_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_quote_products_table.php', 31),
                __DIR__.'/../database/migrations/create_laravel_crm_files_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_files_table.php', 32),
                __DIR__.'/../database/migrations/add_mime_to_laravel_crm_files_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_mime_to_laravel_crm_files_table.php', 33),
                __DIR__.'/../database/migrations/create_xero_tokens_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_xero_tokens_table.php', 34),
                __DIR__.'/../database/migrations/create_laravel_crm_xero_items_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_items_table.php', 35),
                __DIR__.'/../database/migrations/create_laravel_crm_xero_contacts_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_contacts_table.php', 36),
                __DIR__.'/../database/migrations/create_laravel_crm_xero_people_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_people_table.php', 37),
                __DIR__.'/../database/migrations/add_reference_to_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_reference_to_laravel_crm_quotes_table.php', 38),
                __DIR__.'/../database/migrations/create_laravel_crm_tasks_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tasks_table.php', 39),
                __DIR__.'/../database/migrations/add_deleted_at_to_laravel_crm_activities_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_deleted_at_to_laravel_crm_activities_table.php', 40),
                __DIR__.'/../database/migrations/create_laravel_crm_timezones_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_timezones_table.php', 41),
                __DIR__.'/../database/migrations/add_team_id_to_xero_tokens_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_team_id_to_xero_tokens_table.php', 42),
                __DIR__.'/../database/migrations/create_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_orders_table.php', 43),
                __DIR__.'/../database/migrations/create_laravel_crm_order_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_order_products_table.php', 44),
                __DIR__.'/../database/migrations/create_laravel_crm_invoices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_invoices_table.php', 45),
                __DIR__.'/../database/migrations/create_laravel_crm_invoice_lines_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_invoice_lines_table.php', 46),
                __DIR__.'/../database/migrations/add_reference_to_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_reference_to_laravel_crm_orders_table.php', 47),
                __DIR__.'/../database/migrations/create_laravel_crm_calls_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_calls_table.php', 48),
                __DIR__.'/../database/migrations/create_laravel_crm_meetings_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_meetings_table.php', 49),
                __DIR__.'/../database/migrations/create_laravel_crm_lunches_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_lunches_table.php', 50),
                __DIR__.'/../database/migrations/add_location_to_laravel_crm_activities_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_location_to_laravel_crm_activities_table.php', 51),
                __DIR__.'/../database/migrations/add_prefix_to_laravel_crm_invoices_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_invoices_table.php', 52),
                __DIR__.'/../database/migrations/create_laravel_crm_usage_requests_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_usage_requests_table.php', 53),
                __DIR__.'/../database/migrations/add_label_type_to_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_label_type_to_laravel_crm_fields_table.php', 54),
                __DIR__.'/../database/migrations/create_laravel_crm_field_models_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_field_models_table.php', 55),
                __DIR__.'/../database/migrations/create_laravel_crm_field_groups_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_field_groups_table.php', 56),
                __DIR__.'/../database/migrations/add_team_id_to_laravel_crm_usage_requests_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_team_id_to_laravel_crm_usage_requests_table.php', 57),
                __DIR__.'/../database/migrations/alter_field_group_id_on_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_field_group_id_on_laravel_crm_fields_table.php', 58),
                __DIR__.'/../database/migrations/add_system_to_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_system_to_laravel_crm_fields_table.php', 59),
                __DIR__.'/../database/migrations/add_comments_to_laravel_crm_quote_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_comments_to_laravel_crm_quote_products_table.php', 60),
                __DIR__.'/../database/migrations/add_comments_to_laravel_crm_order_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_comments_to_laravel_crm_order_products_table.php', 61),
                __DIR__.'/../database/migrations/create_laravel_crm_deliveries_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_deliveries_table.php', 62),
                __DIR__.'/../database/migrations/create_laravel_crm_delivery_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_delivery_products_table.php', 63),
                __DIR__.'/../database/migrations/alter_url_on_laravel_crm_usage_requests_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_url_on_laravel_crm_usage_requests_table.php', 64),
                __DIR__.'/../database/migrations/create_laravel_crm_clients_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_clients_table.php', 65),
                __DIR__.'/../database/migrations/create_laravel_crm_xero_invoices_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_invoices_table.php', 66),
                __DIR__.'/../database/migrations/add_contact_to_laravel_crm_addresses_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_contact_to_laravel_crm_addresses_table.php', 67),
                __DIR__.'/../database/migrations/add_phone_to_laravel_crm_addresses_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_phone_to_laravel_crm_addresses_table.php', 68),
                __DIR__.'/../database/migrations/add_name_to_laravel_crm_clients_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_name_to_laravel_crm_clients_table.php', 69),
                __DIR__.'/../database/migrations/add_delivery_dates_to_laravel_crm_deliveries_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_delivery_dates_to_laravel_crm_deliveries_table.php', 70),
                __DIR__.'/../database/migrations/add_client_to_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_orders_table.php', 71),
                __DIR__.'/../database/migrations/add_client_to_laravel_crm_leads_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_leads_table.php', 72),
                __DIR__.'/../database/migrations/add_client_to_laravel_crm_deals_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_deals_table.php', 73),
                __DIR__.'/../database/migrations/add_client_to_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_client_to_laravel_crm_quotes_table.php', 74),
                __DIR__.'/../database/migrations/add_account_codes_to_laravel_crm_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_account_codes_to_laravel_crm_products_table.php', 75),
                __DIR__.'/../database/migrations/add_prefix_to_laravel_crm_quotes_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_quotes_table.php', 76),
                __DIR__.'/../database/migrations/add_prefix_to_laravel_crm_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_orders_table.php', 77),
                __DIR__.'/../database/migrations/add_quote_product_id_to_laravel_crm_order_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_quote_product_id_to_laravel_crm_order_products_table.php', 78),
                __DIR__.'/../database/migrations/add_quantity_to_laravel_crm_delivery_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_quantity_to_laravel_crm_delivery_products_table.php', 79),
                __DIR__.'/../database/migrations/create_laravel_crm_tax_rates_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_tax_rates_table.php', 80),
                __DIR__.'/../database/migrations/add_order_product_id_to_laravel_crm_invoice_lines_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_order_product_id_to_laravel_crm_invoice_lines_table.php', 81),
                __DIR__.'/../database/migrations/add_prefix_to_laravel_crm_deliveries_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_deliveries_table.php', 82),
                __DIR__.'/../database/migrations/alter_value_on_laravel_crm_field_values_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_value_on_laravel_crm_field_values_table.php', 83),
                __DIR__.'/../database/migrations/add_comments_to_laravel_crm_invoice_lines_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_comments_to_laravel_crm_invoice_lines_table.php', 84),
                __DIR__.'/../database/migrations/add_default_to_laravel_crm_tax_rates_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_default_to_laravel_crm_tax_rates_table.php', 85),
                __DIR__.'/../database/migrations/create_laravel_crm_industries_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_industries_table.php', 86),
                __DIR__.'/../database/migrations/add_extra_fields_to_laravel_crm_organizations_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_extra_fields_to_laravel_crm_organizations_table.php', 87),
                __DIR__.'/../database/migrations/create_laravel_crm_purchase_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_purchase_orders_table.php', 88),
                __DIR__.'/../database/migrations/create_laravel_crm_purchase_order_lines_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_purchase_order_lines_table.php', 89),
                __DIR__.'/../database/migrations/create_laravel_crm_xero_purchase_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_xero_purchase_orders_table.php', 90),
                __DIR__.'/../database/migrations/add_tax_type_to_laravel_crm_tax_rates_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_tax_type_to_laravel_crm_tax_rates_table.php', 91),
                __DIR__.'/../database/migrations/add_barcode_to_laravel_crm_products_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_barcode_to_laravel_crm_products_table.php', 92),
                __DIR__.'/../database/migrations/create_laravel_crm_field_options_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_field_options_table.php', 93),
                __DIR__.'/../database/migrations/alter_type_on_laravel_crm_fields_table.php.stub' => $this->getMigrationFileName($filesystem, 'alter_type_on_laravel_crm_fields_table.php', 94),
                __DIR__.'/../database/migrations/add_soft_delete_to_laravel_crm_field_values_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_soft_delete_to_laravel_crm_field_values_table.php', 95),
                __DIR__.'/../database/migrations/add_terms_to_laravel_crm_purchase_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_terms_to_laravel_crm_purchase_orders_table.php', 96),
                __DIR__.'/../database/migrations/add_delivery_type_to_laravel_crm_purchase_orders_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_delivery_type_to_laravel_crm_purchase_orders_table.php', 97),
                __DIR__.'/../database/migrations/create_laravel_crm_pipelines_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_pipelines_table.php', 98),
                __DIR__.'/../database/migrations/create_laravel_crm_pipeline_stage_probabilities_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_pipeline_stage_probabilities_table.php', 99),
                __DIR__.'/../database/migrations/create_laravel_crm_pipeline_stages_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_pipeline_stages_table.php', 100),
                __DIR__.'/../database/migrations/add_pipeline_to_laravel_crm_models_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_pipeline_to_laravel_crm_models_table.php', 101),
                __DIR__.'/../database/migrations/add_user_to_laravel_crm_settings_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_user_to_laravel_crm_settings_table.php', 102),
                __DIR__.'/../database/migrations/add_prefix_to_laravel_crm_leads_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_leads_table.php', 103),
                __DIR__.'/../database/migrations/add_prefix_to_laravel_crm_deals_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_prefix_to_laravel_crm_deals_table.php', 104),
                __DIR__.'/../database/migrations/add_order_to_laravel_crm_items_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_order_to_laravel_crm_items_tables.php', 105),
                __DIR__.'/../database/migrations/add_pipeline_order_to_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'add_pipeline_order_to_laravel_crm_tables.php', 106),
                __DIR__.'/../database/migrations/create_laravel_crm_chat_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_chat_tables.php', 107),
                __DIR__.'/../database/migrations/create_laravel_crm_chat_visitor_page_views_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_chat_visitor_page_views_table.php', 108),
                __DIR__.'/../database/migrations/add_lead_id_to_laravel_crm_chat_conversations_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_lead_id_to_laravel_crm_chat_conversations_table.php', 109),
                __DIR__.'/../database/migrations/add_visitor_read_at_to_laravel_crm_chat_messages_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_visitor_read_at_to_laravel_crm_chat_messages_table.php', 110),
                __DIR__.'/../database/migrations/add_subscribed_to_laravel_crm_emails_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_subscribed_to_laravel_crm_emails_table.php', 111),
                __DIR__.'/../database/migrations/create_laravel_crm_email_templates_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_email_templates_table.php', 112),
                __DIR__.'/../database/migrations/create_laravel_crm_email_campaigns_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_email_campaigns_table.php', 113),
                __DIR__.'/../database/migrations/create_laravel_crm_email_campaign_recipients_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_email_campaign_recipients_table.php', 114),
                __DIR__.'/../database/migrations/create_laravel_crm_email_campaign_clicks_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_email_campaign_clicks_table.php', 115),
                __DIR__.'/../database/migrations/seed_laravel_crm_email_templates.php.stub' => $this->getMigrationFileName($filesystem, 'seed_laravel_crm_email_templates.php', 116),
                __DIR__.'/../database/migrations/add_subscribed_to_laravel_crm_phones_table.php.stub' => $this->getMigrationFileName($filesystem, 'add_subscribed_to_laravel_crm_phones_table.php', 117),
                __DIR__.'/../database/migrations/create_laravel_crm_sms_templates_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_sms_templates_table.php', 118),
                __DIR__.'/../database/migrations/create_laravel_crm_sms_campaigns_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_sms_campaigns_table.php', 119),
                __DIR__.'/../database/migrations/create_laravel_crm_sms_campaign_recipients_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_sms_campaign_recipients_table.php', 120),
                __DIR__.'/../database/migrations/create_laravel_crm_sms_campaign_clicks_table.php.stub' => $this->getMigrationFileName($filesystem, 'create_laravel_crm_sms_campaign_clicks_table.php', 121),
                __DIR__.'/../database/migrations/seed_laravel_crm_sms_templates.php.stub' => $this->getMigrationFileName($filesystem, 'seed_laravel_crm_sms_templates.php', 122),
                __DIR__.'/../database/migrations/ensure_encryptable_columns_widened_on_laravel_crm_tables.php.stub' => $this->getMigrationFileName($filesystem, 'ensure_encryptable_columns_widened_on_laravel_crm_tables.php', 123),
            ], 'migrations');

            // Publishing the seeders
            if (! class_exists('LaravelCrmTablesSeeder')) {
                if (version_compare(app()->version(), '8', '>=')) {
                    $this->publishes([
                        __DIR__.'/../database/seeders/LaravelCrmTablesSeeder.php' => database_path(
                            'seeders/LaravelCrmTablesSeeder.php'
                        ),
                    ], 'seeders');
                } else {
                    $this->publishes([
                        __DIR__.'/../database/seeders/LaravelCrmTablesSeeder.php' => database_path(
                            'seeds/LaravelCrmTablesSeeder.php'
                        ),
                    ], 'seeders');
                }
            }

            // Registering package commands.
            $this->commands([
                LaravelCrmInstall::class,
                LaravelCrmAddUser::class,
                LaravelCrmUpdate::class,
                LaravelCrmPermissions::class,
                LaravelCrmLabels::class,
                LaravelCrmLeadSources::class,
                LaravelCrmAddressTypes::class,
                LaravelCrmOrganizationTypes::class,
                LaravelCrmXero::class,
                LaravelCrmReminders::class,
                LaravelCrmContactTypes::class,
                LaravelCrmFields::class,
                LaravelCrmArchive::class,
                LaravelCrmEncrypt::class,
                LaravelCrmDecrypt::class,
                LaravelCrmV2::class,
                LaravelCrmSampleData::class,
                LaravelCrmEmailCampaignsDispatch::class,
                LaravelCrmSmsCampaignsDispatch::class,
            ]);

        }

        // View components
        Blade::componentNamespace('VentureDrake\\LaravelCrm\\View\\Components', 'crm');
        Blade::component('crm-index-toggle', IndexToggle::class);
        Blade::component('crm-delete-confirm', DeleteConfirm::class);
        Blade::component('crm-header', Header::class);
        Blade::component('crm-phones', Phones::class);
        Blade::component('crm-emails', Emails::class);
        Blade::component('crm-addresses', Addresses::class);
        Blade::component('crm-timeline-item', TimelineItem::class);
        Blade::component('crm-custom-fields', CustomFields::class);
        Blade::component('crm-custom-field-values', CustomFieldValues::class);

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
        Livewire::component('related-contact-organizations', LiveRelatedContactOrganization::class);
        Livewire::component('related-contact-people', LiveRelatedContactPerson::class);
        Livewire::component('related-people', LiveRelatedPerson::class);
        Livewire::component('live-lead-form', LiveLeadForm::class);
        Livewire::component('live-lead-board', LiveLeadBoard::class);
        Livewire::component('deal-form', LiveDealForm::class);
        Livewire::component('live-deal-board', LiveDealBoard::class);
        Livewire::component('quote-form', LiveQuoteForm::class);
        Livewire::component('live-quote-board', LiveQuoteBoard::class);
        Livewire::component('notify-toast', NotifyToast::class);
        Livewire::component('quote-items', LiveQuoteItems::class);
        Livewire::component('order-form', LiveOrderForm::class);
        Livewire::component('order-items', LiveOrderItems::class);
        Livewire::component('invoice-form', LiveInvoiceForm::class);
        Livewire::component('delivery-items', LiveDeliveryItems::class);
        Livewire::component('purchase-order-form', LivePurchaseOrderForm::class);
        Livewire::component('activity-menu', LiveActivityMenu::class);
        Livewire::component('xero-connect', XeroConnect::class);
        Livewire::component('activities', LiveActivities::class);
        Livewire::component('send-quote', SendQuote::class);
        Livewire::component('invoice-lines', LiveInvoiceLines::class);
        Livewire::component('send-invoice', SendInvoice::class);
        Livewire::component('pay-invoice', PayInvoice::class);
        Livewire::component('product-form', LiveProductForm::class);
        Livewire::component('purchase-order-lines', LivePurchaseOrderLines::class);
        Livewire::component('fields.create-or-edit', CreateOrEdit::class);
        Livewire::component('send-purchase-order', SendPurchaseOrder::class);
        Livewire::component('delivery-details', LiveDeliveryDetails::class);

        /* Version 2 Livewire Components */
        Livewire::component('crm-dashboard', Dashboard::class);
        Livewire::component('crm-auth-login', Login::class);
        Livewire::component('crm-auth-forgot-password', ForgotPassword::class);
        Livewire::component('crm-auth-reset-password', ResetPassword::class);
        Livewire::component('crm-kanban-board', KanbanBoard::class);
        Livewire::component('crm-activity-menu', ActivityMenu::class);
        Livewire::component('crm-activity-tabs', ActivityTabs::class);
        Livewire::component('crm-lead-index', LeadIndex::class);
        Livewire::component('crm-lead-board', LeadBoard::class);
        Livewire::component('crm-lead-show', LeadShow::class);
        Livewire::component('crm-lead-create', LeadCreate::class);
        Livewire::component('crm-lead-edit', LeadEdit::class);
        Livewire::component('crm-task-index', TaskIndex::class);
        Livewire::component('crm-task-show', TaskShow::class);
        Livewire::component('crm-task-create', TaskCreate::class);
        Livewire::component('crm-task-edit', TaskEdit::class);
        Livewire::component('crm-task-item', TaskItem::class);
        Livewire::component('crm-task-related', TaskRelated::class);

        // Chat
        Livewire::component('crm-chat-index', ChatIndex::class);
        Livewire::component('crm-chat-show', ChatShow::class);
        Livewire::component('crm-settings-chat-widget-index', ChatWidgetIndex::class);
        Livewire::component('crm-settings-chat-widget-edit', ChatWidgetEdit::class);
        Livewire::component('crm-settings-chat-widget-show', ChatWidgetShow::class);

        // Email Marketing
        Livewire::component('crm-email-campaign-index', EmailCampaignIndex::class);
        Livewire::component('crm-email-campaign-create', EmailCampaignCreate::class);
        Livewire::component('crm-email-campaign-edit', EmailCampaignEdit::class);
        Livewire::component('crm-email-campaign-show', EmailCampaignShow::class);
        Livewire::component('crm-email-template-index', EmailTemplateIndex::class);
        Livewire::component('crm-email-template-create', EmailTemplateCreate::class);
        Livewire::component('crm-email-template-edit', EmailTemplateEdit::class);
        Livewire::component('crm-email-template-show', EmailTemplateShow::class);
        Livewire::component('crm-sms-campaign-index', SmsCampaignIndex::class);
        Livewire::component('crm-sms-campaign-create', SmsCampaignCreate::class);
        Livewire::component('crm-sms-campaign-edit', SmsCampaignEdit::class);
        Livewire::component('crm-sms-campaign-show', SmsCampaignShow::class);
        Livewire::component('crm-sms-template-index', SmsTemplateIndex::class);
        Livewire::component('crm-sms-template-create', SmsTemplateCreate::class);
        Livewire::component('crm-sms-template-edit', SmsTemplateEdit::class);
        Livewire::component('crm-sms-template-show', SmsTemplateShow::class);
        Livewire::component('crm-clicksend-connect', ClickSendConnect::class);
        Livewire::component('crm-deal-index', DealIndex::class);
        Livewire::component('crm-deal-board', DealBoard::class);
        Livewire::component('crm-deal-show', DealShow::class);
        Livewire::component('crm-deal-create', DealCreate::class);
        Livewire::component('crm-deal-edit', DealEdit::class);
        Livewire::component('crm-quote-index', QuoteIndex::class);
        Livewire::component('crm-quote-board', QuoteBoard::class);
        Livewire::component('crm-quote-show', QuoteShow::class);
        Livewire::component('crm-quote-create', QuoteCreate::class);
        Livewire::component('crm-quote-edit', QuoteEdit::class);
        Livewire::component('crm-quote-send', QuoteSend::class);
        Livewire::component('crm-order-index', OrderIndex::class);
        Livewire::component('crm-order-related-index', OrderRelatedIndex::class);
        Livewire::component('crm-order-show', OrderShow::class);
        Livewire::component('crm-order-create', OrderCreate::class);
        Livewire::component('crm-order-edit', OrderEdit::class);
        Livewire::component('crm-invoice-index', InvoiceIndex::class);
        Livewire::component('crm-invoice-related-index', InvoiceRelatedIndex::class);
        Livewire::component('crm-invoice-show', InvoiceShow::class);
        Livewire::component('crm-invoice-create', InvoiceCreate::class);
        Livewire::component('crm-invoice-edit', InvoiceEdit::class);
        Livewire::component('crm-invoice-send', InvoiceSend::class);
        Livewire::component('crm-invoice-pay', InvoicePay::class);
        Livewire::component('crm-delivery-index', DeliveryIndex::class);
        Livewire::component('crm-delivery-related-index', DeliveryRelatedIndex::class);
        Livewire::component('crm-delivery-show', DeliveryShow::class);
        Livewire::component('crm-delivery-create', DeliveryCreate::class);
        Livewire::component('crm-delivery-edit', DeliveryEdit::class);
        Livewire::component('crm-purchase-order-index', PurchaseOrderIndex::class);
        Livewire::component('crm-purchase-order-related-index', PurchaseOrderRelatedIndex::class);
        Livewire::component('crm-purchase-order-show', PurchaseOrderShow::class);
        Livewire::component('crm-purchase-order-create', PurchaseOrderCreate::class);
        Livewire::component('crm-purchase-order-edit', PurchaseOrderEdit::class);
        Livewire::component('crm-purchase-order-send', PurchaseOrderSend::class);
        Livewire::component('crm-person-index', PersonIndex::class);
        Livewire::component('crm-person-create', PersonCreate::class);
        Livewire::component('crm-person-edit', PersonEdit::class);
        Livewire::component('crm-person-show', PersonShow::class);
        Livewire::component('crm-person-import', PersonImport::class);
        Livewire::component('crm-organization-index', OrganizationIndex::class);
        Livewire::component('crm-organization-create', OrganizationCreate::class);
        Livewire::component('crm-organization-edit', OrganizationEdit::class);
        Livewire::component('crm-organization-show', OrganizationShow::class);
        Livewire::component('crm-organization-import', OrganizationImport::class);
        Livewire::component('crm-user-index', UserIndex::class);
        Livewire::component('crm-user-create', UserCreate::class);
        Livewire::component('crm-user-edit', UserEdit::class);
        Livewire::component('crm-user-show', UserShow::class);
        Livewire::component('crm-user-import', UserImport::class);
        Livewire::component('crm-team-index', TeamIndex::class);
        Livewire::component('crm-team-create', TeamCreate::class);
        Livewire::component('crm-team-edit', TeamEdit::class);
        Livewire::component('crm-team-show', TeamShow::class);
        Livewire::component('crm-product-index', ProductIndex::class);
        Livewire::component('crm-product-create', ProductCreate::class);
        Livewire::component('crm-product-edit', ProductEdit::class);
        Livewire::component('crm-product-show', ProductShow::class);

        Livewire::component('crm-activity-feed', ActivityFeed::class);
        Livewire::component('crm-activity-index', ActivityIndex::class);
        Livewire::component('crm-note-item', NoteItem::class);
        Livewire::component('crm-note-related', NoteRelated::class);
        Livewire::component('crm-call-item', CallItem::class);
        Livewire::component('crm-call-related', CallRelated::class);
        Livewire::component('crm-meeting-item', MeetingItem::class);
        Livewire::component('crm-meeting-related', MeetingRelated::class);
        Livewire::component('crm-lunch-item', LunchItem::class);
        Livewire::component('crm-lunch-related', LunchRelated::class);
        Livewire::component('crm-file-item', FileItem::class);
        Livewire::component('crm-file-related', FileRelated::class);

        Livewire::component('crm-model-phones', ModelPhones::class);
        Livewire::component('crm-model-emails', ModelEmails::class);
        Livewire::component('crm-model-addresses', ModelAddresses::class);
        Livewire::component('crm-model-products', ModelProducts::class);
        Livewire::component('crm-related-people', RelatedPeople::class);
        Livewire::component('crm-related-organizations', RelatedOrganizations::class);
        Livewire::component('crm-related-deals', RelatedDeals::class);

        Livewire::component('crm-settings-edit', SettingEdit::class);

        Livewire::component('crm-profile-update-information', UpdateProfileInformationForm::class);
        Livewire::component('crm-profile-update-password', UpdatePasswordForm::class);
        Livewire::component('crm-profile-browser-sessions', LogoutOtherBrowserSessionsForm::class);
        Livewire::component('crm-profile-delete-user', DeleteUserForm::class);
        Livewire::component('crm-profile-two-factor', TwoFactorAuthenticationForm::class);

        Livewire::component('crm-settings-role-index', RoleIndex::class);
        Livewire::component('crm-settings-role-create', RoleCreate::class);
        Livewire::component('crm-settings-role-edit', RoleEdit::class);
        Livewire::component('crm-settings-role-show', RoleShow::class);

        Livewire::component('crm-settings-pipeline-index', PipelineIndex::class);
        Livewire::component('crm-settings-pipeline-edit', PipelineEdit::class);
        Livewire::component('crm-settings-pipeline-show', PipelineShow::class);

        Livewire::component('crm-settings-pipeline-stage-index', PipelineStageIndex::class);
        Livewire::component('crm-settings-pipeline-stage-edit', PipelineStageEdit::class);
        Livewire::component('crm-settings-pipeline-stage-show', PipelineStageShow::class);

        Livewire::component('crm-settings-product-category-index', ProductCategoryIndex::class);
        Livewire::component('crm-settings-product-category-create', ProductCategoryCreate::class);
        Livewire::component('crm-settings-product-category-edit', ProductCategoryEdit::class);
        Livewire::component('crm-settings-product-category-show', ProductCategoryShow::class);

        Livewire::component('crm-settings-tax-rate-index', TaxRateIndex::class);
        Livewire::component('crm-settings-tax-rate-create', TaxRateCreate::class);
        Livewire::component('crm-settings-tax-rate-edit', TaxRateEdit::class);
        Livewire::component('crm-settings-tax-rate-show', TaxRateShow::class);

        Livewire::component('crm-settings-label-index', LabelIndex::class);
        Livewire::component('crm-settings-label-create', LabelCreate::class);
        Livewire::component('crm-settings-label-edit', LabelEdit::class);
        Livewire::component('crm-settings-label-show', LabelShow::class);

        Livewire::component('crm-settings-lead-source-index', LeadSourceIndex::class);
        Livewire::component('crm-settings-lead-source-create', LeadSourceCreate::class);
        Livewire::component('crm-settings-lead-source-edit', LeadSourceEdit::class);
        Livewire::component('crm-settings-lead-source-show', LeadSourceShow::class);

        Livewire::component('crm-settings-custom-field-index', CustomFieldIndex::class);
        Livewire::component('crm-settings-custom-field-create', CustomFieldCreate::class);
        Livewire::component('crm-settings-custom-field-edit', CustomFieldEdit::class);
        Livewire::component('crm-settings-custom-field-show', CustomFieldShow::class);

        Livewire::component('crm-settings-custom-field-group-index', CustomFieldGroupIndex::class);
        Livewire::component('crm-settings-custom-field-group-create', CustomFieldGroupCreate::class);
        Livewire::component('crm-settings-custom-field-group-edit', CustomFieldGroupEdit::class);
        Livewire::component('crm-settings-custom-field-group-show', CustomFieldGroupShow::class);

        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);

                $schedule->command('laravelcrm:reminders')
                    ->name('laravelCrmReminders')
                    ->everyMinute()
                    ->withoutOverlapping();

                $schedule->command('laravelcrm:email-campaigns-dispatch')
                    ->name('laravelCrmEmailCampaignsDispatch')
                    ->everyMinute()
                    ->withoutOverlapping();

                $schedule->command('laravelcrm:sms-campaigns-dispatch')
                    ->name('laravelCrmSmsCampaignsDispatch')
                    ->everyMinute()
                    ->withoutOverlapping();

                $schedule->command('laravelcrm:archive')
                    ->name('laravelCrmArchiving')
                    ->daily()
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

        View::composer('*', SettingsComposer::class);

        Blade::if('hasleadsenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('leads', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasdealsenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('deals', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasquotesenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('quotes', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasordersenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('orders', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasinvoicesenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('invoices', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasdeliveriesenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('deliveries', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('haspurchaseordersenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('purchase-orders', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasteamsenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('teams', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('haschatenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('chat', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hasemailmarketingenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('email-marketing', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
                return true;
            }
        });

        Blade::if('hassmsmarketingenabled', function () {
            if (is_array(config('laravel-crm.modules')) && in_array('sms-marketing', config('laravel-crm.modules'))) {
                return true;
            } elseif (! config('laravel-crm.modules')) {
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
        $this->mergeConfigFrom(__DIR__.'/../config/package.php', 'laravel-crm');
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-crm.php', 'laravel-crm');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-crm', function () {
            return new LaravelCrm;
        });

        $this->app->singleton('laravel-crm.settings', function () {
            return new SettingService;
        });

        $this->app->register(LaravelCrmEventServiceProvider::class);
    }

    protected function registerRoutes()
    {
        // Auth routes — only 'web' middleware, no crm/crm-api (those require auth)
        Route::group($this->authRouteConfiguration(), function () {
            if (config('laravel-crm.user_interface')) {
                $this->loadRoutesFrom(__DIR__.'/Http/auth-routes.php');
            }
        });

        // Chat widget embed — registered FIRST and OUTSIDE the web middleware
        // group so it bypasses session/CSRF (visitor uses an opaque token).
        // Also registered without the `crm` route prefix so the embed URL
        // is always /p/chat/{key} regardless of LARAVEL_CRM_ROUTE_PREFIX.
        Route::group(['domain' => null, 'prefix' => null, 'middleware' => []], function () {
            if (config('laravel-crm.user_interface')) {
                $this->loadRoutesFrom(__DIR__.'/Http/chat-embed-routes.php');
            }
        });

        // Email campaign tracking routes — also outside `web` middleware so
        // open pixels and one-click unsubscribe links work without session/CSRF.
        Route::group(['domain' => null, 'prefix' => null, 'middleware' => []], function () {
            if (config('laravel-crm.user_interface')) {
                $this->loadRoutesFrom(__DIR__.'/Http/email-tracking-routes.php');
            }
        });

        // Main CRM routes — full middleware stack
        Route::group($this->routeConfiguration(), function () {
            if (config('laravel-crm.user_interface')) {
                $this->loadRoutesFrom(__DIR__.'/Http/routes.php');
            }
        });
    }

    protected function authRouteConfiguration()
    {
        if (config('laravel-crm.route_subdomain')) {
            $host = explode('.', request()->getHost());
            if (count($host) == 3) {
                $domain = config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 2)].'.'.end($host);
            } elseif (count($host) == 4) {
                $domain = config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 3)].'.'.$host[(count($host) - 2)].'.'.end($host);
            }
        }

        return [
            'domain' => $domain ?? null,
            'prefix' => (config('laravel-crm.route_prefix')) ? config('laravel-crm.route_prefix') : null,
            'middleware' => ['web'],
        ];
    }

    protected function routeConfiguration()
    {
        if (config('laravel-crm.route_subdomain')) {
            $host = explode('.', request()->getHost());
            if (count($host) == 3) { // .com
                $domain = config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 2)].'.'.end($host);
            } elseif (count($host) == 4) { // .com.au
                $domain = config('laravel-crm.route_subdomain').'.'.$host[(count($host) - 3)].'.'.$host[(count($host) - 2)].'.'.end($host);
            }
        }

        return [
            'domain' => $domain ?? null,
            'prefix' => (config('laravel-crm.route_prefix')) ? config('laravel-crm.route_prefix') : null,
            'middleware' => array_unique(array_merge(['web', 'crm', 'crm-api'], config('laravel-crm.route_middleware') ?? [])),
        ];
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
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
