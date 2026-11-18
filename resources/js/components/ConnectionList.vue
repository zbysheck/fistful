<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { noteParts } from '@/lib/http';
import type { Connection } from '@/types';

defineProps<{
    id: string;
    title: string;
    items: Connection[];
    empty: string;
    isAdmin: boolean;
    refs: Record<string, number>;
}>();
</script>

<template>
    <section :id="id" class="mt-6 scroll-mt-4">
        <h2 class="mb-2 border-b border-rule pb-0.5 font-serif text-[1.6rem]">
            {{ title }}
        </h2>
        <p v-if="items.length === 0" class="text-muted italic">
            {{ empty }}
        </p>
        <ul class="list-disc space-y-1.5 pl-6 leading-relaxed">
            <li v-for="item in items" :key="item.id">
                <Link :href="`/work/${item.work.slug}`" class="wiki-link">{{
                    item.work.title
                }}</Link
                ><span v-if="item.work.year"> ({{ item.work.year }})</span>
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
                    – {{ item.work.description }}
                </template>
                <sup
                    v-if="item.source_url && refs[item.source_url]"
                    class="text-[0.7em]"
                    ><a
                        :href="`#cite-${refs[item.source_url]}`"
                        class="wiki-link"
                        >[{{ refs[item.source_url] }}]</a
                    ></sup
                ><sup
                    v-if="!item.source_url && item.origin === 'wikidata'"
                    class="text-[0.7em]"
                    ><a href="#cite-wikidata" class="wiki-link"
                        >[Wikidata]</a
                    ></sup
                ><sup
                    v-if="!item.verified"
                    class="text-[0.7em] whitespace-nowrap italic"
                    title="Written by AI from web sources, not yet checked by a human"
                    >[<span class="text-link">unverified</span>]</sup
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
