import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "TransformersPHP",
    description: "State-of-the-art Machine Learning for PHP. Run Transformers in PHP",
    base: "/",
    themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
            {text: 'Home', link: '/'},
            {text: 'Docs', link: '/introduction'},
            {
                text: '0.3.x',
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
                    {
                        text: 'NLP Tasks',
                        collapsed: true,
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
                        text: 'Computer Vision Tasks',
                        collapsed: true,
                        items: [
                            {text: 'Image Classification', link: '/image-classification'},
                            {text: 'Zero Shot Image Classification', link: '/zero-shot-image-classification'},
                            {text: 'Object Detection', link: '/object-detection'},
                            {text: 'Zero Shot Object Detection', link: '/zero-shot-object-detection'},
                            {text: 'Image Feature Extraction', link: '/image-feature-extraction'},
                            {text: 'Image To Text', link: '/image-to-text'},
                            {text: 'Image To Image', link: '/image-to-image'},
                        ]
                    },
                    {
                        text: 'Audio Tasks',
                        collapsed: true,
                        items: [
                            {text: 'Audio Classification', link: '/audio-classification'},
                            {text: 'Automatic Speech Recognition', link: '/automatic-speech-recognition'},
                        ]
                    }
                ]
            },
            {
                text: 'Advanced Usage',
                collapsed: false,
                items: [
                    {text: 'Models', link: '/models'},
                    {text: 'Tokenizers', link: '/tokenizers'},
                ]
            },
            {
                text: 'Utilities',
                collapsed: false,
                items: [
                    {text: 'Generation', link: '/utils/generation'},
                    {text: 'Image', link: '/utils/image'},
                    {text: 'Tensor', link: '/utils/tensor'},
                ]
            }
        ],

        socialLinks: [
            {icon: 'github', link: 'https://github.com/CodeWithKyrian/transformers-php'},
            {icon: 'twitter', link: 'https://twitter.com/CodeWithKyrian'}
        ],

        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2024 <a href="https://twitter.com/CodeWithKyrian">Kyrian Obikwelu</a>'
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
    },
    head: [
        [
            'script',
            {
                'defer': '',
                'data-domain': 'codewithkyrian.github.io/transformers-php',
                'src': 'https://analytics.codewithkyrian.com/js/script.js'
            }
        ]
    ]
})
