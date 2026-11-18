<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SearchBox from '@/components/SearchBox.vue';
import WikiLayout from '@/components/WikiLayout.vue';
import { noteParts } from '@/lib/http';
import type { WorkSummary } from '@/types';

defineProps<{
    recent: WorkSummary[];
    facts: { note: string; source: WorkSummary; target: WorkSummary }[];
    count: number;
    connections: number;
}>();

const chain = [
    { label: 'Cinnamon Shops', href: '/open/Q3182978', year: 1934 },
    { label: 'Street of Crocodiles', href: '/open/Q7623033', year: 1986 },
    { label: "Jacob's Ladder", href: '/open/Q482626', year: 1990 },
    { label: 'Silent Hill', href: '/open/Q753164', year: 1999 },
];
</script>

<template>
    <Head title="Main Page" />
    <WikiLayout>
        <div
            class="mb-5 border border-rule bg-[#f6f6f6] px-4 py-5 text-center sm:px-8"
        >
            <h1 class="font-serif text-[1.9rem] leading-tight">
                Welcome to
                <span class="text-link">Fistful of Ideas</span>,
            </h1>
            <p class="mt-1">
                the wiki of influence – what every work borrowed, and what
                borrowed from it.
            </p>
            <p class="mt-1 text-sm text-muted">
                {{ count }} articles · {{ connections }} connections
            </p>
            <div class="mx-auto mt-4 max-w-xl text-left">
                <SearchBox large />
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[3fr_2fr]">
            <div class="space-y-4">
                <section class="border border-[#cef2e0] bg-[#f5fffa]">
                    <h2
                        class="border border-[#a3bfb1] bg-[#cef2e0] px-3 py-1 font-serif text-lg"
                    >
                        Featured chain
                    </h2>
                    <div class="px-3 py-3 leading-relaxed">
                        <p>
                            Bruno Schulz's stories became a puppet film, the
                            film shaped the nightmares of a Hollywood thriller,
                            and the thriller became the template for a video
                            game:
                        </p>
                        <p class="mt-2 flex flex-wrap items-center gap-x-2">
                            <template
                                v-for="(link, i) in chain"
                                :key="link.href"
                            >
                                <Link :href="link.href" class="wiki-link"
                                    ><i>{{ link.label }}</i></Link
                                >
                                <span class="text-muted"
                                    >({{ link.year }})</span
                                >
                                <span
                                    v-if="i < chain.length - 1"
                                    class="text-muted"
                                    >→</span
                                >
                            </template>
                        </p>
                        <p class="mt-2 text-sm">
                            Or start with
                            <Link href="/open/Q20475" class="wiki-link"
                                ><i>Yojimbo</i></Link
                            >,
                            <Link href="/open/Q83495" class="wiki-link"
                                ><i>The Matrix</i></Link
                            >
                            or a
                            <Link href="/random" class="wiki-link"
                                >random article</Link
                            >.
                        </p>
                    </div>
                </section>

                <section
                    v-if="facts.length > 0"
                    class="border border-[#cedff2] bg-[#f5faff]"
                >
                    <h2
                        class="border border-[#a3b0bf] bg-[#cedff2] px-3 py-1 font-serif text-lg"
                    >
                        Did you know…
                    </h2>
                    <ul
                        class="list-disc space-y-2 py-3 pr-3 pl-8 leading-relaxed"
                    >
                        <li v-for="(fact, i) in facts" :key="i">
                            <template
                                v-for="(part, j) in noteParts(fact.note)"
                                :key="j"
                            >
                                <i v-if="part.em">{{ part.text }}</i>
                                <template v-else>{{ part.text }}</template>
                            </template>
                            <span class="text-sm">
                                (<Link
                                    :href="`/work/${fact.target.slug}`"
                                    class="wiki-link"
                                    >{{ fact.target.title }}</Link
                                >)</span
                            >
                        </li>
                    </ul>
                </section>
            </div>

            <div class="space-y-4">
                <section class="border border-[#ddcef2] bg-[#faf5ff]">
                    <h2
                        class="border border-[#afa3bf] bg-[#ddcef2] px-3 py-1 font-serif text-lg"
                    >
                        Recently mapped
                    </h2>
                    <ul
                        v-if="recent.length > 0"
                        class="list-disc space-y-1 py-3 pr-3 pl-8"
                    >
                        <li v-for="work in recent" :key="work.slug">
                            <Link :href="`/work/${work.slug}`" class="wiki-link"
                                ><i>{{ work.title }}</i></Link
                            >
                            <span v-if="work.year" class="text-muted">
                                ({{ work.year }})</span
                            >
                        </li>
                    </ul>
                    <p v-else class="px-3 py-3 text-muted italic">
                        Nothing yet.
                    </p>
                </section>

                <section class="border border-[#f2e0ce] bg-[#fffaf5]">
                    <h2
                        class="border border-[#bfb1a3] bg-[#f2e0ce] px-3 py-1 font-serif text-lg"
                    >
                        How it works
                    </h2>
                    <div class="space-y-2 px-3 py-3 text-sm leading-relaxed">
                        <p>
                            Every article has two lists: what the work was
                            <b>inspired by</b> and what it <b>inspired</b>.
                            Follow the links to walk the chain in either
                            direction.
                        </p>
                        <p>
                            Search for any book, film, game or myth. If there's
                            no article yet, one is researched and written on the
                            spot, with sources.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </WikiLayout>
</template>
