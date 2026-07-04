import defaultTheme from 'tailwindcss/defaultTheme';

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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                // Permanent typography fix: keep display text on Inter too.
                // Oswald made headings/menus look too bold after every Vite build.
                display: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            // Permanent typography fix: make Tailwind generate lighter weights.
            // This prevents font-black/font-extrabold/font-bold classes in Blade
            // from becoming heavy again after npm run build.
            fontWeight: {
                thin: '100',
                extralight: '200',
                light: '300',
                normal: '400',
                medium: '500',
                semibold: '550',
                bold: '600',
                extrabold: '650',
                black: '650',
            },

            colors: {
                brand: {
                    navy: '#15345d',
                    dark: '#0d2545',
                    blue: '#2467b7',
                    red: '#e91d33',
                    redDark: '#c9182b',
                    ink: '#111827',
                    muted: '#64748b',
                    soft: '#f4f6f8',
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
