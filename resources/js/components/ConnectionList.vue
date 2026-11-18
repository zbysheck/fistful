<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { noteParts } from '@/lib/http';
import type { Connection } from '@/types';

defineProps<{
    title: string;
    items: Connection[];
    empty: string;
    isAdmin: boolean;
}>();
</script>

<template>
    <section class="mt-8">
        <h2 class="mb-3 border-b border-rule pb-1 font-serif text-2xl">
            {{ title }}
        </h2>
        <p v-if="items.length === 0" class="text-muted italic">
            {{ empty }}
        </p>
        <ul class="list-disc space-y-2 pl-6 leading-relaxed">
            <li v-for="item in items" :key="item.id">
                <Link
                    :href="`/work/${item.work.slug}`"
                    class="wiki-link font-semibold"
                    >{{ item.work.title }}</Link
                ><span v-if="item.work.year" class="text-muted">
                    ({{ item.work.year }})</span
                >
                <template v-if="item.note">
                    –
                    <template
                        v-for="(part, i) in noteParts(item.note)"
                        :key="i"
                    >
                        <i v-if="part.em">{{ part.text }}</i>
                        <template v-else>{{ part.text }}</template>
                    </template>
                </template>
                <template v-else-if="item.work.description">
                    –
                    <span class="text-muted">{{ item.work.description }}</span>
                </template>
                <sup v-if="item.source_url" class="ml-0.5 text-xs">
                    <a
                        :href="item.source_url"
                        target="_blank"
                        rel="noopener nofollow"
                        class="wiki-link"
                        >[source]</a
                    >
                </sup>
                <sup
                    v-else-if="item.origin === 'wikidata'"
                    class="ml-0.5 text-xs"
                >
                    <a
                        href="https://www.wikidata.org/"
                        target="_blank"
                        rel="noopener"
                        class="wiki-link"
                        title="Listed on Wikidata as 'inspired by' or 'based on'"
                        >[Wikidata]</a
                    >
                </sup>
                <sup
                    v-if="!item.verified"
                    class="ml-0.5 text-xs whitespace-nowrap text-muted italic"
                    title="Written by AI from web sources, not yet checked by a human"
                    >[unverified]</sup
                >
                <span
                    v-if="isAdmin && !item.note_ok"
                    class="ml-1 text-xs font-bold text-redlink"
                    >⚠ note doesn't name both works</span
                >
            </li>
        </ul>
    </section>
</template>
