<?php

namespace App\Http\Controllers;

use App\Models\LookupCategory;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function index()
    {
        $lookups = LookupCategory::forSlugs([
            'bangsa', 'jantina', 'kumpulan_darah', 'negeri', 'penyakit_kronik',
        ]);

        return Inertia::render('Register', [
            'currentRoute' => 'register',
            'lookups'      => $lookups,
        ]);
    }
}
