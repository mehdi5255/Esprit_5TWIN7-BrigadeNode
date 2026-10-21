// Thème Tailwind du Back Office (chargé juste après le CDN Tailwind).
tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['DM Sans', 'sans-serif'], display: ['Manrope', 'sans-serif'] },
            colors: { ink: '#17211d', forest: { 50: '#f2f7f4', 100: '#deeee4', 500: '#2f7d57', 600: '#256747', 700: '#1f523b', 900: '#17382b' } }
        }
    }
};
