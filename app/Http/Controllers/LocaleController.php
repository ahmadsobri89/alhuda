<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $locale = $request->input('locale', 'ms');
        if (!in_array($locale, ['ms', 'en'])) {
            $locale = 'ms';
        }
        $request->session()->put('locale', $locale);
        return back();
    }
}
