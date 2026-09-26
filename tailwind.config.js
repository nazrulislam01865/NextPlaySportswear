// Keep Tailwind brand utilities on the same canonical CSS variables used by
// handcrafted storefront CSS. Relative rgb() preserves Tailwind opacity
// modifiers such as bg-brand-red/10 without duplicating the palette here.
const themeColor = (token) => `rgb(from var(${token}) r g b / <alpha-value>)`;

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/View/Components/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--np-font-body)'],
                display: ['var(--np-font-heading)'],
            },

            colors: {
                brand: {
                    navy: themeColor('--np-color-primary'),
                    dark: themeColor('--np-color-primary'),
                    blue: themeColor('--np-color-primary'),
                    red: themeColor('--np-color-secondary'),
                    redDark: themeColor('--np-color-secondary-hover'),
                    ink: themeColor('--np-color-heading'),
                    body: themeColor('--np-color-body'),
                    muted: themeColor('--np-color-muted'),
                    page: themeColor('--np-color-page'),
                    soft: themeColor('--np-color-soft'),
                    border: themeColor('--np-color-border'),
                    success: themeColor('--np-color-success'),
                    warning: themeColor('--np-color-warning'),
                    error: themeColor('--np-color-error'),
                },
            },

            boxShadow: {
                soft: '0 8px 22px rgba(15,23,42,.08)',
                card: '0 6px 18px rgba(15,23,42,.05)',
                hero: '0 14px 36px rgba(15,23,42,.10)',
            },
        },
    },

    plugins: [],
};
