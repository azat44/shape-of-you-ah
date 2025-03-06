
document.addEventListener("DOMContentLoaded", function () {
    const themeButtons = document.querySelectorAll('.theme-toggle');
    const htmlElement = document.documentElement;
    
    const themes = ["default", "dark", "theme-one", "theme-two"];
    
    let currentTheme = localStorage.getItem("theme") || "default";
    
    if (!themes.includes(currentTheme)) {
        currentTheme = "default";
        localStorage.setItem("theme", currentTheme);
    }
    
    function updateThemeButtons(activeTheme) {
        themeButtons.forEach(button => {
            const buttonTheme = button.getAttribute('data-theme');
            
            button.classList.remove(
                'bg-primary', 'bg-gray-200', 'text-white', 'text-gray-700', 
                'dark:text-gray-300', 'ring-2', 'ring-primary-light', 'ring-blue-300',
                'bg-dark-bg', 'bg-fashion-primary', 'bg-nature-primary',
                'ring-blue-400', 'ring-fashion-accent', 'ring-nature-accent'
            );
            
            if (buttonTheme === activeTheme) {
                switch (activeTheme) {
                    case 'dark':
                        button.classList.add('bg-gray-800', 'text-white', 'ring-2', 'ring-blue-400');
                        break;
                    case 'theme-one':
                        button.classList.add('bg-themeOnePrimary', 'text-white', 'ring-2', 'ring-themeOneBorder');
                        break;
                    case 'theme-two':
                        button.classList.add('bg-themeTwoPrimary', 'text-white', 'ring-2', 'ring-themeTwoBorder');
                        break;
                    default:
                        button.classList.add('bg-primary', 'text-white', 'ring-2', 'ring-blue-300');
                        break;
                }
            } else {
                button.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                
                if (activeTheme === 'dark') {
                    button.classList.add('dark:bg-gray-700', 'dark:text-gray-300', 'dark:hover:bg-gray-600');
                } else if (activeTheme === 'theme-one') {
                    button.classList.add('theme-one:bg-themeOneBg/50', 'theme-one:text-themeOneText');
                } else if (activeTheme === 'theme-two') {
                    button.classList.add('theme-two:bg-themeTwoBg/50', 'theme-two:text-themeTwoText');
                }
            }
        });
    }
    
    function setTheme(theme) {
        htmlElement.classList.remove('dark', 'theme-one', 'theme-two');
        
        if (theme === 'dark') {
            htmlElement.classList.add('dark');
        } else if (theme === 'theme-one') {
            htmlElement.classList.add('theme-one');
        } else if (theme === 'theme-two') {
            htmlElement.classList.add('theme-two');
        }
        
        localStorage.setItem("theme", theme);
        currentTheme = theme;
        
        updateThemeButtons(theme);
    }
    
    themeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            setTheme(theme);
        });
    });
    
    setTheme(currentTheme);
    
    if (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        setTheme('dark');
    }
});