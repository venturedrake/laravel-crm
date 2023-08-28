<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StoreOrderRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateOrderRequest;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Client;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\DeliveryService;
use VentureDrake\LaravelCrm\Services\InvoiceService;
use VentureDrake\LaravelCrm\Services\OrderService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\SettingService;

class OrderController extends Controller
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var PersonService
     */
    private $personService;

    /**
     * @var OrganisationService
     */
    private $organisationService;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var SettingService
     */
    private $settingService;

    /**
     * @var DeliveryService
     */
    private $deliveryService;

    public function __construct(OrderService $orderService, PersonService $personService, OrganisationService $organisationService, InvoiceService $invoiceService, SettingService $settingService, DeliveryService $deliveryService)
    {
        $this->orderService = $orderService;
        $this->personService = $personService;
        $this->organisationService = $organisationService;
        $this->invoiceService = $invoiceService;
        $this->settingService = $settingService;
        $this->deliveryService = $deliveryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Order::resetSearchValue($request);
        $params = Order::filters($request);

        if (Order::filter($params)->get()->count() < 30) {
            $orders = Order::filter($params)->latest()->get();
        } else {
            $orders = Order::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::orders.index', [
            'orders' => $orders,
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
            case 'client':
                $client = Client::find($request->id);

                break;

            case 'organisation':
                $organisation = Organisation::find($request->id);

                break;

            case 'person':
                $person = Person::find($request->id);

                break;

            case 'quote':
                $quote = Quote::find($request->id);
                $client = $quote->client;
                $organisation = $quote->organisation;
                $person = $quote->person;

                $addressIds = [];

                if ($address = $quote->organisation->getBillingAddress()) {
                    $addressIds[] = $address->id;
                }

                if ($address = $quote->organisation->getShippingAddress()) {
                    $addressIds[] = $address->id;
                }

                $addresses = Address::whereIn('id', $addressIds)->get();

                break;
        }

        $orderTerms = $this->settingService->get('order_terms');

        return view('laravel-crm::orders.create', [
            'quote' => $quote ?? null,
            'client' => $client ?? null,
            'organisation' => $organisation ?? null,
            'person' => $person ?? null,
            'addresses' => $addresses ?? null,
            'prefix' => $this->settingService->get('order_prefix'),
            'number' => (Order::latest()->first()->number ?? 1000) + 1,
            'orderTerms' => $orderTerms,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderRequest $request)
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

        if ($request->client_name && ! $request->client_id) {
            $client = Client::create([
                'name' => $request->client_name,
                'user_owner_id' => $request->user_owner_id,
            ]);
        } else {
            $client = Client::find($request->client_id);
        }

        if (isset($client)) {
            if (isset($organisation)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $organisation->getMorphClass(),
                    'entityable_id' => $organisation->id,
                ]);
            }

            if (isset($person)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $this->orderService->create($request, $person ?? null, $organisation ?? null, $client ?? null);

        flash(ucfirst(trans('laravel-crm::lang.order_stored')))->success()->important();

        return redirect(route('laravel-crm.orders.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        if ($order->person) {
            $email = $order->person->getPrimaryEmail();
            $phone = $order->person->getPrimaryPhone();
        }

        if ($order->organisation) {
            $address = $order->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::orders.show', [
            'order' => $order,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'organisation_address' => $address ?? null,
            'addresses' => $order->addresses,
            'invoices' => $order->invoices()->latest()->get(),
            'deliveries' => $order->deliveries()->latest()->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        if ($order->person) {
            $email = $order->person->getPrimaryEmail();
            $phone = $order->person->getPrimaryPhone();
        }

        if ($order->organisation) {
            $address = $order->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::orders.edit', [
            'order' => $order,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'organisation_address' => $address ?? null,
            'addresses' => $order->addresses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrderRequest $request, Order $order)
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

        if ($request->client_name && ! $request->client_id) {
            $client = Client::create([
                'name' => $request->client_name,
                'user_owner_id' => $request->user_owner_id,
            ]);
        } else {
            $client = Client::find($request->client_id);
        }

        if (isset($client)) {
            if (isset($organisation)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $organisation->getMorphClass(),
                    'entityable_id' => $organisation->id,
                ]);
            }

            if (isset($person)) {
                $client->contacts()->firstOrCreate([
                    'entityable_type' => $person->getMorphClass(),
                    'entityable_id' => $person->id,
                ]);
            }
        }

        $order = $this->orderService->update($request, $order, $person ?? null, $organisation ?? null, $client ?? null);

        flash(ucfirst(trans('laravel-crm::lang.order_updated')))->success()->important();

        return redirect(route('laravel-crm.orders.show', $order));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        $order->delete();

        flash(ucfirst(trans('laravel-crm::lang.order_deleted')))->success()->important();

        return redirect(route('laravel-crm.orders.index'));
    }

    public function search(Request $request)
    {
        $searchValue = Order::searchValue($request);

        if (! $searchValue || trim($searchValue) == '') {
            return redirect(route('laravel-crm.orders.index'));
        }

        $params = Order::filters($request, 'search');

        $orders = Order::filter($params)
            ->select(
                config('laravel-crm.db_table_prefix').'orders.*',
                config('laravel-crm.db_table_prefix').'people.first_name',
                config('laravel-crm.db_table_prefix').'people.middle_name',
                config('laravel-crm.db_table_prefix').'people.last_name',
                config('laravel-crm.db_table_prefix').'people.maiden_name',
                config('laravel-crm.db_table_prefix').'organisations.name'
            )
            ->leftJoin(config('laravel-crm.db_table_prefix').'people', config('laravel-crm.db_table_prefix').'orders.person_id', '=', config('laravel-crm.db_table_prefix').'people.id')
            ->leftJoin(config('laravel-crm.db_table_prefix').'organisations', config('laravel-crm.db_table_prefix').'orders.organisation_id', '=', config('laravel-crm.db_table_prefix').'organisations.id')
            ->get()
            ->filter(function ($record) use ($searchValue) {
                foreach ($record->getSearchable() as $field) {
                    if (Str::contains($field, '.')) {
                        $field = explode('.', $field);
                        if ($record->{$field[1]} && $descryptedField = decrypt($record->{$field[1]})) {
                            if (Str::contains(strtolower($descryptedField), strtolower($searchValue))) {
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

        return view('laravel-crm::orders.index', [
            'orders' => $orders,
            'searchValue' => $searchValue ?? null,
        ]);
    }

    /**
     * Create an order from the quote
     *
     * @param  Order  $order
     * @return \Illuminate\Http\Response
     */
    public function createDelivery(Order $order)
    {
        $request = new \Illuminate\Http\Request();
        $products = [];

        foreach ($order->orderProducts as $orderProduct) {
            $products[] = [
                'order_product_id' => $orderProduct->id,
            ];
        }

        $request->replace([
            'order_id' => $order->id,
            'user_owner_id' => $order->user_owner_id,
            'products' => $products,
        ]);

        $delivery = $this->deliveryService->create($request, $order);

        if ($address = $order->getShippingAddress()) {
            $shippingAddress = $address;
        } elseif ($address = $order->organisation->getShippingAddress()) {
            $shippingAddress = $address;
        } elseif ($address = $order->organisation->getPrimaryAddress()) {
            $shippingAddress = $address;
        }

        if (isset($shippingAddress)) {
            $delivery->addresses()->create([
                'external_id' => Uuid::uuid4()->toString(),
                'address_type_id' => 6,
                'address' => $shippingAddress->address,
                'name' => $shippingAddress->name,
                'contact' => $shippingAddress->contact,
                'phone' => $shippingAddress->phone,
                'line1' => $shippingAddress->line1,
                'line2' => $shippingAddress->line2,
                'line3' => $shippingAddress->line3,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'code' => $shippingAddress->code,
                'country' => $shippingAddress->country,
                'primary' => $shippingAddress->primary,
            ]);
        }

        flash(ucfirst(trans('laravel-crm::lang.delivery_created_from_order')))->success()->important();

        return back();
    }

    public function download(Order $order)
    {
        if ($order->person) {
            $email = $order->person->getPrimaryEmail();
            $phone = $order->person->getPrimaryPhone();
            $address = $order->person->getPrimaryAddress();
        }

        if ($order->organisation) {
            $organisation_address = $order->organisation->getPrimaryAddress();
        }

        /*$pdfLocation = 'laravel-crm/'.strtolower(class_basename($quote)).'/'.$quote->id.'/';

        if(!File::exists($pdfLocation)){
            Storage::makeDirectory($pdfLocation);
        }*/

        /*return view('laravel-crm::orders.pdf', [
            'order' => $order,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'address' => $address ?? null,
            'organisation_address' => $organisation_address ?? null,
            'fromName' => $this->settingService->get('organisation_name')->value ?? null,
            'logo' => $this->settingService->get('logo_file')->value ?? null,
        ]);*/

        return Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::orders.pdf', [
                'order' => $order,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $address ?? null,
                'organisation_address' => $organisation_address ?? null,
                'fromName' => $this->settingService->get('organisation_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->download('order-'.strtolower($order->order_id).'.pdf');
    }
}
