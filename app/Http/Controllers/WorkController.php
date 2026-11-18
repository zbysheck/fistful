<?php

namespace App\Http\Controllers;

use App\Models\Inspiration;
use App\Models\Work;
use App\Services\InspirationGenerator;
use App\Services\Wikidata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    public function home(): Response
    {
        $recent = Work::query()
            ->where('status', Work::READY)
            ->latest('generated_at')
            ->limit(12)
            ->get()
            ->map(fn (Work $w) => $w->summary());

        // "Did you know…": losowe powiązania z opisem.
        $facts = Inspiration::query()
            ->with(['source', 'target'])
            ->whereNotNull('note')
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(fn (Inspiration $i) => [
                'note' => $i->note,
                'source' => $i->source->summary(),
                'target' => $i->target->summary(),
            ]);

        return Inertia::render('Home', [
            'recent' => $recent,
            'facts' => $facts,
            'count' => Work::query()->where('status', Work::READY)->count(),
            'connections' => Inspiration::query()->count(),
        ]);
    }

    public function random(): RedirectResponse
    {
        $slug = Work::query()->where('status', Work::READY)->inRandomOrder()->value('slug');

        return redirect(is_string($slug) ? '/work/'.$slug : '/');
    }

    public function recent(): Response
    {
        $changes = Inspiration::query()
            ->with(['source', 'target'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Inspiration $i) => [
                'id' => $i->id,
                'at' => $i->created_at?->toIso8601String(),
                'source' => $i->source->summary(),
                'target' => $i->target->summary(),
                'origin' => $i->origin,
                'verified' => $i->verified,
            ]);

        return Inertia::render('Recent', ['changes' => $changes]);
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function show(Request $request, Work $work, InspirationGenerator $generator): Response
    {
        $isAdmin = (bool) $request->session()->get('admin', false);

        $map = function (Inspiration $i, Work $other) use ($isAdmin): array {
            return [
                'id' => $i->id,
                'kind' => $i->kind,
                'work' => $other->summary(),
                'note' => $i->note,
                'source_url' => $i->source_url,
                'origin' => $i->origin,
                'verified' => $i->verified,
                'note_ok' => $isAdmin ? $i->noteNamesBothWorks() : true,
            ];
        };

        $inspiredBy = $work->inspiredBy()->with(['source', 'target'])->get()
            ->sortBy(fn (Inspiration $i) => $i->source->year ?? 9999)
            ->map(fn (Inspiration $i) => $map($i, $i->source))->values();

        $inspired = $work->inspired()->with(['source', 'target'])->get()
            ->sortBy(fn (Inspiration $i) => $i->target->year ?? 9999)
            ->map(fn (Inspiration $i) => $map($i, $i->target))->values();

        return Inertia::render('Work', [
            'work' => [
                ...$work->summary(),
                'status' => $work->status,
                'wikidata_id' => $work->wikidata_id,
                'generated_at' => $work->generated_at?->toIso8601String(),
            ],
            'inspiredBy' => $inspiredBy,
            'inspired' => $inspired,
            'canGenerate' => in_array($work->status, [Work::STUB, Work::FAILED], true) && ! $generator->limitReached(),
            'limitReached' => $generator->limitReached(),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function generate(Request $request, Work $work, InspirationGenerator $generator): JsonResponse
    {
        if ($work->status === Work::READY) {
            return response()->json(['status' => Work::READY]);
        }

        $generator->generate($work);

        return response()->json(['status' => $work->refresh()->status]);
    }

    public function search(Request $request, Wikidata $wikidata): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $local = Work::query()
            ->where('title', 'like', '%'.addcslashes($q, '%_\\').'%')
            ->orderByRaw("status = 'ready' desc")
            ->limit(5)
            ->get();

        $results = $local->map(fn (Work $w) => [
            'title' => $w->title,
            'description' => $w->description ?? ($w->year !== null ? (string) $w->year : null),
            'url' => '/work/'.$w->slug,
        ])->all();

        $seen = $local->pluck('wikidata_id')->filter()->all();

        foreach ($wikidata->search($q, 8) as $item) {
            if (in_array($item['wikidata_id'], $seen, true) || count($results) >= 10) {
                continue;
            }
            $results[] = [
                'title' => $item['title'],
                'description' => $item['description'],
                'url' => '/open/'.$item['wikidata_id'],
            ];
        }

        return response()->json($results);
    }

    /**
     * Enter w wyszukiwarce bez wybrania podpowiedzi: najlepsze trafienie, jak "Go" w Wikipedii.
     */
    public function go(Request $request, Wikidata $wikidata): RedirectResponse
    {
        $q = trim((string) $request->query('search', ''));
        if ($q === '') {
            return redirect('/');
        }

        $local = Work::query()->where('title', $q)->orderByRaw("status = 'ready' desc")->first()
            ?? Work::query()->where('title', 'like', '%'.addcslashes($q, '%_\\').'%')->orderByRaw("status = 'ready' desc")->first();
        if ($local !== null) {
            return redirect('/work/'.$local->slug);
        }

        $hit = $wikidata->search($q, 1)[0] ?? null;

        return redirect($hit !== null ? '/open/'.$hit['wikidata_id'] : '/');
    }

    /**
     * Wynik wyszukiwania z Wikidaty: zakładamy pustą stronę (stub) i przekierowujemy na nią.
     */
    public function open(string $qid, Wikidata $wikidata, InspirationGenerator $generator): RedirectResponse
    {
        abort_unless(preg_match('/^Q\d+$/', $qid) === 1, 404);

        $existing = Work::query()->where('wikidata_id', $qid)->first();
        if ($existing !== null) {
            return redirect('/work/'.$existing->slug);
        }

        $entity = $wikidata->entity($qid);
        abort_if($entity === null, 404);

        $work = $generator->workFor($qid, $entity['title'], $entity['description'], $entity['year']);

        return redirect('/work/'.$work->slug);
    }
}
