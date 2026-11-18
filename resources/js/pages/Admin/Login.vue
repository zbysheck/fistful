<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import WikiLayout from '@/components/WikiLayout.vue';

const form = useForm({ password: '' });
</script>

<template>
    <Head title="Admin" />
    <WikiLayout>
        <main class="max-w-sm py-6">
            <form class="space-y-4" @submit.prevent="form.post('/admin/login')">
                <input
                    v-model="form.password"
                    type="password"
                    placeholder="Password"
                    class="w-full rounded-sm border border-rule px-3 py-2"
                />
                <p v-if="form.errors.password" class="text-redlink">
                    {{ form.errors.password }}
                </p>
                <button
                    class="rounded-sm border border-rule bg-soft px-4 py-1.5 hover:bg-page"
                    :disabled="form.processing"
                >
                    Log in
                </button>
            </form>
        </main>
    </WikiLayout>
</template>
