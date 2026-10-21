<?php

namespace App\Traits;

use App\Observers\ActionObserver;
use Horsefly\ModuleNote;
use Horsefly\Office;
use Horsefly\Sale;
use Horsefly\SaleNote;
use Horsefly\Unit;

/**
 * Saves a blacklist message through each module's normal notes flow, so it shows
 * up in the notes column, notes history and audit log like any other note.
 */
trait RecordsBlacklistNotes
{
    /** Same steps as HeadOfficeController::storeHeadOfficeShortNotes(). */
    protected function recordOfficeNote(Office $office, string $note, int $userId): void
    {
        $office->update(['office_notes' => $note]);
        $this->replaceActiveModuleNote($office->id, 'Horsefly\Office', $note, $userId);

        (new ActionObserver())->customOfficeAudit($office, 'office_notes');
    }

    /** Same steps as UnitController::storeUnitShortNotes(). */
    protected function recordUnitNote(Unit $unit, string $note, int $userId): void
    {
        $unit->update(['unit_notes' => $note]);
        $this->replaceActiveModuleNote($unit->id, 'Horsefly\Unit', $note, $userId);

        (new ActionObserver())->customUnitAudit($unit, 'unit_notes');
    }

    /** Same steps as SaleController::storeSaleNotes(). */
    protected function recordSaleNote(Sale $sale, string $note, int $userId): void
    {
        $sale->update(['sale_notes' => $note]);

        SaleNote::where('sale_id', $sale->id)->update(['status' => 0]);

        $saleNote = SaleNote::create([
            'sale_id' => $sale->id,
            'sale_note' => $note,
            'user_id' => $userId,
        ]);
        $saleNote->update(['sales_notes_uid' => md5($saleNote->id)]);

        (new ActionObserver())->customSaleAudit($sale, 'sale_notes');

        $this->replaceActiveModuleNote($sale->id, 'Horsefly\Sale', $note, $userId);
    }

    private function replaceActiveModuleNote(int $modelId, string $modelType, string $details, int $userId): void
    {
        ModuleNote::where('module_noteable_id', $modelId)
            ->where('module_noteable_type', $modelType)
            ->where('status', 1)
            ->update(['status' => 0]);

        $moduleNote = ModuleNote::create([
            'details' => $details,
            'module_noteable_id' => $modelId,
            'module_noteable_type' => $modelType,
            'user_id' => $userId,
            'status' => 1,
        ]);

        $moduleNote->update(['module_note_uid' => md5($moduleNote->id)]);
    }
}
