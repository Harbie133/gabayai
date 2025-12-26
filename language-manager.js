// language-manager.js

// 1. Translation Dictionary (Dito mo ilalagay lahat ng text mo)
const translations = {
  en: {
    // Navbar
    nav_dashboard: "Dashboard",
    nav_analyzer: "Analyzer",
    nav_consult: "Consultation",
    nav_profile: "Profile",
    nav_logout: "Logout",
    
    // Analyzer Page
    lbl_age: "Age",
    lbl_gender: "Gender",
    lbl_height: "Height (cm)",
    lbl_weight: "Weight (kg)",
    lbl_symptoms: "Describe your symptoms",
    lbl_bp: "Blood Pressure",
    btn_continue: "Continue",
    btn_back: "Back",
    btn_next: "Next",
    btn_analyze: "Analyze Symptoms",
    ph_symptoms: "Describe what you feel (e.g. Headache since yesterday...)",
    
    // Add more keys here for other pages...
  },
  ph: {
    // Navbar
    nav_dashboard: "Berdanda", // Dashboard is often kept as is, but Berdanda/Tahanan is formal
    nav_analyzer: "Pagsusuri",
    nav_consult: "Konsultasyon",
    nav_profile: "Profile",
    nav_logout: "Mag-logout",

    // Analyzer Page
    lbl_age: "Edad",
    lbl_gender: "Kasarian",
    lbl_height: "Taas (cm)",
    lbl_weight: "Timbang (kg)",
    lbl_symptoms: "Ilarawan ang nararamdaman",
    lbl_bp: "Presyon ng Dugo",
    btn_continue: "Magpatuloy",
    btn_back: "Bumalik",
    btn_next: "Sunod",
    btn_analyze: "Suriin ang Sintomas",
    ph_symptoms: "Ikwento ang nararamdaman (Hal: Masakit ang ulo kahapon pa...)",
  }
};

// 2. Initialize Language on Load
document.addEventListener('DOMContentLoaded', () => {
  // Check localStorage or default to English
  const savedLang = localStorage.getItem('gabayai_lang') || 'en';
  applyLanguage(savedLang);
  
  // Update the dropdown UI to match saved language
  const selector = document.getElementById('global-lang-select');
  if (selector) selector.value = savedLang;
});

// 3. Function to Apply Language
function applyLanguage(lang) {
  // Save preference
  localStorage.setItem('gabayai_lang', lang);
  
  // Update all elements with 'data-i18n' attribute
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    if (translations[lang][key]) {
      // Check if it's an input/textarea with placeholder
      if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
        el.placeholder = translations[lang][key];
      } else {
        // Normal text
        el.innerText = translations[lang][key];
      }
    }
  });

  // Special handling for the Analyzer logic (Passing to backend)
  // We set a global variable that your analyzer script can read
  window.CURRENT_LANGUAGE = lang;
}

// 4. Function called by the HTML Selector
function changeGlobalLanguage(selectElement) {
  applyLanguage(selectElement.value);
}
