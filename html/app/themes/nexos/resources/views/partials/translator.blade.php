<div class="has-text-primary has-padding-top-10">
    <a data-languages="es" class="has-padding-right-5 is-small-text notranslate" style="pointer-events: auto;">ES</a> /
    <a data-languages="en" class="mx-2 is-small-text notranslate" style="pointer-events: auto;">EN</a> /
    <a data-languages="fr" class="mx-2 is-small-text notranslate" style="pointer-events: auto;">FR</a> /
    <a data-languages="ko" class="mx-2 is-small-text notranslate" style="pointer-events: auto;">KO</a> /
    <a data-languages="pt" class="mx-2 is-small-text notranslate" style="pointer-events: auto;">POR</a> <br>
    <div class="is-opaque is-30" id="google_translate_element"></div>
</div>


<script defer src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script defer type="text/javascript">
function googleTranslateElementInit() {
    new google.translate.TranslateElement({pageLanguage: 'es'}, 'google_translate_element');
}

const changeLanguage = (lang = lang || 'es') => {
    const combo =  document.querySelector('.goog-te-combo')
    const e = new Event("change");
    combo.value = lang; 
    combo.dispatchEvent(e); 
} 
 
const languages = document.querySelectorAll('[data-languages]')

languages.forEach(lang => {
    lang.addEventListener('click', function(e) {
        e.preventDefault() 
        changeLanguage(lang.dataset.languages)
    })
})


</script>

<style>
    .skiptranslate {
        display: none !important;
    } 

    #google_translate_element .skiptranslate{
        display: block !important;
    }

    body {
        top:0 !important;
    } 

    .navbar .goog-logo-link img, .navbar.aos-animate .goog-logo-link img {
        width: 37px !important;
    }

    .goog-te-combo {
        display: none !important; 
    }
</style>