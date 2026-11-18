<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { noteParts } from '@/lib/http';
import { MEDIUM_TAG, sameTitle } from '@/lib/taxonomy';
import type { Connection } from '@/types';

defineProps<{
    item: Connection;
    currentTitle: string;
    isAdmin: boolean;
    refs: Record<string, number>;
}>();
</script>

<template>
    <li>
        <Link :href="`/work/${item.work.slug}`" class="wiki-link">{{
            item.work.title
        }}</Link
        ><span v-if="item.work.year"> ({{ item.work.year }})</span
        ><span
            v-if="item.work.medium"
            class="ml-1.5 rounded-sm border border-rule px-1 align-[1px] text-[0.7rem] text-muted"
            >{{ MEDIUM_TAG[item.work.medium] ?? item.work.medium }}</span
        >
        <template v-if="item.note">
            –
            <template v-for="(part, i) in noteParts(item.note)" :key="i">
                <Link
                    v-if="part.em && sameTitle(part.text, item.work.title)"
                    :href="`/work/${item.work.slug}`"
                    class="wiki-link italic"
                    >{{ part.text }}</Link
                >
                <i v-else-if="part.em">{{ part.text }}</i>
                <template v-else>{{ part.text }}</template>
            </template>
        </template>
        <template v-else-if="item.work.description">
            – {{ item.work.description }}
        </template>
        <sup
            v-if="item.source_url && refs[item.source_url]"
            class="text-[0.7em]"
            ><a :href="`#cite-${refs[item.source_url]}`" class="wiki-link"
                >[{{ refs[item.source_url] }}]</a
            ></sup
        ><sup
            v-if="!item.source_url && item.origin === 'wikidata'"
            class="text-[0.7em]"
            ><a href="#cite-wikidata" class="wiki-link">[Wikidata]</a></sup
        ><sup
            v-if="!item.verified"
            class="text-[0.7em] whitespace-nowrap italic"
            title="Written by AI from cited sources, not yet checked by a human"
            >[<span class="text-link">unverified</span>]</sup
        >
        <span
            v-if="isAdmin && !item.note_ok"
            class="ml-1 text-xs font-bold text-redlink"
            >⚠ note doesn't name both works</span
        >
    </li>
</template>
