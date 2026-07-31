<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function submitPublic(Request $request)
    {
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_area' => ['nullable', 'string', 'max:255'],
            'quote' => ['required', 'string', 'max:2000'],
        ]);

        // Honeypot — real visitors never see or fill this field (hidden via CSS).
        if ($request->filled('website')) {
            return back()->with('success', 'Terima kasih! Testimoni anda telah dihantar.');
        }

        $data['sort_order'] = (int) Testimonial::max('sort_order') + 1;
        $data['is_active'] = false;

        $testimonial = Testimonial::create($data);

        AuditLog::record('testimonial.submit_public', "Testimoni awam: {$testimonial->patient_name}");

        return back()->with('success', 'Terima kasih! Testimoni anda telah dihantar dan akan disemak sebelum dipaparkan.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_area' => ['nullable', 'string', 'max:255'],
            'quote' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? ((int) Testimonial::max('sort_order') + 1);

        $testimonial = Testimonial::create($data);

        AuditLog::record('testimonial.create', "Testimoni: {$testimonial->patient_name}");

        return back()->with('success', "Testimoni {$testimonial->patient_name} berjaya ditambah.");
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_area' => ['nullable', 'string', 'max:255'],
            'quote' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $testimonial->update($data);

        AuditLog::record('testimonial.update', "Testimoni: {$testimonial->patient_name}");

        return back()->with('success', "Testimoni {$testimonial->patient_name} berjaya dikemaskini.");
    }

    public function toggle(Testimonial $testimonial)
    {
        $testimonial->update(['is_active' => ! $testimonial->is_active]);

        AuditLog::record('testimonial.toggle', "Testimoni: {$testimonial->patient_name} · ".($testimonial->is_active ? 'aktif' : 'tidak aktif'));

        return back()->with('success', 'Status testimoni berjaya dikemaskini.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $name = $testimonial->patient_name;
        $testimonial->delete();

        AuditLog::record('testimonial.delete', "Testimoni: {$name}");

        return back()->with('success', "Testimoni {$name} berjaya dipadam.");
    }
}
