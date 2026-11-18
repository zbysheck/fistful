<script setup lang="ts">
import { computed } from 'vue';
import ConnectionItem from '@/components/ConnectionItem.vue';
import { KIND_LABELS, KINDS } from '@/lib/taxonomy';
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

/** Poniżej tej liczby pozycji nie dzielimy na zwijane podsekcje - krótka lista czyta się lepiej w całości. */
const GROUP_FROM = 5;

const order = [...KINDS, 'other'];

/** Zawsze posortowane typem; podsekcje tylko przy dłuższych listach. */
const sorted = computed(() =>
    [...props.items].sort(
        (a, b) =>
            order.indexOf(a.kind ?? 'other') - order.indexOf(b.kind ?? 'other'),
    ),
);

const groups = computed(() =>
    order
        .map((kind) => ({
            key: kind,
            label: KIND_LABELS[props.direction][kind],
            items: sorted.value.filter(
                (item) => (item.kind ?? 'other') === kind,
            ),
        }))
        .filter((group) => group.items.length > 0),
);
</script>

<template>
    <section :id="id" class="mt-6 scroll-mt-4">
        <h2 class="mb-2 border-b border-rule pb-0.5 font-serif text-[1.6rem]">
            {{ title }}
        </h2>
        <p v-if="items.length === 0" class="text-muted italic">
            {{ empty }}
        </p>

        <ul
            v-else-if="items.length < GROUP_FROM"
            class="list-disc space-y-1.5 pl-6 leading-relaxed"
        >
            <ConnectionItem
                v-for="item in sorted"
                :key="item.id"
                :item="item"
                :current-title="currentTitle"
                :is-admin="isAdmin"
                :refs="refs"
            />
        </ul>

        <details
            v-for="group in groups"
            v-else
            :key="group.key"
            class="group mt-2"
            open
        >
            <summary
                class="flex cursor-pointer list-none items-baseline gap-2 py-0.5 select-none [&::-webkit-details-marker]:hidden"
            >
                <span class="font-bold">{{ group.label }}</span>
                <span class="text-sm text-muted"
                    >({{ group.items.length }})</span
                >
                <span class="text-sm text-link group-open:hidden">[show]</span>
                <span class="hidden text-sm text-link group-open:inline"
                    >[hide]</span
                >
            </summary>
            <ul class="mb-2 list-disc space-y-1.5 pl-6 leading-relaxed">
                <ConnectionItem
                    v-for="item in group.items"
                    :key="item.id"
                    :item="item"
                    :current-title="currentTitle"
                    :is-admin="isAdmin"
                    :refs="refs"
                />
            </ul>
        </details>
    </section>
</template>
