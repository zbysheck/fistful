<script setup lang="ts">
import { CdxTypeaheadSearch } from '@wikimedia/codex';
import type { SearchResult } from '@wikimedia/codex';
import { ref } from 'vue';
import type { SearchResult as Hit } from '@/types';

withDefaults(defineProps<{ large?: boolean }>(), { large: false });

const results = ref<SearchResult[]>([]);
const query = ref('');
let controller: AbortController | undefined;

async function onInput(value: string) {
    query.value = value;
    const q = value.trim();
    controller?.abort();

    if (q.length < 2) {
        results.value = [];

        return;
    }

    controller = new AbortController();

    try {
        const response = await fetch(`/search?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        });
        const hits = (await response.json()) as Hit[];

        // Wynik mógł przyjść po nowszym zapytaniu - wtedy go pomijamy.
        if (q !== query.value.trim()) {
            return;
        }

        results.value = hits.map((hit) => ({
            value: hit.url,
            label: hit.title,
            description: hit.description,
            url: hit.url,
        }));
    } catch {
        // przerwane albo sieć: zostawiamy poprzednie wyniki
    }
}
</script>

<template>
    <CdxTypeaheadSearch
        id="site-search"
        form-action="/go"
        :search-results="results"
        :class="['w-full', large ? 'fistful-search--large' : '']"
        :visible-item-limit="10"
        highlight-query
        use-button
        button-label="Search"
        placeholder="Search Fistful of Ideas"
        @input="onInput"
    >
        <template #search-no-results-text>No results found</template>
        <!-- Kliknięcie wyniku to zwykły link; Enter bez wyboru wysyła formularz na /go. -->
    </CdxTypeaheadSearch>
</template>
