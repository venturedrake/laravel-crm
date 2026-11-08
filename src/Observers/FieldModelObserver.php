<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\FieldModel;
use VentureDrake\LaravelCrm\Models\FieldValue;

class FieldModelObserver
{
    /**
     * Handle the fieldModel "creating" event.
     *
     * @return void
     */
    public function creating(FieldModel $fieldModel)
    {
        $fieldModel->external_id = Uuid::uuid4()->toString();
    }

    /**
     * Handle the fieldModel "created" event.
     *
     * @return void
     */
    public function created(FieldModel $fieldModel)
    {
        foreach ($fieldModel->model::all() as $model) {
            $model->fields()->create([
                'field_id' => $fieldModel->field_id,
                'value' => $fieldModel->field->default,
            ]);
        }
    }

    /**
     * Handle the fieldModel "updating" event.
     *
     * @return void
     */
    public function updating(FieldModel $fieldModel)
    {
        //
    }

    /**
     * Handle the fieldModel "updated" event.
     *
     * @return void
     */
    public function updated(FieldModel $fieldModel)
    {
        //
    }

    /**
     * Handle the fieldModel "deleting" event.
     *
     * Remove the field's values from that model, one at a time so
     * FieldValueObserver still runs.
     *
     * @return void
     */
    public function deleting(FieldModel $fieldModel)
    {
        FieldValue::where('field_id', $fieldModel->field_id)
            ->where('field_valueable_type', (new $fieldModel->model)->getMorphClass())
            ->get()
            ->each
            ->delete();
    }

    /**
     * Handle the fieldModel "deleted" event.
     *
     * @return void
     */
    public function deleted(FieldModel $fieldModel)
    {
        //
    }

    /**
     * Handle the fieldModel "restored" event.
     *
     * @return void
     */
    public function restored(FieldModel $fieldModel)
    {
        //
    }

    /**
     * Handle the fieldModel "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(FieldModel $fieldModel)
    {
        //
    }
}
