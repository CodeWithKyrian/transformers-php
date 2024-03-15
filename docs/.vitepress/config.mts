import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Transformers PHP",
    description: "State-of-the-art Machine Learning for PHP. Run Transformers in PHP",
    base: "/transformers-docs/",
    themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
            {text: 'Home', link: '/'},
            {text: 'Docs', link: '/docs/'},
            {
                text: '0.1.x',
                items: [
                    {
                        text: 'Changelog',
                        link: 'https://github.com/CodeWithKyrian/transformers-php/blob/main/CHANGELOG.md'
                    },
                    {
                        text: 'Contributing',
                        link: 'https://github.com/CodeWithKyrian/transformers-php/blob/main/.github/contributing.md'
                    },
                ]
            }
        ],

        sidebar: [
            {
                text: 'Getting Started',
                collapsed: false,
                items: [
                    {text: 'Introduction', link: '/docs/'},
                    {text: 'Getting Started', link: '/docs/getting-started'},
                    {text: 'Basic Usage', link: '/docs/basic-usage'},
                    {text: 'Configuration', link: '/docs/configuration'},
                ]
            },
            {
                text: 'Pipelines',
                collapsed: false,
                link: '/docs/pipelines',
                items: [
                    {text: 'Text Classification', link: '/docs/text-classification'},
                    {text: 'Fill Mask', link: '/docs/fill-mask'},
                    {text: 'Zero Shot Classification', link: '/docs/zero-shot-classification'},
                    {text: 'Question Answering', link: '/docs/question-answering'},
                    {text: 'Token Classification', link: '/docs/token-classification'},
                    {text: 'Feature Extraction', link: '/docs/feature-extraction'},
                    {text: 'Text to Text Generation', link: '/docs/text-to-text-generation'},
                    {text: 'Translation', link: '/docs/translation'},
                    {text: 'Summarization', link: '/docs/summarization'},
                    {text: 'Text Generation', link: '/docs/text-generation'},
                ]
            },
            {
                text: 'Advanced Usage',
                collapsed: false,
                items: [
                    {text: 'Auto Models', link: '/docs/auto-models'},
                    {text: 'Auto Tokenizers', link: '/docs/auto-tokenizers'},
                ]
            },
            {
                text: 'Utilities',
                collapsed: false,
                items: [
                    {text: 'Generation', link: '/docs/generation'},
                ]
            }
        ],

        socialLinks: [
            {icon: 'github', link: 'https://github.com/CodeWithKyrian/transformers-php'},
            {icon: 'twitter', link: 'https://twitter.com/CodeWithKyrian'}
        ],

        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2024 <a href="https://github.com/yyx990803">Kyrian Obikwelu</a>'
        },

        editLink: {
            pattern: 'https://github.com/vuejs/vitepress/edit/main/docs/:path',
            text: 'Edit this page on GitHub'
        },

        search: {
            provider: 'local'
        }
    },
    cleanUrls: true,
    lastUpdated: true,
    markdown: {
        theme: {light: 'min-light', dark: 'min-dark'},
    }
})
