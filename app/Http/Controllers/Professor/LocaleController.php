<?php

namespace App\Http\Controllers\Professor;

use App\Enums\InterfaceLocale;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::enum(InterfaceLocale::class)],
        ]);

        $request->user()->forceFill([
            'locale' => $validated['locale'],
        ])->save();

        return back();
    }
}
