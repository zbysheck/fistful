<?php

namespace App\Http\Controllers;

use App\Models\Inspiration;
use App\Models\Work;
use App\Services\InspirationGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $password = config('services.admin.password');
        $given = (string) $request->input('password', '');

        if (! is_string($password) || $password === '' || ! hash_equals($password, $given)) {
            return back()->withErrors(['password' => 'Wrong password.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin', true);

        return redirect('/admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin');

        return redirect('/');
    }

    public function review(InspirationGenerator $generator): Response
    {
        $pending = Inspiration::query()
            ->with(['source', 'target'])
            ->where('verified', false)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Inspiration $i) => [
                'id' => $i->id,
                'source' => $i->source->summary(),
                'target' => $i->target->summary(),
                'note' => $i->note,
                'source_url' => $i->source_url,
                'origin' => $i->origin,
                'note_ok' => $i->noteNamesBothWorks(),
            ]);

        return Inertia::render('Admin/Review', [
            'pending' => $pending,
            'stats' => [
                'works' => Work::query()->count(),
                'ready' => Work::query()->where('status', Work::READY)->count(),
                'connections' => Inspiration::query()->count(),
                'unverified' => Inspiration::query()->where('verified', false)->count(),
                'ai_today' => $generator->usedToday(),
                'ai_limit' => (int) config('services.gemini.daily_limit', 200),
            ],
        ]);
    }

    public function update(Request $request, Inspiration $inspiration): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:600'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'verified' => ['sometimes', 'boolean'],
        ]);

        $inspiration->fill($data)->save();

        return back();
    }

    public function destroy(Inspiration $inspiration): RedirectResponse
    {
        $inspiration->delete();

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'string', 'exists:works,slug'],
            'target' => ['required', 'string', 'exists:works,slug', 'different:source'],
            'note' => ['nullable', 'string', 'max:600'],
            'source_url' => ['nullable', 'url', 'max:2000'],
        ]);

        Inspiration::query()->updateOrCreate(
            [
                'source_work_id' => Work::query()->where('slug', $data['source'])->value('id'),
                'target_work_id' => Work::query()->where('slug', $data['target'])->value('id'),
            ],
            [
                'note' => $data['note'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'origin' => Inspiration::ORIGIN_MANUAL,
                'verified' => true,
            ],
        );

        return back();
    }

    public function regenerate(Work $work, InspirationGenerator $generator): RedirectResponse
    {
        $work->forceFill(['status' => Work::STUB])->save();
        $generator->generate($work);

        return back();
    }
}
