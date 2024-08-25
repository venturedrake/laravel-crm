<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Http\Requests\StorePurchaseOrderRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePurchaseOrderRequest;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\PurchaseOrderService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\SettingService;

class PurchaseOrderController extends Controller
{
    /**
     * @var SettingService
     */
    private $settingService;

    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganisationService
     */
    private $organisationService;

    /**
     * @var PurchaseOrderService
     */
    private $purchaseOrderService;

    public function __construct(SettingService $settingService, PersonService $personService, OrganisationService $organisationService, PurchaseOrderService $purchaseOrderService)
    {
        $this->settingService = $settingService;
        $this->personService = $personService;
        $this->organisationService = $organisationService;
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        PurchaseOrder::resetSearchValue($request);
        $params = PurchaseOrder::filters($request);

        if (PurchaseOrder::filter($params)->get()->count() < 30) {
            $purchaseOrders = PurchaseOrder::filter($params)->latest()->get();
        } else {
            $purchaseOrders = PurchaseOrder::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        switch ($request->model) {
            case "person":
                $person = Person::find($request->id);

                break;

            case "organisation":
                $organisation = Organisation::find($request->id);

                break;

            case "order":
                $order = Order::find($request->id);

                break;
        }

        $related = $this->settingService->get('team');

        $addresses = [];
        foreach($related->addresses()->get() as $address) {
            $addresses[$address->id] = $address->address;
        }

        $purchaseOrderTerms = $this->settingService->get('purchase_order_terms');
        $purchaseOrderDeliveryInstructions = $this->settingService->get('purchase_order_delivery_instructions');

        return view('laravel-crm::purchase-orders.create', [
            'person' => $person ?? null,
            'organisation' => $organisation ?? null,
            'order' => $order ?? null,
            'prefix' => $this->settingService->get('purchase_order_prefix'),
            'number' => (PurchaseOrder::latest()->first()->number ?? 1000) + 1,
            'addresses' => $addresses,
            'purchaseOrderTerms' => $purchaseOrderTerms,
            'purchaseOrderDeliveryInstructions' => $purchaseOrderDeliveryInstructions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchaseOrderRequest $request)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($request->person_id) {
            $person = Person::find($request->person_id);
        }

        if ($request->organisation_name && ! $request->organisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        } elseif ($request->organisation_id) {
            $organisation = Organisation::find($request->organisation_id);
        }

        $this->purchaseOrderService->create($request, $person ?? null, $organisation ?? null);

        flash(ucfirst(trans('laravel-crm::lang.purchase_order_created')))->success()->important();

        return redirect(route('laravel-crm.purchase-orders.index'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeMultiple(StorePurchaseOrderRequest $request)
    {
        $purchaseOrders = [];

        foreach($request->purchaseOrderLines as $purchaseOrderLine) {
            if($purchaseOrderLine['organisation_id']) {
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['order_id'] = $request->order_id;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['organisation_id'] = $purchaseOrderLine['organisation_id'];
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['reference'] = $request->reference;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['currency'] = $request->currency;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['issue_date'] = $request->issue_date;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['delivery_date'] = $request->delivery_date;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['delivery_type'] = $request->delivery_type;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['delivery_instructions'] = $request->delivery_instructions;
                $purchaseOrders[$purchaseOrderLine['organisation_id']]['purchaseOrderLines'][] = $purchaseOrderLine;
            }
        }

        foreach($purchaseOrders as $organisationId => $purchaseOrder) {
            $purchaseOrderRequest = Request::create(url(route('laravel-crm.purchase-orders.create')), 'POST', $purchaseOrder);

            if($organisation = Organisation::find($purchaseOrderRequest->organisation_id)) {
                $this->purchaseOrderService->create($purchaseOrderRequest, $person ?? null, $organisation ?? null);
            }

            sleep(1);
        }

        flash(ucfirst(trans('laravel-crm::lang.purchase_orders_created')))->success()->important();

        return redirect(route('laravel-crm.purchase-orders.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->person) {
            $email = $purchaseOrder->person->getPrimaryEmail();
            $phone = $purchaseOrder->person->getPrimaryPhone();
            $address = $purchaseOrder->person->getPrimaryAddress();
        }

        if ($purchaseOrder->organisation) {
            $organisation_address = $purchaseOrder->organisation->getPrimaryAddress();
        }

        $related = $this->settingService->get('team');

        if($purchaseOrder->address) {
            $deliveryAddress = $purchaseOrder->address;
        }

        return view('laravel-crm::purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
            'deliveryAddress' => $deliveryAddress ?? null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->person) {
            $email = $purchaseOrder->person->getPrimaryEmail();
            $phone = $purchaseOrder->person->getPrimaryPhone();
        }

        if ($purchaseOrder->organisation) {
            $address = $purchaseOrder->organisation->getPrimaryAddress();
        }

        $related = $this->settingService->get('team');

        $addresses = [];
        foreach($related->addresses()->get() as $address) {
            $addresses[$address->id] = $address->address;
        }

        return view('laravel-crm::purchase-orders.edit', [
            'purchaseOrder' => $purchaseOrder,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        if ($request->person_name && ! $request->person_id) {
            $person = $this->personService->createFromRelated($request);
        } elseif ($request->person_id) {
            $person = Person::find($request->person_id);
        }

        if ($request->organisation_name && ! $request->organisation_id) {
            $organisation = $this->organisationService->createFromRelated($request);
        } elseif ($request->organisation_id) {
            $organisation = Organisation::find($request->organisation_id);
        }

        $purchaseOrder = $this->purchaseOrderService->update($request, $purchaseOrder, $person ?? null, $organisation ?? null);

        flash(ucfirst(trans('laravel-crm::lang.purchase_order_updated')))->success()->important();

        return redirect(route('laravel-crm.purchase-orders.show', $purchaseOrder));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();

        flash(ucfirst(trans('laravel-crm::lang.purchase_order_deleted')))->success()->important();

        return redirect(route('laravel-crm.purchase-orders.index'));
    }

    public function search(Request $request)
    {
        $searchValue = PurchaseOrder::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.purchase-orders.index'));
        }

        $params = PurchaseOrder::filters($request, 'search');

        $purchaseOrders = PurchaseOrder::filter($params)
            ->select(
                config('laravel-crm.db_table_prefix').'purchase_orders.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.middle_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'people.maiden_name',
                config('laravel-crm.db_table_prefix').'organisations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'purchase_orders.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organisations', config('laravel-crm.db_table_prefix').'purchase_orders.organisation_id', '=', config('laravel-crm.db_table_prefix').'organisations.id')
            ->latest()
            ->get()
            ->filter(function ($record) use ($searchValue) {
                foreach ($record->getSearchable() as $field) {
                    if (Str::contains($field, '.')) {
                        $field = explode('.', $field);

                        if(config('laravel-crm.encrypt_db_fields')) {
                            $relatedField = decrypt($record->{$field[1]});
                        } else {
                            $relatedField = $record->{$field[1]};
                        }

                        if ($record->{$field[1]} && $relatedField) {
                            if (Str::contains(strtolower($relatedField), strtolower($searchValue))) {
                                return $record;
                            }
                        }
                    } elseif ($record->{$field}) {
                        if (Str::contains(strtolower($record->{$field}), strtolower($searchValue))) {
                            return $record;
                        }
                    }
                }
            });

        return view('laravel-crm::purchase-orders.index', [
            'purchaseOrders' => $purchaseOrders,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    public function download(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->person) {
            $email = $purchaseOrder->person->getPrimaryEmail();
            $phone = $purchaseOrder->person->getPrimaryPhone();
            $address = $purchaseOrder->person->getPrimaryAddress();
        }

        if ($purchaseOrder->organisation) {
            $organisation_address = $purchaseOrder->organisation->getPrimaryAddress();
        }

        return Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::purchase-orders.pdf', [
                'purchaseOrder' => $purchaseOrder,
                'contactDetails' => $this->settingService->get('purchase_order_contact_details')->value ?? null,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organisation_address' => $organisation_address ?? null,
                'fromName' => $this->settingService->get('organisation_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->download('purchase-order-'.strtolower($purchaseOrder->xeroPurchaseOrder->number ?? $purchaseOrder->purchase_order_id).'.pdf');
    }
}
