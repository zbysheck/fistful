<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import SiteHeader from '@/components/SiteHeader.vue';
import type { WorkSummary } from '@/types';

type Pending = {
    id: number;
    source: WorkSummary;
    target: WorkSummary;
    note: string | null;
    source_url: string | null;
    origin: string;
    note_ok: boolean;
};

const props = defineProps<{
    pending: Pending[];
    stats: Record<string, number>;
}>();

const notes = reactive<Record<number, string>>(
    Object.fromEntries(props.pending.map((p) => [p.id, p.note ?? ''])),
);

const opts = { preserveScroll: true };

function approve(item: Pending) {
    router.patch(
        `/admin/inspirations/${item.id}`,
        { note: notes[item.id] || null, verified: true },
        opts,
    );
}

function save(item: Pending) {
    router.patch(
        `/admin/inspirations/${item.id}`,
        { note: notes[item.id] || null },
        opts,
    );
}

function remove(item: Pending) {
    router.delete(`/admin/inspirations/${item.id}`, opts);
}
</script>

<template>
    <Head title="Review queue" />
    <SiteHeader />
    <main class="mx-auto max-w-4xl px-5 py-10">
        <div class="mb-6 flex items-baseline justify-between">
            <h1 class="font-logo text-3xl">Review queue</h1>
            <button
                class="text-sm underline"
                @click="router.post('/admin/logout')"
            >
                Log out
            </button>
        </div>
        <p class="mb-8 text-sm text-ink/60">
            {{ stats.works }} works ({{ stats.ready }} written) ·
            {{ stats.connections }} connections ({{
                stats.unverified
            }}
            unverified) · AI today: {{ stats.ai_today }}/{{ stats.ai_limit }}
        </p>

        <p v-if="pending.length === 0" class="text-ink/60 italic">
            Nothing to review.
        </p>
        <ul class="space-y-6">
            <li
                v-for="item in pending"
                :key="item.id"
                class="border-2 border-ink p-4"
            >
                <div class="font-display text-lg">
                    <Link
                        :href="`/work/${item.source.slug}`"
                        class="font-semibold hover:text-blood"
                        >{{ item.source.title }}</Link
                    >
                    <span class="mx-2 text-blood">→</span>
                    <Link
                        :href="`/work/${item.target.slug}`"
                        class="font-semibold hover:text-blood"
                        >{{ item.target.title }}</Link
                    >
                </div>
                <textarea
                    v-model="notes[item.id]"
                    rows="2"
                    class="mt-2 w-full border border-ink/40 bg-paper p-2"
                />
                <p v-if="!item.note_ok" class="text-sm font-bold text-blood">
                    ⚠ The note should name both works by title.
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                    <a
                        v-if="item.source_url"
                        :href="item.source_url"
                        target="_blank"
                        rel="noopener"
                        class="max-w-xs truncate underline"
                        >{{ item.source_url }}</a
                    >
                    <span class="text-ink/50">{{ item.origin }}</span>
                    <span class="grow" />
                    <button class="underline" @click="save(item)">Save</button>
                    <button
                        class="border-2 border-ink bg-ink px-3 py-1 text-paper hover:bg-blood"
                        @click="approve(item)"
                    >
                        Approve
                    </button>
                    <button
                        class="border-2 border-blood px-3 py-1 text-blood"
                        @click="remove(item)"
                    >
                        Delete
                    </button>
                </div>
            </li>
        </ul>
    </main>
</template>
