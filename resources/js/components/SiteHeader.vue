<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SearchBox from '@/components/SearchBox.vue';

withDefaults(defineProps<{ search?: boolean }>(), { search: true });
</script>

<template>
    <header class="border-b border-rule bg-page">
        <div
            class="mx-auto flex max-w-6xl flex-col gap-3 px-5 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Link href="/" class="flex items-baseline gap-2 no-underline">
                <span class="font-serif text-2xl text-text"
                    >Fistful of Ideas</span
                >
                <span class="hidden text-sm text-muted sm:inline"
                    >the wiki of influence</span
                >
            </Link>
            <div v-if="search" class="sm:w-96">
                <SearchBox />
            </div>
        </div>
    </header>
</template>
