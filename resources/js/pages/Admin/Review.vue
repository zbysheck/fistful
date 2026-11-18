<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import WikiLayout from '@/components/WikiLayout.vue';
import { CdxButton, CdxSelect, CdxTextArea } from '@wikimedia/codex';
import { KINDS } from '@/lib/taxonomy';
import type { WorkSummary } from '@/types';

type Pending = {
    id: number;
    source: WorkSummary;
    target: WorkSummary;
    kind: string | null;
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

const kinds = reactive<Record<number, string>>(
    Object.fromEntries(props.pending.map((p) => [p.id, p.kind ?? ''])),
);

const kindItems = [
    { value: '', label: 'general' },
    ...KINDS.map((k) => ({ value: k, label: k })),
];

const opts = { preserveScroll: true };

function approve(item: Pending) {
    router.patch(
        `/admin/inspirations/${item.id}`,
        {
            note: notes[item.id] || null,
            kind: kinds[item.id] || null,
            verified: true,
        },
        opts,
    );
}

function save(item: Pending) {
    router.patch(
        `/admin/inspirations/${item.id}`,
        { note: notes[item.id] || null, kind: kinds[item.id] || null },
        opts,
    );
}

function remove(item: Pending) {
    router.delete(`/admin/inspirations/${item.id}`, opts);
}
</script>

<template>
    <Head title="Review queue" />
    <WikiLayout>
        <main class="max-w-4xl py-2">
            <div class="mb-6 flex items-baseline justify-between">
                <h1 class="font-serif text-3xl">Review queue</h1>
                <CdxButton weight="quiet" @click="router.post('/admin/logout')"
                    >Log out</CdxButton
                >
            </div>
            <p class="mb-8 text-sm text-muted">
                {{ stats.works }} works ({{ stats.ready }} written) ·
                {{ stats.connections }} connections ({{
                    stats.unverified
                }}
                unverified) · AI today: {{ stats.ai_today }}/{{
                    stats.ai_limit
                }}
            </p>

            <p v-if="pending.length === 0" class="text-muted italic">
                Nothing to review.
            </p>
            <ul class="space-y-6">
                <li
                    v-for="item in pending"
                    :key="item.id"
                    class="border border-rule p-4"
                >
                    <div class="text-base">
                        <Link
                            :href="`/work/${item.source.slug}`"
                            class="wiki-link font-semibold"
                            >{{ item.source.title }}</Link
                        >
                        <span class="mx-2 text-muted">→</span>
                        <Link
                            :href="`/work/${item.target.slug}`"
                            class="wiki-link font-semibold"
                            >{{ item.target.title }}</Link
                        >
                    </div>
                    <CdxTextArea
                        v-model="notes[item.id]"
                        class="mt-2"
                        autosize
                    />
                    <p
                        v-if="!item.note_ok"
                        class="text-sm font-bold text-redlink"
                    >
                        ⚠ The note should name both works by title.
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                        <a
                            v-if="item.source_url"
                            :href="item.source_url"
                            target="_blank"
                            rel="noopener"
                            class="wiki-link max-w-xs truncate"
                            >{{ item.source_url }}</a
                        >
                        <span class="text-muted">{{ item.origin }}</span>
                        <CdxSelect
                            v-model:selected="kinds[item.id]"
                            :menu-items="kindItems"
                        />
                        <span class="grow" />
                        <CdxButton weight="quiet" @click="save(item)"
                            >Save</CdxButton
                        >
                        <CdxButton
                            action="progressive"
                            weight="primary"
                            @click="approve(item)"
                            >Approve</CdxButton
                        >
                        <CdxButton action="destructive" @click="remove(item)"
                            >Delete</CdxButton
                        >
                    </div>
                </li>
            </ul>
        </main>
    </WikiLayout>
</template>
