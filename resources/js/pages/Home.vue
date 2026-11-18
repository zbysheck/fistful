<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SearchBox from '@/components/SearchBox.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import type { WorkSummary } from '@/types';

defineProps<{ recent: WorkSummary[]; count: number }>();

const trails = [
    { label: 'Yojimbo', href: '/open/Q823422' },
    { label: 'Street of Crocodiles', href: '/open/Q1170469' },
    { label: 'Silent Hill', href: '/open/Q1361079' },
    { label: 'The Matrix', href: '/open/Q83495' },
];
</script>

<template>
    <Head title="Where ideas come from" />
    <SiteHeader :search="false" />
    <main class="mx-auto max-w-4xl px-5 py-12">
        <p class="mb-2 text-sm tracking-[0.3em] text-blood uppercase">
            A wiki of influence
        </p>
        <h1 class="mb-6 font-display text-4xl leading-tight sm:text-5xl">
            <em>A Fistful of Dollars</em> came from <em>Yojimbo</em>.<br />
            Where did <em>your</em> favourite come from?
        </h1>
        <p class="mb-8 max-w-2xl text-lg text-ink/75">
            Every work borrows from something and lends to something else. Look
            one up and follow the trail in both directions.
        </p>
        <SearchBox large />
        <p class="mt-4 text-sm text-ink/60">
            Try:
            <template v-for="(trail, i) in trails" :key="trail.href">
                <Link
                    :href="trail.href"
                    class="underline decoration-blood underline-offset-2 hover:text-blood"
                    >{{ trail.label }}</Link
                ><span v-if="i < trails.length - 1">, </span>
            </template>
        </p>

        <section v-if="recent.length > 0" class="mt-16">
            <h2
                class="mb-4 border-b-2 border-ink pb-1 font-logo text-2xl tracking-wide"
            >
                Recently mapped
            </h2>
            <ul class="grid gap-x-8 gap-y-3 sm:grid-cols-2">
                <li v-for="work in recent" :key="work.slug">
                    <Link
                        :href="`/work/${work.slug}`"
                        class="font-display text-lg font-semibold hover:text-blood"
                        >{{ work.title }}</Link
                    >
                    <span v-if="work.year" class="text-ink/55">
                        ({{ work.year }})</span
                    >
                </li>
            </ul>
        </section>
    </main>
</template>
