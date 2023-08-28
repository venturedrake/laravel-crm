<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use VentureDrake\LaravelCrm\Http\Requests\StoreDeliveryRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateDeliveryRequest;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\Organisation;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Services\DeliveryService;
use VentureDrake\LaravelCrm\Services\OrganisationService;
use VentureDrake\LaravelCrm\Services\PersonService;
use VentureDrake\LaravelCrm\Services\SettingService;

class DeliveryController extends Controller
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
     * @var DeliveryService
     */
    private $deliveryService;

    public function __construct(SettingService $settingService, PersonService $personService, OrganisationService $organisationService, DeliveryService $deliveryService)
    {
        $this->settingService = $settingService;
        $this->personService = $personService;
        $this->organisationService = $organisationService;
        $this->deliveryService = $deliveryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Delivery::resetSearchValue($request);
        $params = Delivery::filters($request);

        if (Delivery::filter($params)->get()->count() < 30) {
            $deliveries = Delivery::filter($params)->latest()->get();
        } else {
            $deliveries = Delivery::filter($params)->latest()->paginate(30);
        }

        return view('laravel-crm::deliveries.index', [
            'deliveries' => $deliveries,
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
                $client = $order->client;
                $person = $order->person;
                $organisation = $order->organisation;

                $addressIds = [];

                if ($address = $order->getShippingAddress()) {
                    $addressIds[] = $address->id;
                } elseif($address = $order->organisation->getShippingAddress()) {
                    $addressIds[] = $address->id;
                }

                $addresses = Address::whereIn('id', $addressIds)->get();

                break;
        }

        return view('laravel-crm::deliveries.create', [
            'client' => $client ?? null,
            'person' => $person ?? null,
            'organisation' => $organisation ?? null,
            'order' => $order ?? null,
            'addresses' => $addresses ?? null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDeliveryRequest $request)
    {
        $this->deliveryService->create($request);

        flash(ucfirst(trans('laravel-crm::lang.delivery_created')))->success()->important();

        return redirect(route('laravel-crm.deliveries.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Delivery $delivery)
    {
        if ($delivery->person) {
            $email = $delivery->person->getPrimaryEmail();
            $phone = $delivery->person->getPrimaryPhone();
        }

        if ($delivery->organisation) {
            $organisation_address = $delivery->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::deliveries.show', [
            'delivery' => $delivery,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'organisation_address' => $organisation_address ?? null,
            'addresses' => $delivery->addresses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Delivery $delivery)
    {
        if ($delivery->person) {
            $email = $delivery->person->getPrimaryEmail();
            $phone = $delivery->person->getPrimaryPhone();
        }

        if ($delivery->organisation) {
            $address = $delivery->organisation->getPrimaryAddress();
        }

        return view('laravel-crm::deliveries.edit', [
            'delivery' => $delivery,
            'email' => $email ?? null,
            'phone' => $phone ?? null,
            'organisation_address' => $address ?? null,
            'addresses' => $delivery->addresses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDeliveryRequest $request, Delivery $delivery)
    {
        $delivery = $this->deliveryService->update($request, $delivery);

        flash(ucfirst(trans('laravel-crm::lang.delivery_updated')))->success()->important();

        return redirect(route('laravel-crm.deliveries.show', $delivery));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Delivery $delivery)
    {
        $delivery->delete();

        flash(ucfirst(trans('laravel-crm::lang.delivery_deleted')))->success()->important();

        return redirect(route('laravel-crm.deliveries.index'));
    }

    public function download(Delivery $delivery)
    {
        if ($person = $delivery->order->person) {
            $email = $person->getPrimaryEmail();
            $phone = $person->getPrimaryPhone();
        }

        if ($organisation = $delivery->order->organisation) {
            $organisation_address = $organisation->getPrimaryAddress();
        }

        return Pdf::setOption([
            'fontDir' => public_path('vendor/laravel-crm/fonts'),
        ])
            ->loadView('laravel-crm::deliveries.pdf', [
                'delivery' => $delivery,
                'order' => $delivery->order,
                'email' => $email ?? null,
                'phone' => $phone ?? null,
                'address' => $delivery->getShippingAddress() ?? null,
                'organisation_address' => $delivery->order->getShippingAddress() ?? $organisation_address ?? null,
                'fromName' => $this->settingService->get('organisation_name')->value ?? null,
                'logo' => $this->settingService->get('logo_file')->value ?? null,
            ])->download('delivery-'.strtolower($delivery->delivery_id).'.pdf');
    }
}
