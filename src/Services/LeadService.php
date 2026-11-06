<?php

namespace VentureDrake\LaravelCrm\Services;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Repositories\LeadRepository;

class LeadService
{
    /**
     * The stage names a converted lead moves to, for pipelines that have one.
     */
    private const CONVERTED_STAGES = ['Converted', 'Closed Won', 'Won'];

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * LeadService constructor.
     */
    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    public function create($request, $person = null, $organization = null, $client = null)
    {
        $lead = Lead::create([
            'external_id' => Uuid::uuid4()->toString(),
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'lead_status_id' => 1,
            'lead_source_id' => $request->lead_source_id ?? null,
            'user_owner_id' => $request->user_owner_id,
            'pipeline_id' => optional(PipelineStage::find($request->pipeline_stage_id))->pipeline?->id,
            'pipeline_stage_id' => $request->pipeline_stage_id ?? null,
        ]);

        $lead->labels()->sync($request->labels ?? []);

        return $lead;
    }

    /**
     * A pipeline without a converted or won stage keeps the lead where it is.
     */
    public function markConverted(Lead $lead): void
    {
        $pipeline = $lead->pipeline ?? Pipeline::where('model', Lead::class)->first();
        $stageId = $pipeline?->pipelineStages()->whereIn('name', self::CONVERTED_STAGES)->value('id');

        $lead->update(array_filter([
            'converted_at' => Carbon::now(),
            'pipeline_stage_id' => $stageId,
        ]));
    }

    /**
     * A lead whose deals have all gone can be converted again. Its stage stays
     * as it is, because nothing records where the lead was before converting.
     */
    public function undoConversionWithoutDeals(Lead $lead): void
    {
        if ($lead->isConverted() && ! $lead->deals()->exists()) {
            $lead->update(['converted_at' => null]);
        }
    }

    public function update($request, Lead $lead, $person = null, $organization = null, $client = null)
    {
        $lead->update([
            'person_id' => $person->id ?? null,
            'organization_id' => $organization->id ?? null,
            'client_id' => $client->id ?? null,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'lead_source_id' => $request->lead_source_id ?? null,
            'user_owner_id' => $request->user_owner_id,
            'pipeline_id' => optional(PipelineStage::find($request->pipeline_stage_id))->pipeline?->id,
            'pipeline_stage_id' => $request->pipeline_stage_id ?? null,
        ]);

        $lead->labels()->sync($request->labels ?? []);

        return $lead;
    }
}
