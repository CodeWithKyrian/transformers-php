import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Transformers PHP",
    description: "State-of-the-art Machine Learning for PHP. Run Transformers in PHP",
    base: "/transformers-php/",
    themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
            {text: 'Home', link: '/'},
            {text: 'Docs', link: '/introduction'},
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
                    {text: 'Introduction', link: '/introduction'},
                    {text: 'Getting Started', link: '/getting-started'},
                    {text: 'Basic Usage', link: '/basic-usage'},
                    {text: 'Configuration', link: '/configuration'},
                ]
            },
            {
                text: 'Pipelines',
                collapsed: false,
                link: '/pipelines',
                items: [
                    {text: 'Text Classification', link: '/text-classification'},
                    {text: 'Fill Mask', link: '/fill-mask'},
                    {text: 'Zero Shot Classification', link: '/zero-shot-classification'},
                    {text: 'Question Answering', link: '/question-answering'},
                    {text: 'Token Classification', link: '/token-classification'},
                    {text: 'Feature Extraction', link: '/feature-extraction'},
                    {text: 'Text to Text Generation', link: '/text-to-text-generation'},
                    {text: 'Translation', link: '/translation'},
                    {text: 'Summarization', link: '/summarization'},
                    {text: 'Text Generation', link: '/text-generation'},
                ]
            },
            {
                text: 'Advanced Usage',
                collapsed: false,
                items: [
                    {text: 'Auto Models', link: '/auto-models'},
                    {text: 'Auto Tokenizers', link: '/auto-tokenizers'},
                ]
            },
            {
                text: 'Utilities',
                collapsed: false,
                items: [
                    {text: 'Generation', link: '/generation'},
                ]
            }
        ],

        socialLinks: [
            {icon: 'github', link: 'https://github.com/CodeWithKyrian/transformers-php'},
            {icon: 'twitter', link: 'https://twitter.com/CodeWithKyrian'}
        ],

        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2024 <a href="https://github.com/CodeWithKyrian">Kyrian Obikwelu</a>'
        },

        editLink: {
            pattern: 'https://github.com/CodeWithKyrian/transformers-php/edit/main/docs/:path',
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
