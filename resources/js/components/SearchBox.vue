<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { SearchResult } from '@/types';

const props = withDefaults(defineProps<{ large?: boolean }>(), {
    large: false,
});

const query = ref('');
const results = ref<SearchResult[]>([]);
const open = ref(false);
const active = ref(-1);
const loading = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;
let controller: AbortController | undefined;

watch(query, (value) => {
    clearTimeout(timer);
    const q = value.trim();

    if (q.length < 2) {
        results.value = [];
        open.value = false;

        return;
    }

    timer = setTimeout(() => void search(q), 250);
});

async function search(q: string) {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        const response = await fetch(`/search?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        results.value = (await response.json()) as SearchResult[];
        open.value = true;
        active.value = results.value.length > 0 ? 0 : -1;
    } catch {
        // przerwane albo sieć: zostawiamy poprzednie wyniki
    } finally {
        loading.value = false;
    }
}

function go(result: SearchResult | undefined) {
    if (!result) {
        return;
    }

    open.value = false;
    query.value = '';
    router.visit(result.url);
}

function onKey(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        active.value = Math.min(active.value + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value = Math.max(active.value - 1, 0);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(results.value[active.value]);
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}
</script>

<template>
    <div class="relative w-full">
        <input
            v-model="query"
            type="search"
            placeholder="Search Fistful of Ideas"
            autocomplete="off"
            :class="[
                'w-full rounded-sm border border-rule bg-page text-text placeholder:text-muted focus:border-link focus:ring-1 focus:ring-link focus:outline-none',
                props.large ? 'px-4 py-2.5 text-lg' : 'px-3 py-1.5 text-sm',
            ]"
            @keydown="onKey"
            @focus="open = results.length > 0"
            @blur="open = false"
        />
        <span
            v-if="loading"
            class="absolute top-1/2 right-3 -translate-y-1/2 text-sm text-muted"
            >…</span
        >
        <ul
            v-if="open"
            class="absolute z-20 mt-px max-h-[70vh] w-full overflow-y-auto border border-rule bg-page shadow-md"
        >
            <li
                v-if="results.length === 0"
                class="px-3 py-2 text-sm text-muted"
            >
                Nothing found.
            </li>
            <li
                v-for="(result, i) in results"
                :key="result.url"
                :class="[
                    'cursor-pointer px-3 py-1.5',
                    i === active ? 'bg-soft' : '',
                ]"
                @mousedown.prevent="go(result)"
                @mouseenter="active = i"
            >
                <div class="text-sm font-semibold text-text">
                    {{ result.title }}
                </div>
                <div v-if="result.description" class="text-xs text-muted">
                    {{ result.description }}
                </div>
            </li>
        </ul>
    </div>
</template>
