// BBR Fragrance NY - Lightweight i18n toggle for public navigation/hero

(function () {
    const STORAGE_KEY = 'bbr_lang';
    const defaultLang = 'es';

    const messages = {
        es: {
            'nav.home': 'Inicio',
            'nav.categories': 'Categorias',
            'nav.products': 'Productos',
            'nav.promotions': 'Promociones',
            'nav.about': 'Nosotros',
            'nav.contact': 'Contacto',
            'hero.subtitle': 'Fragancias de lujo con actitud de New York',
            'hero.badge': 'Sede principal en New York City',
            'hero.shopNow': 'Comprar ahora',
            'hero.whatsapp': 'WhatsApp'
        },
        en: {
            'nav.home': 'Home',
            'nav.categories': 'Categories',
            'nav.products': 'Products',
            'nav.promotions': 'Promotions',
            'nav.about': 'About',
            'nav.contact': 'Contact',
            'hero.subtitle': 'Luxury scents with New York attitude',
            'hero.badge': 'New York City flagship',
            'hero.shopNow': 'Shop now',
            'hero.whatsapp': 'WhatsApp'
        }
    };

    function getText(lang, key) {
        return messages[lang]?.[key] || messages.es[key] || key;
    }

    function applyLanguage(lang) {
        document.documentElement.lang = lang;

        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            el.textContent = getText(lang, key);
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
            const key = el.getAttribute('data-i18n-placeholder');
            el.setAttribute('placeholder', getText(lang, key));
        });

        document.querySelectorAll('[data-i18n-attr]').forEach((el) => {
            const mapping = el.getAttribute('data-i18n-attr');
            if (!mapping) return;
            const [attr, key] = mapping.split(':');
            if (attr && key) {
                el.setAttribute(attr.trim(), getText(lang, key.trim()));
            }
        });

        const nextLangLabel = lang === 'es' ? 'EN' : 'ES';
        const desktopToggle = document.getElementById('language-toggle-text');
        const mobileToggle = document.getElementById('language-toggle-mobile-text');
        if (desktopToggle) desktopToggle.textContent = nextLangLabel;
        if (mobileToggle) mobileToggle.textContent = nextLangLabel;

        localStorage.setItem(STORAGE_KEY, lang);
    }

    function toggleLanguage() {
        const current = localStorage.getItem(STORAGE_KEY) || defaultLang;
        applyLanguage(current === 'es' ? 'en' : 'es');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const saved = localStorage.getItem(STORAGE_KEY) || defaultLang;
        applyLanguage(saved);

        const desktopToggle = document.getElementById('language-toggle');
        const mobileToggle = document.getElementById('language-toggle-mobile');

        desktopToggle?.addEventListener('click', toggleLanguage);
        mobileToggle?.addEventListener('click', toggleLanguage);
    });
})();
