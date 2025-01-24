<x-crm::app-layout>
    <x-slot name="header">
        <div class="mb-10">
            <div class="flex flex-wrap gap-5 justify-between items-center">
                <div>
                    <div class="text-4xl font-extrabold ">
                        {{ ucfirst(__('laravel-crm::lang.dashboard')) }}
                    </div>
                </div>
                <div class="flex items-center gap-3 ">
                    <div>
                        <div class="relative flex-1">
                            <select id="marya82de51370451601b26ad5ceef42d49bperiod" wire:model.live="period" class="select select-primary w-full font-normal pl-10">
                                <option value="-7 days">Last 7 days</option>
                                <option value="-15 days">Last 15 days</option>
                                <option value="-30 days">Last 30 days</option>
                            </select>
                            <svg class="inline w-5 h-5 absolute pointer-events-none top-1/2 -translate-y-1/2 left-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <div class="h-0.5 -mt-9 mb-9">
                <progress class="progress progress-primary w-full h-0.5 dark:h-1" wire:loading=""></progress>
            </div>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-4 gap-5 lg:gap-8">
        <div class="bg-base-100 rounded-lg px-5 py-4  w-full shadow truncate text-ellipsis">
            <div class="flex items-center gap-3">
                <!--[if BLOCK]><![endif]-->                <div class="  text-primary">
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                    <svg class="inline w-9 h-9" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"></path>
                    </svg>
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->                </div>
                <!--[if ENDBLOCK]><![endif]-->

                <div class="text-left">
                    <!--[if BLOCK]><![endif]-->                    <div class="text-xs text-gray-500 whitespace-nowrap">Gross</div>
                    <!--[if ENDBLOCK]><![endif]-->

                    <div class="font-black text-xl">$150,407.07</div>

                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                </div>
            </div>
        </div>        <div class="bg-base-100 rounded-lg px-5 py-4  w-full shadow">
            <div class="flex items-center gap-3">
                <!--[if BLOCK]><![endif]-->                <div class="  text-primary">
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                    <svg class="inline w-9 h-9" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"></path>
                    </svg>
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->                </div>
                <!--[if ENDBLOCK]><![endif]-->

                <div class="text-left">
                    <!--[if BLOCK]><![endif]-->                    <div class="text-xs text-gray-500 whitespace-nowrap">Orders</div>
                    <!--[if ENDBLOCK]><![endif]-->

                    <div class="font-black text-xl">53</div>

                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                </div>
            </div>
        </div>        <div class="bg-base-100 rounded-lg px-5 py-4  w-full shadow">
            <div class="flex items-center gap-3">
                <!--[if BLOCK]><![endif]-->                <div class="  text-primary">
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                    <svg class="inline w-9 h-9" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"></path>
                    </svg>
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->                </div>
                <!--[if ENDBLOCK]><![endif]-->

                <div class="text-left">
                    <!--[if BLOCK]><![endif]-->                    <div class="text-xs text-gray-500 whitespace-nowrap">New customers</div>
                    <!--[if ENDBLOCK]><![endif]-->

                    <div class="font-black text-xl">563</div>

                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                </div>
            </div>
        </div>        <div class="bg-base-100 rounded-lg px-5 py-4  w-full shadow">
            <div class="flex items-center gap-3">
                <!--[if BLOCK]><![endif]-->                <div class="  !text-pink-500">
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                    <svg class="inline w-9 h-9" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"></path>
                    </svg>
                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->                </div>
                <!--[if ENDBLOCK]><![endif]-->

                <div class="text-left">
                    <!--[if BLOCK]><![endif]-->                    <div class="text-xs text-gray-500 whitespace-nowrap">Built with</div>
                    <!--[if ENDBLOCK]><![endif]-->

                    <div class="font-black text-xl">maryUI</div>

                    <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                </div>
            </div>
        </div>    </div>
</x-crm::app-layout>

{{--@extends('laravel-crm::layouts.app')--}}

{{--
@section('content')

    <div class="container-content">
        <div class="row">
            @hasleadsenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.leads')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalLeadsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_leads')) }}</small>
                    </div>
                </div>
            </div>
            @endhasleadsenabled
            @hasdealsenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.deals')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalDealsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_deals')) }}</small>
                    </div>
                </div>
            </div>
            @endhasdealsenabled
            @hasquotesenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.quotes')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalQuotesCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_quotes')) }}</small>
                    </div>
                </div>
            </div>
            @endhasquotesenabled
            @hasordersenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.orders')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalOrdersCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_orders')) }}</small>
                    </div>
                </div>
            </div>
            @endhasordersenabled
            @hasinvoicesenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.invoices')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalInvoicesCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_invoices')) }}</small>
                    </div>
                </div>
            </div>
            @endhasinvoicesenabled
            @hasdeliveriesenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.deliveries')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalDeliveriesCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_deliveries')) }}</small>
                    </div>
                </div>
            </div>
            @endhasdeliveriesenabled
            @haspurchaseordersenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.purchase_orders')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalPurchaseOrdersCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_purchase_orders')) }}</small>
                    </div>
                </div>
            </div>
            @endhaspurchaseordersenabled
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.clients')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalClientsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_clients')) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.people')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalPeopleCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_people')) }}</small>
                    </div>
                </div>
            </div>
            <div class="col-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title float-left m-0">{{ ucfirst(__('laravel-crm::lang.organizations')) }}</h4>
                    </div>
                    <div class="card-body">
                        <h2>{{ $totalOrganisationsCount ?? 0 }}</h2>
                        <small>{{ ucfirst(__('laravel-crm::lang.total_organizations')) }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title m-0">{{ ucfirst(__('laravel-crm::lang.created_last_14_days')) }}</h4>
                </div>
                <div class="card-body">
                    <canvas id="createdLast14Days" style="height:500px; width:100%" data-chart="{{ $createdLast14Days }}" data-label-leads="{{ ucfirst(__('laravel-crm::lang.leads')) }}" data-label-deals="{{ ucfirst(__('laravel-crm::lang.deals')) }}"></canvas>
                </div>
            </div>
        </div>
        <div class="col-sm mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title m-0">{{ ucfirst(__('laravel-crm::lang.users_online')) }}</h4>
                </div>
                <div class="card-body">
                    @foreach($usersOnline as $user)
                        <div class="media {{ (!$loop->last) ? 'mb-3' : null }}">
                            <span class="fa fa-user fa-2x mr-3 border rounded-circle p-2" aria-hidden="true"></span>
                            <div class="media-body">
                                <h4 class="mt-1 mb-0">{{ $user->name }}</h4>
                                <p class="mb-0">{{  \Carbon\Carbon::parse($user->last_online_at)->diffForHumans() }}.</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
@endsection--}}
