<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SearchBox from '@/components/SearchBox.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import type { WorkSummary } from '@/types';

defineProps<{ recent: WorkSummary[]; count: number }>();

const trails = [
    { label: 'Yojimbo', href: '/open/Q20475' },
    { label: 'Street of Crocodiles', href: '/open/Q7623033' },
    { label: 'Silent Hill', href: '/open/Q753164' },
    { label: 'The Matrix', href: '/open/Q83495' },
];
</script>

<template>
    <Head title="Main Page" />
    <SiteHeader :search="false" />
    <main class="mx-auto max-w-4xl px-5 py-10">
        <div class="border border-rule bg-soft px-6 py-8 text-center">
            <h1 class="font-serif text-3xl sm:text-4xl">
                Welcome to Fistful of Ideas
            </h1>
            <p class="mt-2 text-muted">
                the wiki of influence – what every work borrowed, and what
                borrowed from it.
                <span v-if="count > 0">{{ count }} works so far.</span>
            </p>
            <div class="mx-auto mt-6 max-w-xl text-left">
                <SearchBox large />
            </div>
            <p class="mt-3 text-sm text-muted">
                Try:
                <template v-for="(trail, i) in trails" :key="trail.href">
                    <Link :href="trail.href" class="wiki-link">{{
                        trail.label
                    }}</Link
                    ><span v-if="i < trails.length - 1"> · </span>
                </template>
            </p>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <section class="border border-rule">
                <h2
                    class="border-b border-rule bg-soft px-3 py-1.5 font-serif text-lg"
                >
                    How it works
                </h2>
                <div class="space-y-2 px-4 py-3 text-sm leading-relaxed">
                    <p>
                        <i>A Fistful of Dollars</i> retold <i>Yojimbo</i>.
                        <i>Jacob's Ladder</i> took its nightmares from
                        <i>Street of Crocodiles</i>, and <i>Silent Hill</i> took
                        them from <i>Jacob's Ladder</i>.
                    </p>
                    <p>
                        Every article has two lists: what the work was
                        <b>inspired by</b>, and what it <b>inspired</b>. Follow
                        the links to walk the chain.
                    </p>
                </div>
            </section>
            <section class="border border-rule">
                <h2
                    class="border-b border-rule bg-soft px-3 py-1.5 font-serif text-lg"
                >
                    Recently mapped
                </h2>
                <ul
                    v-if="recent.length > 0"
                    class="list-disc space-y-1 py-3 pr-4 pl-8 text-sm"
                >
                    <li v-for="work in recent" :key="work.slug">
                        <Link :href="`/work/${work.slug}`" class="wiki-link">{{
                            work.title
                        }}</Link>
                        <span v-if="work.year" class="text-muted">
                            ({{ work.year }})</span
                        >
                    </li>
                </ul>
                <p v-else class="px-4 py-3 text-sm text-muted italic">
                    Nothing yet – search for a work to write its article.
                </p>
            </section>
        </div>
    </main>
</template>
