<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import BeefreeSDK from '@beefree.io/sdk';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' }
];

const isBuilderVisible = ref(false);
const beeEditor = ref<BeefreeSDK | null>(null);
const BASE_TEMPLATE = 'https://rsrc.getbee.io/api/templates/m-bee';

// get json template
async function fetchTemplate() {
    const response = await fetch(BASE_TEMPLATE);
    return await response.json();
}

async function showBuilder() {
    try {
        isBuilderVisible.value = true;

        // create instance
        beeEditor.value = new BeefreeSDK();

        // api keys
        const credentials = {
            clientId: 'a954f910-500d-49e2-a499-fd44bf1e6d2e',
            clientSecret: 'Y0XlxEDcBgFEg9yrFP64o58jlMeJS0qzkCkn6OotPBIJ57fLxhT2',
        };
        console.log('api keys', credentials);

        // authenticate instance using api keys
        await beeEditor.value.getToken(credentials.clientId, credentials.clientSecret);

        // get template
        const template = await fetchTemplate();

        // start authenticated instance with template
        beeEditor.value.start({
            uid: 'username1',
            container: 'bee-plugin-container',
            language: 'en-US',
            autosave: true
        }, template);

    } catch (error) {
        console.error('Initialization failed:', error);
        alert('Failed to load editor. Check console for details.');
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl">
            <div
                v-if="isBuilderVisible"
                id="bee-plugin-container"
                class="w-full h-[800px] border"></div>
            <div v-else>
                <button
                    @click="showBuilder"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Show Email Builder
                </button>
            </div>
        </div>
    </AppLayout>
</template>
