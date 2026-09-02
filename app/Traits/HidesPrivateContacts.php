<?php

namespace App\Traits;

use Horsefly\JobSource;
use Horsefly\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

trait HidesPrivateContacts
{
    protected $privateDataContextCache = false;

    /**
     * Exclude contacts hidden by hide_private_data for users without show-private-data.
     * Matches the string (e.g. hayaibu) in contact fields and matching job_source_id values (e.g. 10).
     */
    protected function excludePrivateContacts($query)
    {
        $context = $this->privateDataContext();

        if ($context === null) {
            return $query;
        }

        return $query->where(function ($q) use ($context) {
            foreach ($context['values'] as $hideValue) {
                $q->where(function ($fieldGroup) use ($hideValue) {
                    $fieldGroup
                        ->where(function ($nameCheck) use ($hideValue) {
                            $nameCheck->whereNull('contacts.contact_name')
                                ->orWhere('contacts.contact_name', 'NOT LIKE', '%' . $hideValue . '%');
                        })
                        ->where(function ($emailCheck) use ($hideValue) {
                            $emailCheck->whereNull('contacts.contact_email')
                                ->orWhere('contacts.contact_email', 'NOT LIKE', '%' . $hideValue . '%');
                        })
                        ->where(function ($noteCheck) use ($hideValue) {
                            $noteCheck->whereNull('contacts.contact_note')
                                ->orWhere('contacts.contact_note', 'NOT LIKE', '%' . $hideValue . '%');
                        });
                });
            }

            if ($context['sourceIds'] !== []) {
                $q->where(function ($source) use ($context) {
                    $source->whereNotIn('contacts.job_source_id', $context['sourceIds'])
                        ->orWhereNull('contacts.job_source_id');
                });
            }
        });
    }

    /**
     * Join only visible unit contacts, using a string compare so the contactable_id index is used.
     * Private contacts (hide_private_data / job source) are excluded in SQL so the export does not
     * hydrate them in PHP.
     */
    protected function constrainVisibleUnitContacts($join): void
    {
        $join->on('contacts.contactable_id', '=', DB::raw('CAST(units.id AS CHAR)'))
            ->where('contacts.contactable_type', '=', 'Horsefly\\Unit');

        $context = $this->privateDataContext();
        if ($context === null) {
            return;
        }

        foreach ($context['values'] as $hideValue) {
            $like = '%' . $hideValue . '%';
            $join->whereRaw('(contacts.contact_name IS NULL OR contacts.contact_name NOT LIKE ?)', [$like]);
            $join->whereRaw('(contacts.contact_email IS NULL OR contacts.contact_email NOT LIKE ?)', [$like]);
            $join->whereRaw('(contacts.contact_note IS NULL OR contacts.contact_note NOT LIKE ?)', [$like]);
        }

        if ($context['sourceIds'] !== []) {
            $placeholders = implode(',', array_fill(0, count($context['sourceIds']), '?'));
            $join->whereRaw(
                "(contacts.job_source_id IS NULL OR contacts.job_source_id NOT IN ({$placeholders}))",
                $context['sourceIds']
            );
        }
    }

    /**
     * Hide sales whose job_source_id matches hide_private_data (same as the sales list).
     */
    protected function excludeHiddenSaleSources($query)
    {
        $context = $this->privateDataContext();

        if ($context === null || $context['sourceIds'] === []) {
            return $query;
        }

        return $query->where(function ($q) use ($context) {
            $q->whereNotIn('sales.job_source_id', $context['sourceIds'])
                ->orWhereNull('sales.job_source_id');
        });
    }

    /**
     * Blank contact fields when the joined contact is private.
     * Used after fetch so LEFT JOIN rows stay, without leaking hayaibu / source id 10 data.
     */
    protected function hidePrivateContactData($item)
    {
        if (!$this->isPrivateContactRow($item)) {
            return $item;
        }

        $item->contact_name = null;
        $item->contact_email = null;
        $item->contact_phone = null;
        $item->contact_landline = null;
        $item->contact_note = null;

        return $item;
    }

    protected function isPrivateContactRow($item): bool
    {
        $context = $this->privateDataContext();

        if ($context === null) {
            return false;
        }

        $contactSourceId = $item->contact_job_source_id ?? null;
        if ($contactSourceId !== null && $contactSourceId !== '' && $context['sourceIds'] !== []) {
            if (in_array((int) $contactSourceId, $context['sourceIds'], true)) {
                return true;
            }
        }

        foreach ($context['values'] as $hideValue) {
            foreach ([$item->contact_email ?? null, $item->contact_name ?? null, $item->contact_note ?? null] as $haystack) {
                if ($haystack !== null && $haystack !== '' && stripos((string) $haystack, $hideValue) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Job source IDs hidden by hide_private_data. Empty when the user may see private data.
     */
    protected function hiddenPrivateSourceIds(): array
    {
        $context = $this->privateDataContext();

        return $context === null ? [] : $context['sourceIds'];
    }

    /**
     * @return array{values: array, sourceIds: array}|null
     */
    protected function privateDataContext(): ?array
    {
        if ($this->privateDataContextCache !== false) {
            return $this->privateDataContextCache;
        }

        if (Gate::allows('show-private-data')) {
            return $this->privateDataContextCache = null;
        }

        $values = array_values(array_filter(
            array_map('trim', explode(',', Setting::where('key', 'hide_private_data')->value('value') ?? ''))
        ));

        if ($values === []) {
            return $this->privateDataContextCache = null;
        }

        $sourceIds = JobSource::query()
            ->where(function ($q) use ($values) {
                foreach ($values as $hideName) {
                    $q->orWhere('name', 'LIKE', '%' . $hideName . '%');
                    if (ctype_digit((string) $hideName)) {
                        $q->orWhere('id', (int) $hideName);
                    }
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($values as $hideName) {
            if (ctype_digit((string) $hideName)) {
                $sourceIds[] = (int) $hideName;
            }
        }

        return $this->privateDataContextCache = [
            'values' => $values,
            'sourceIds' => array_values(array_unique($sourceIds)),
        ];
    }
}
