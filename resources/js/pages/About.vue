<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import WikiLayout from '@/components/WikiLayout.vue';
</script>

<template>
    <Head title="About" />
    <WikiLayout>
        <h1 class="border-b border-rule pb-1 font-serif text-[1.9rem]">
            About Fistful of Ideas
        </h1>
        <div class="mt-4 max-w-3xl space-y-3 leading-relaxed">
            <p>
                <b>Fistful of Ideas</b> is a wiki about influence. Sergio
                Leone's <i>A Fistful of Dollars</i> retold Akira Kurosawa's
                <i>Yojimbo</i>; the Brothers Quay turned Bruno Schulz's stories
                into <i>Street of Crocodiles</i>, which shaped the nightmares of
                <i>Jacob's Ladder</i>, which in turn shaped <i>Silent Hill</i>.
                This site collects chains like these.
            </p>
            <h2 class="border-b border-rule pb-0.5 font-serif text-[1.5rem]">
                How articles are written
            </h2>
            <ul class="list-disc space-y-1 pl-6">
                <li>
                    Connections listed on Wikidata as <i>inspired by</i> or
                    <i>based on</i> are imported directly.
                </li>
                <li>
                    Other connections are researched by AI. Every one must cite
                    a source, and the source page must mention both works;
                    otherwise it is discarded.
                </li>
                <li>
                    AI-written connections are marked <i>[unverified]</i> until
                    an editor checks them.
                </li>
            </ul>
            <p>
                Start from the
                <Link href="/" class="wiki-link">main page</Link> or try a
                <Link href="/random" class="wiki-link">random article</Link>.
            </p>
        </div>
    </WikiLayout>
</template>
