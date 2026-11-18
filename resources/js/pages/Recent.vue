<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import WikiLayout from '@/components/WikiLayout.vue';
import type { WorkSummary } from '@/types';

defineProps<{
    changes: {
        id: number;
        at: string | null;
        source: WorkSummary;
        target: WorkSummary;
        origin: string;
        verified: boolean;
    }[];
}>();

function when(at: string | null): string {
    return at
        ? new Date(at).toLocaleString('en-GB', {
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '';
}
</script>

<template>
    <Head title="Recent changes" />
    <WikiLayout>
        <h1 class="border-b border-rule pb-1 font-serif text-[1.9rem]">
            Recent changes
        </h1>
        <p class="mt-2 mb-4 text-sm text-muted">
            The latest connections added to Fistful of Ideas.
        </p>
        <p v-if="changes.length === 0" class="text-muted italic">
            No changes yet.
        </p>
        <ul class="space-y-1 text-sm">
            <li v-for="c in changes" :key="c.id">
                <span class="text-muted tabular-nums">{{ when(c.at) }}</span>
                ·
                <Link :href="`/work/${c.source.slug}`" class="wiki-link"
                    ><i>{{ c.source.title }}</i></Link
                >
                inspired
                <Link :href="`/work/${c.target.slug}`" class="wiki-link"
                    ><i>{{ c.target.title }}</i></Link
                >
                <span class="text-muted">
                    ({{
                        c.origin === 'ai'
                            ? 'AI'
                            : c.origin === 'wikidata'
                              ? 'Wikidata'
                              : 'editor'
                    }}{{ c.verified ? '' : ', unverified' }})</span
                >
            </li>
        </ul>
    </WikiLayout>
</template>
