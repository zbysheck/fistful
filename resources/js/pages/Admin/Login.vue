<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SiteHeader from '@/components/SiteHeader.vue';

const form = useForm({ password: '' });
</script>

<template>
    <Head title="Admin" />
    <SiteHeader :search="false" />
    <main class="mx-auto max-w-sm px-5 py-16">
        <form class="space-y-4" @submit.prevent="form.post('/admin/login')">
            <input
                v-model="form.password"
                type="password"
                placeholder="Password"
                class="w-full border-2 border-ink bg-paper px-3 py-2"
            />
            <p v-if="form.errors.password" class="text-blood">
                {{ form.errors.password }}
            </p>
            <button
                class="border-2 border-ink bg-ink px-5 py-2 text-paper hover:bg-blood"
                :disabled="form.processing"
            >
                Log in
            </button>
        </form>
    </main>
</template>
