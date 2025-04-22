<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import axios from 'axios';
import BeefreeSDK from '@beefree.io/sdk';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const loading = ref(true);
const token = ref<string | null>(null);

const getBasicTemplate = () => ({

  "page": {
    "title": "",
    "description": "",
    "template": {
      "name": "template-base",
      "type": "basic",
      "version": "0.0.1"
    },
    "body": {
      "type": "mailup-bee-page-proprerties",
      "container": {
        "style": {
          "background-color": "#FFFFFF"
        }
      },
      "content": {
        "style": {
          "font-family": "Arial, 'Helvetica Neue', Helvetica, sans-serif",
          "color": "#000000"
        },
        "computedStyle": {
          "linkColor": "#0068A5",
          "messageBackgroundColor": "transparent",
          "messageWidth": "500px"
        }
      }
    },
    "rows": [
      {
        "type": "one-column-empty",
        "container": {
          "style": {
            "background-color": "transparent"
          }
        },
        "content": {
          "style": {
            "background-color": "transparent",
            "color": "#000000",
            "width": "500px"
          }
        },
        "columns": [
          {
            "grid-columns": 12,
            "modules": [
              {
                "type": "mailup-bee-newsletter-modules-empty",
                "descriptor": {}
              }
            ],
            "style": {
              "background-color": "transparent",
              "padding-top": "5px",
              "padding-right": "0px",
              "padding-bottom": "5px",
              "padding-left": "0px",
              "border-top": "0px dotted transparent",
              "border-right": "0px dotted transparent",
              "border-bottom": "0px dotted transparent",
              "border-left": "0px dotted transparent"
            }
          }
        ]
      }
    ]
  }
});

onMounted(async () => {
    try {
        const response = await axios.post('/beefree/token');
        token.value = response.data.token;
        console.log('Token received:', token.value);

        if (token.value) {
            const beeConfig = {
                uid: 'test1-clientside',
                container: 'bee-plugin-container',
                language: 'en-US',
                onSave: (jsonFile, htmlFile) => {
                    console.log('onSave', jsonFile, htmlFile);
                },
                onSaveAsTemplate: (jsonFile) => {
                    console.log('onSaveAsTemplate', jsonFile);
                },
                onSend: (htmlFile) => {
                    console.log('onSend', htmlFile);
                },
                onError: (errorMessage) => {
                    console.log('onError', errorMessage);
                }
            };

            const beeTest = new BeefreeSDK(token.value);
            const template = getBasicTemplate();
            console.log('Using template:', template);

            await beeTest.start(beeConfig, template);

            console.log('Beefree SDK initialized successfully', beeTest);
            loading.value = false;
        }
    } catch (error) {
        console.error('API failed:', {
            status: error.response?.status,
            data: error.response?.data,
            fullError: error
        });
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div v-if="loading">Loading editor...</div>
            <div v-else id="bee-plugin-container" class="w-full h-[800px] border border-gray-300"></div>
        </div>
    </AppLayout>
</template>
