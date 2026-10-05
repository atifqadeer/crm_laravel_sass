<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A sales column (timing, experience, benefits, qualification) that the sale form
 * composes from selectable options into "<prefix> A, B and C.".
 * Managed from Administrator > Sale Requirements; defaults live in config/sale_requirements.php.
 */
class SaleRequirementField extends Model
{
    public const CACHE_KEY = 'sale_requirements.form';

    protected $table = 'sale_requirement_fields';
    protected $fillable = [
        'key',
        'label',
        'icon',
        'hint',
        'prefix',
        'connector',
        'is_required',
        'show_hours',
    ];
    protected $casts = [
        'is_required' => 'boolean',
        'show_hours' => 'boolean',
    ];

    public function groups()
    {
        return $this->hasMany(SaleRequirementGroup::class, 'field_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Picker definitions for the sale form, keyed by sales column. Only active options are included.
     * Falls back to the config defaults when the tables are not migrated yet.
     */
    public static function forForm(): array
    {
        if (!Schema::hasTable('sale_requirement_fields')) {
            return static::defaultsFromConfig();
        }

        return Cache::rememberForever(self::CACHE_KEY, function () {
            $fields = static::with('groups.options')->orderBy('id')->get();
            if ($fields->isEmpty()) {
                return static::defaultsFromConfig();
            }

            return $fields->mapWithKeys(fn (self $field) => [$field->key => $field->toFormArray()])->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function toFormArray(): array
    {
        $lead = null;
        $groups = [];

        foreach ($this->groups as $group) {
            $options = $group->options->where('is_active', true);
            // A number-only group (e.g. "48 hours in week") is shown even without chips
            if ($options->isEmpty() && !($group->has_quantity && !$group->is_single)) {
                continue;
            }

            if ($group->is_single && $lead === null) {
                $lead = [
                    'title' => $group->title,
                    'options' => $options->map(fn ($option) => [
                        'label' => $option->label,
                        'text' => $option->text ?: $option->label,
                        'connector' => $option->connector,
                        'suffix' => $option->suffix,
                    ])->values()->all(),
                ];
                continue;
            }

            $groups[] = [
                'title' => $group->title,
                'prefix' => $group->prefix,
                'quantity' => $group->has_quantity,
                'options' => $options->pluck('label')->values()->all(),
            ];
        }

        return [
            'label' => $this->label,
            'icon' => $this->icon ?: 'solar:checklist-minimalistic-bold-duotone',
            'hint' => $this->hint,
            'prefix' => $this->prefix,
            'connector' => $this->connector,
            'required' => $this->is_required,
            'hours' => $this->show_hours,
            'lead' => $lead,
            'groups' => $groups,
        ];
    }

    /**
     * config/sale_requirements.php normalised to the forForm() shape.
     */
    public static function defaultsFromConfig(): array
    {
        $defaults = [];
        foreach (config('sale_requirements', []) as $key => $field) {
            $defaults[$key] = [
                'label' => $field['label'],
                'icon' => $field['icon'] ?? null,
                'hint' => $field['hint'] ?? null,
                'prefix' => $field['prefix'] ?? null,
                'connector' => $field['connector'] ?? null,
                'required' => $field['required'] ?? false,
                'hours' => $field['hours'] ?? false,
                'lead' => isset($field['lead']) ? [
                    'title' => $field['lead']['title'],
                    'options' => array_map(fn ($option) => [
                        'label' => $option['label'],
                        'text' => $option['text'] ?? $option['label'],
                        'connector' => $option['connector'] ?? null,
                        'suffix' => $option['suffix'] ?? null,
                    ], $field['lead']['options']),
                ] : null,
                'groups' => collect($field['groups'] ?? [])
                    ->map(fn ($options, $title) => ['title' => $title, 'prefix' => null, 'quantity' => false, 'options' => $options])
                    ->values()->all(),
            ];
        }

        return $defaults;
    }

    /**
     * Create any fields missing from the database using the config defaults.
     */
    public static function seedDefaults(): void
    {
        foreach (array_keys(static::defaultsFromConfig()) as $key) {
            if (!static::where('key', $key)->exists()) {
                static::restoreDefaults($key);
            }
        }
    }

    /**
     * Replace one field's settings, groups and options with the config defaults.
     */
    public static function restoreDefaults(string $key): ?self
    {
        $default = static::defaultsFromConfig()[$key] ?? null;
        if ($default === null) {
            return null;
        }

        $field = DB::transaction(function () use ($key, $default) {
            $field = static::updateOrCreate(['key' => $key], [
                'label' => $default['label'],
                'icon' => $default['icon'],
                'hint' => $default['hint'],
                'prefix' => $default['prefix'],
                'connector' => $default['connector'],
                'is_required' => $default['required'],
                'show_hours' => $default['hours'],
            ]);
            $field->groups()->delete();

            $sort = 0;
            if ($default['lead']) {
                $group = $field->groups()->create([
                    'title' => $default['lead']['title'],
                    'is_single' => true,
                    'sort_order' => $sort++,
                ]);
                foreach ($default['lead']['options'] as $i => $option) {
                    $group->options()->create($option + ['sort_order' => $i]);
                }
            }

            foreach ($default['groups'] as $groupDefault) {
                $group = $field->groups()->create([
                    'title' => $groupDefault['title'],
                    'is_single' => false,
                    'sort_order' => $sort++,
                ]);
                foreach ($groupDefault['options'] as $i => $label) {
                    $group->options()->create(['label' => $label, 'sort_order' => $i]);
                }
            }

            return $field;
        });

        static::flushCache();

        return $field;
    }
}
