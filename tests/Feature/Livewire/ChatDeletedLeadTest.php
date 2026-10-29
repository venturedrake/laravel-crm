<?php

use Livewire\Livewire;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Livewire\Chat\ChatShow;
use VentureDrake\LaravelCrm\Models\ChatConversation;
use VentureDrake\LaravelCrm\Models\ChatVisitor;
use VentureDrake\LaravelCrm\Models\ChatWidget;
use VentureDrake\LaravelCrm\Models\Lead;

function chatConversationWithLead(): ChatConversation
{
    $widget = ChatWidget::create([
        'external_id' => Uuid::uuid4()->toString(),
        'public_key' => Uuid::uuid4()->toString(),
        'name' => 'Widget',
    ]);

    $visitor = ChatVisitor::create([
        'external_id' => Uuid::uuid4()->toString(),
        'chat_widget_id' => $widget->id,
        'visitor_token' => Uuid::uuid4()->toString(),
        'name' => 'Jane Visitor',
    ]);

    $lead = Lead::create([
        'external_id' => Uuid::uuid4()->toString(),
        'title' => 'Chat lead',
        'lead_status_id' => 1,
    ]);

    return ChatConversation::create([
        'external_id' => Uuid::uuid4()->toString(),
        'chat_widget_id' => $widget->id,
        'chat_visitor_id' => $visitor->id,
        'lead_id' => $lead->id,
        'status' => 'open',
    ]);
}

it('renders the chat show page with a link to the converted lead', function () {
    $this->actingAsUserWithPermissions(['view crm chat']);
    $conversation = chatConversationWithLead();

    Livewire::test(ChatShow::class, ['conversation' => $conversation])
        ->assertOk()
        ->assertSee(route('laravel-crm.leads.show', $conversation->lead));
});

it('renders the chat show page when the converted lead has been deleted', function () {
    $this->actingAsUserWithPermissions(['view crm chat']);
    $conversation = chatConversationWithLead();
    $conversation->lead->delete();

    Livewire::test(ChatShow::class, ['conversation' => $conversation->fresh()])
        ->assertOk()
        ->assertDontSee(ucfirst(__('laravel-crm::lang.view_lead')));
});

it('converts a conversation again once its lead has been deleted', function () {
    $this->actingAsUserWithPermissions(['view crm chat', 'create crm leads', 'create crm people']);
    $conversation = chatConversationWithLead();
    $deletedLeadId = $conversation->lead_id;
    $conversation->lead->delete();

    Livewire::test(ChatShow::class, ['conversation' => $conversation->fresh()])
        ->call('convertToLead');

    expect($conversation->fresh()->lead_id)->not->toBe($deletedLeadId)
        ->and($conversation->fresh()->lead)->not->toBeNull();
});
