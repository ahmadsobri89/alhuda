<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LookupCategory;
use App\Models\LookupValue;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function index()
    {
        $categories = LookupCategory::orderBy('group')->orderBy('sort_order')
            ->with(['values' => fn ($q) => $q->orderBy('sort_order')])
            ->get()
            ->map(fn ($cat) => [
                'id'             => $cat->id,
                'group'          => $cat->group,
                'slug'           => $cat->slug,
                'name_ms'        => $cat->name_ms,
                'name_en'        => $cat->name_en,
                'description_ms' => $cat->description_ms,
                'description_en' => $cat->description_en,
                'sort_order'     => $cat->sort_order,
                'values'         => $cat->values->map(fn ($v) => [
                    'id'         => $v->id,
                    'code'       => $v->code,
                    'label_ms'   => $v->label_ms,
                    'label_en'   => $v->label_en,
                    'sort_order' => $v->sort_order,
                    'is_active'  => $v->is_active,
                    'is_system'  => $v->is_system,
                ]),
            ]);

        return $categories;
    }

    public function storeValue(Request $request, LookupCategory $category)
    {
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:80'],
            'label_ms'   => ['required', 'string', 'max:120'],
            'label_en'   => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? ($category->values()->max('sort_order') + 1);

        $exists = $category->values()->where('code', $data['code'])->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Kod ini sudah wujud dalam kategori ini.']);
        }

        $value = $category->values()->create($data);

        AuditLog::record('lookup.value.create', "{$category->name_ms}: {$value->code}");

        return back()->with('success', "Nilai '{$value->label_ms}' berjaya ditambah.");
    }

    public function updateValue(Request $request, LookupCategory $category, LookupValue $value)
    {
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:80'],
            'label_ms'   => ['required', 'string', 'max:120'],
            'label_en'   => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $exists = $category->values()
            ->where('code', $data['code'])
            ->where('id', '!=', $value->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Kod ini sudah wujud dalam kategori ini.']);
        }

        $value->update($data);

        AuditLog::record('lookup.value.update', "{$category->name_ms}: {$value->code}");

        return back()->with('success', "Nilai '{$value->label_ms}' berjaya dikemaskini.");
    }

    public function destroyValue(LookupCategory $category, LookupValue $value)
    {
        if ($value->is_system) {
            return back()->withErrors(['lookup' => 'Nilai sistem tidak boleh dipadam.']);
        }

        $label = $value->label_ms;
        $value->delete();

        AuditLog::record('lookup.value.delete', "{$category->name_ms}: {$value->code}");

        return back()->with('success', "Nilai '{$label}' berjaya dipadam.");
    }

    public function toggleValue(LookupCategory $category, LookupValue $value)
    {
        $value->update(['is_active' => !$value->is_active]);

        $state = $value->is_active ? 'diaktifkan' : 'dinyahaktifkan';
        AuditLog::record('lookup.value.toggle', "{$category->name_ms}: {$value->code} → {$state}");

        return back()->with('success', "Nilai '{$value->label_ms}' berjaya {$state}.");
    }
}
