<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HealthTip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HealthTipController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? ((int) HealthTip::max('sort_order') + 1);
        $data['image_path'] = $request->file('image')->store('tips', 'public');
        unset($data['image']);

        $tip = HealthTip::create($data);

        AuditLog::record('health_tip.create', "Tip Kesihatan: {$tip->title}");

        return back()->with('success', "Tip \"{$tip->title}\" berjaya ditambah.");
    }

    public function update(Request $request, HealthTip $healthTip)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($healthTip->image_path);
            $data['image_path'] = $request->file('image')->store('tips', 'public');
        }

        unset($data['image']);
        $healthTip->update($data);

        AuditLog::record('health_tip.update', "Tip Kesihatan: {$healthTip->title}");

        return back()->with('success', "Tip \"{$healthTip->title}\" berjaya dikemaskini.");
    }

    public function toggle(HealthTip $healthTip)
    {
        $healthTip->update(['is_active' => ! $healthTip->is_active]);

        AuditLog::record('health_tip.toggle', "Tip Kesihatan: {$healthTip->title} · ".($healthTip->is_active ? 'aktif' : 'tidak aktif'));

        return back()->with('success', 'Status tip berjaya dikemaskini.');
    }

    public function destroy(HealthTip $healthTip)
    {
        $title = $healthTip->title;
        Storage::disk('public')->delete($healthTip->image_path);
        $healthTip->delete();

        AuditLog::record('health_tip.delete', "Tip Kesihatan: {$title}");

        return back()->with('success', "Tip \"{$title}\" berjaya dipadam.");
    }
}
