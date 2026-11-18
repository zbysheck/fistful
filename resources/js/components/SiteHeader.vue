<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SearchBox from '@/components/SearchBox.vue';

withDefaults(defineProps<{ search?: boolean }>(), { search: true });
</script>

<template>
    <header class="border-b-4 border-double border-ink">
        <div
            class="mx-auto flex max-w-4xl flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <Link
                href="/"
                class="font-logo text-3xl tracking-wide text-ink no-underline"
            >
                Fistful <span class="text-blood">of</span> Ideas
            </Link>
            <div v-if="search" class="sm:w-80">
                <SearchBox />
            </div>
        </div>
    </header>
</template>
