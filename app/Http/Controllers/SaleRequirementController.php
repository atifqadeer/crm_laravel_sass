<?php

namespace App\Http\Controllers;

use Horsefly\SaleRequirementField;
use Horsefly\SaleRequirementGroup;
use Horsefly\SaleRequirementOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Administrator > Sale Requirements: manages the options shown in the timing, experience,
 * benefits and qualification pickers on the sale form.
 * Every change responds with the re-rendered field panel so the page updates in place.
 */
class SaleRequirementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:administrator-sale-requirements-index');
    }

    public function index()
    {
        SaleRequirementField::seedDefaults();

        $fields = SaleRequirementField::with('groups.options')->orderBy('id')->get();

        return view('sale-requirements.index', compact('fields'));
    }

    public function updateField(Request $request, SaleRequirementField $field): JsonResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
            'hint' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:255',
            'connector' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100',
            'is_required' => 'boolean',
            'show_hours' => 'boolean',
        ]);

        $field->update($data + [
            'is_required' => $request->boolean('is_required'),
            'show_hours' => $request->boolean('show_hours'),
        ]);

        return $this->respond($field, 'Field settings saved.');
    }

    public function restoreField(SaleRequirementField $field): JsonResponse
    {
        SaleRequirementField::restoreDefaults($field->key);

        return $this->respond($field, 'Defaults restored.');
    }

    public function storeGroup(Request $request, SaleRequirementField $field): JsonResponse
    {
        $data = $this->validateGroup($request);

        DB::transaction(function () use ($field, $data) {
            $group = $field->groups()->create($data + [
                'sort_order' => (int) $field->groups()->max('sort_order') + 1,
            ]);
            $this->keepSingleGroupUnique($group);
        });

        return $this->respond($field, 'Group added.');
    }

    public function updateGroup(Request $request, SaleRequirementGroup $group): JsonResponse
    {
        $data = $this->validateGroup($request);

        DB::transaction(function () use ($group, $data) {
            $group->update($data);
            $this->keepSingleGroupUnique($group);
        });

        return $this->respond($group->field, 'Group updated.');
    }

    public function destroyGroup(SaleRequirementGroup $group): JsonResponse
    {
        $field = $group->field;
        $group->delete();

        return $this->respond($field, 'Group deleted.');
    }

    public function storeOption(Request $request, SaleRequirementGroup $group): JsonResponse
    {
        $data = $this->validateOption($request, $group);

        $group->options()->create($data + [
            'is_active' => true,
            'sort_order' => (int) $group->options()->max('sort_order') + 1,
        ]);

        return $this->respond($group->field, 'Option added.');
    }

    public function updateOption(Request $request, SaleRequirementOption $option): JsonResponse
    {
        $data = $this->validateOption($request, $option->group, $option);

        $option->update($data);

        return $this->respond($option->group->field, 'Option updated.');
    }

    public function toggleOption(SaleRequirementOption $option): JsonResponse
    {
        $option->update(['is_active' => !$option->is_active]);

        return $this->respond(
            $option->group->field,
            $option->is_active ? 'Option shown on the sale form.' : 'Option hidden from the sale form.'
        );
    }

    public function destroyOption(SaleRequirementOption $option): JsonResponse
    {
        $field = $option->group->field;
        $option->delete();

        return $this->respond($field, 'Option deleted.');
    }

    /**
     * Save drag-and-drop order. Only ids belonging to the given parent are touched.
     */
    public function reorder(Request $request, SaleRequirementField $field): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:groups,options',
            'group_id' => 'required_if:type,options|nullable|integer',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $query = $data['type'] === 'groups'
            ? SaleRequirementGroup::where('field_id', $field->id)
            : SaleRequirementOption::where('group_id', $data['group_id'])
                ->whereHas('group', fn ($q) => $q->where('field_id', $field->id));

        DB::transaction(function () use ($query, $data) {
            foreach (array_values($data['ids']) as $position => $id) {
                (clone $query)->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return $this->respond($field, 'Order saved.');
    }

    private function validateGroup(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'is_single' => 'boolean',
            'prefix' => 'nullable|string|max:150',
            'has_quantity' => 'boolean',
        ]);
        $single = $request->boolean('is_single');

        // Pick-one groups word each option individually, so the group prefix and number don't apply
        return [
            'title' => $data['title'],
            'is_single' => $single,
            'prefix' => $single ? null : ($data['prefix'] ?? null),
            'has_quantity' => !$single && $request->boolean('has_quantity'),
        ];
    }

    private function validateOption(Request $request, SaleRequirementGroup $group, ?SaleRequirementOption $option = null): array
    {
        $data = $request->validate([
            'label' => [
                'required', 'string', 'max:150',
                Rule::unique('sale_requirement_options', 'label')
                    ->where('group_id', $group->id)
                    ->ignore($option?->id),
            ],
            'text' => 'nullable|string|max:255',
            'connector' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:150',
        ], [
            'label.unique' => 'This option already exists in the group.',
        ]);

        // Sentence wording only applies to pick-one groups
        if (!$group->is_single) {
            $data = array_merge($data, ['text' => null, 'connector' => null, 'suffix' => null]);
        }

        return $data;
    }

    // A field has at most one pick-one group, placed first in the sentence
    private function keepSingleGroupUnique(SaleRequirementGroup $group): void
    {
        if ($group->is_single) {
            SaleRequirementGroup::where('field_id', $group->field_id)
                ->whereKeyNot($group->id)
                ->update(['is_single' => false]);
        }
    }

    private function respond(SaleRequirementField $field, string $message): JsonResponse
    {
        SaleRequirementField::flushCache();

        $field = $field->fresh(['groups.options']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'html' => view('sale-requirements.partials.field-panel', compact('field'))->render(),
        ]);
    }
}
