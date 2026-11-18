<script setup lang="ts">
import { computed, ref } from 'vue';
import ConnectionItem from '@/components/ConnectionItem.vue';
import { KIND_LABELS, KINDS, MEDIA } from '@/lib/taxonomy';
import type { Connection } from '@/types';

const props = defineProps<{
    id: string;
    title: string;
    direction: 'by' | 'to';
    items: Connection[];
    empty: string;
    currentTitle: string;
    isAdmin: boolean;
    refs: Record<string, number>;
}>();

/** Poniżej tej liczby pozycji nie dzielimy na podsekcje - krótka lista czyta się lepiej w całości. */
const GROUP_FROM = 5;

const medium = ref<string | null>(null);

const mediumOf = (item: Connection) => item.work.medium ?? 'other';

const media = computed(() => {
    const counts: Record<string, number> = {};
    for (const item of props.items) {
        counts[mediumOf(item)] = (counts[mediumOf(item)] ?? 0) + 1;
    }

    return Object.keys(MEDIA)
        .filter((m) => counts[m])
        .map((m) => ({ key: m, label: MEDIA[m], count: counts[m] }));
});

const visible = computed(() =>
    medium.value === null
        ? props.items
        : props.items.filter((item) => mediumOf(item) === medium.value),
);

const groups = computed(() => {
    if (visible.value.length < GROUP_FROM) {
        return [{ key: 'all', label: null, items: visible.value }];
    }

    return [...KINDS, 'other']
        .map((kind) => ({
            key: kind,
            label: KIND_LABELS[props.direction][kind],
            items: visible.value.filter(
                (item) => (item.kind ?? 'other') === kind,
            ),
        }))
        .filter((group) => group.items.length > 0);
});
</script>

<template>
    <section :id="id" class="mt-6 scroll-mt-4">
        <h2 class="mb-2 border-b border-rule pb-0.5 font-serif text-[1.6rem]">
            {{ title }}
        </h2>
        <p v-if="items.length === 0" class="text-muted italic">
            {{ empty }}
        </p>

        <div
            v-if="media.length > 1"
            class="mb-2 flex flex-wrap gap-1.5 text-sm"
            role="group"
            :aria-label="`Filter ${title} by medium`"
        >
            <button
                :class="[
                    'rounded-sm border px-2 py-0.5',
                    medium === null
                        ? 'border-link bg-[#eaf3ff] text-text'
                        : 'border-rule text-link hover:bg-soft',
                ]"
                @click="medium = null"
            >
                All {{ items.length }}
            </button>
            <button
                v-for="m in media"
                :key="m.key"
                :class="[
                    'rounded-sm border px-2 py-0.5',
                    medium === m.key
                        ? 'border-link bg-[#eaf3ff] text-text'
                        : 'border-rule text-link hover:bg-soft',
                ]"
                @click="medium = medium === m.key ? null : m.key"
            >
                {{ m.label }} {{ m.count }}
            </button>
        </div>

        <div v-for="group in groups" :key="group.key">
            <h3 v-if="group.label" class="mt-3 mb-1 font-bold">
                {{ group.label }}
            </h3>
            <ul class="list-disc space-y-1.5 pl-6 leading-relaxed">
                <ConnectionItem
                    v-for="item in group.items"
                    :key="item.id"
                    :item="item"
                    :current-title="currentTitle"
                    :is-admin="isAdmin"
                    :refs="refs"
                />
            </ul>
        </div>
    </section>
</template>
