<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CdxButton, CdxField, CdxTextInput } from '@wikimedia/codex';
import WikiLayout from '@/components/WikiLayout.vue';

const form = useForm({ password: '' });
</script>

<template>
    <Head title="Admin" />
    <WikiLayout>
        <main class="max-w-sm py-6">
            <form class="space-y-4" @submit.prevent="form.post('/admin/login')">
                <h1 class="border-b border-rule pb-1 font-serif text-[1.9rem]">
                    Editor login
                </h1>
                <CdxField
                    :status="form.errors.password ? 'error' : 'default'"
                    :messages="{ error: form.errors.password }"
                >
                    <template #label>Password</template>
                    <CdxTextInput
                        v-model="form.password"
                        input-type="password"
                    />
                </CdxField>
                <CdxButton
                    type="submit"
                    action="progressive"
                    weight="primary"
                    :disabled="form.processing"
                >
                    Log in
                </CdxButton>
            </form>
        </main>
    </WikiLayout>
</template>
