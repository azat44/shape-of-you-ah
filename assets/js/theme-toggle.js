document.addEventListener("DOMContentLoaded", function () {
    const themeSwitch = document.getElementById("theme-switch");
    const iconSun = document.getElementById("theme-icon-sun");
    const iconMoon = document.getElementById("theme-icon-moon");
    const iconCustom = document.getElementById("theme-icon-custom");
    const html = document.documentElement;
    
    if (!themeSwitch || !iconSun || !iconMoon || !iconCustom) {
        console.warn("⚠️ Bouton de switch de thème ou icônes non trouvés.");
        return;
    }
    
    const themes = ["light", "dark", "theme-one", "theme-two"];
    let currentTheme = localStorage.getItem("theme") || "light";
    
    if (!themes.includes(currentTheme)) {
        currentTheme = "light";
        localStorage.setItem("theme", currentTheme);
    }
    
    function cleanupThemeClasses() {
        html.classList.remove(...themes);
        document.body.classList.remove("dark");
        
        const textElements = document.querySelectorAll('*');
        textElements.forEach(el => {
            el.classList.remove(
                'text-gray-800', 'text-black', 'text-white', 
                'text-themeOneText', 'text-themeTwoText',
                'dark:text-white', 
                'theme-one:text-themeOneText', 
                'theme-two:text-themeTwoText'
            );
        });
    }
    
    function applyTheme(theme) {
        cleanupThemeClasses();
        html.classList.add(theme);
        
        const allTextElements = document.querySelectorAll('body, p, span, div, h1, h2, h3, h4, h5, h6, label, a, button, input, textarea');
        
        allTextElements.forEach(el => {
            switch(theme) {
                case 'dark':
                    el.classList.add('text-white');
                    break;
                case 'theme-one':
                    el.classList.add('text-themeOneText');
                    break;
                case 'theme-two':
                    el.classList.add('text-themeTwoText');
                    break;
                default: 
                    el.classList.add('text-gray-800');
            }
        });
        
        if (theme === 'dark') {
            document.body.classList.add('dark');
        }
        
        localStorage.setItem("theme", theme);
        currentTheme = theme;
        updateThemeIcons(theme);
    }
    
    function updateThemeIcons(theme) {
        [iconSun, iconMoon, iconCustom].forEach(icon => icon.classList.add('hidden'));
        
        if (theme === 'light') {
            iconSun.classList.remove('hidden');
        } else if (theme === 'dark') {
            iconMoon.classList.remove('hidden');
        } else {
            iconCustom.classList.remove('hidden');
            
            iconCustom.classList.remove('text-blue-500', 'text-yellow-500');
            iconCustom.classList.add(theme === 'theme-one' ? 'text-yellow-500' : 'text-blue-500');
        }
    }
    
    applyTheme(currentTheme);
    
    themeSwitch.addEventListener("click", () => {
        const currentIndex = themes.indexOf(currentTheme);
        const nextIndex = (currentIndex + 1) % themes.length;
        const nextTheme = themes[nextIndex];
        
        applyTheme(nextTheme);
    });
    
    const observer = new MutationObserver(() => {
        applyTheme(currentTheme);
    });
    
    observer.observe(document.body, { 
        childList: true, 
        subtree: true 
    });
});