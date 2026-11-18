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
    <section>
        <h2
            class="mb-4 border-b-2 border-ink pb-1 font-logo text-2xl tracking-wide"
        >
            {{ title }}
        </h2>
        <p v-if="items.length === 0" class="text-ink/60 italic">
            {{ empty }}
        </p>
        <ul class="space-y-5">
            <li v-for="item in items" :key="item.id">
                <Link
                    :href="`/work/${item.work.slug}`"
                    class="font-display text-xl font-semibold text-ink decoration-blood decoration-2 underline-offset-4 hover:underline"
                >
                    {{ item.work.title }}
                </Link>
                <span v-if="item.work.year" class="ml-1 text-ink/55">
                    ({{ item.work.year }})
                </span>
                <p v-if="item.note" class="mt-1 leading-relaxed">
                    <template
                        v-for="(part, i) in noteParts(item.note)"
                        :key="i"
                    >
                        <em v-if="part.em">{{ part.text }}</em>
                        <template v-else>{{ part.text }}</template>
                    </template>
                </p>
                <p v-else-if="item.work.description" class="mt-1 text-ink/70">
                    {{ item.work.description }}
                </p>
                <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-ink/55">
                    <a
                        v-if="item.source_url"
                        :href="item.source_url"
                        target="_blank"
                        rel="noopener nofollow"
                        class="underline hover:text-blood"
                        >source</a
                    >
                    <span v-if="item.origin === 'wikidata'">via Wikidata</span>
                    <span v-if="!item.verified" class="text-blood/80"
                        >AI-written, not yet verified</span
                    >
                    <span
                        v-if="isAdmin && !item.note_ok"
                        class="font-bold text-blood"
                        >⚠ note doesn't name both works</span
                    >
                </div>
            </li>
        </ul>
    </section>
</template>
