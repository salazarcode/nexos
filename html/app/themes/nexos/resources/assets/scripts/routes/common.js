import AOS from 'aos';
import 'aos/dist/aos.css';
import feather from 'feather-icons';
import {jarallax, jarallaxVideo} from 'jarallax';
import {locomo} from './loco.js'
export default {
  init() {
    const $ = (el, parent) => (parent || document).querySelector(el);
    const $$ = (el, parent) => (parent || document).querySelectorAll(el);

    AOS.init()
    feather.replace()
    jarallaxVideo()
    locomo()

    /** Bulma 
     * @docs https://bulmajs.tomerbe.co.uk/docs/0.11/1-getting-started/1-introduction/
     */
    
    
    /**Parallax */

    jarallax(document.querySelectorAll('.is-parallax-contain'), {
      speed: 0.9,
      imgSize: 'cover',
      imgPosition: '25% 50%',
    })

    jarallax(document.querySelectorAll('.is-parallax-cover'), {
      speed: 0.4,
      imgSize: 'cover',
      imgPosition: '25% 50%',
    })

    document.querySelectorAll('.is-parallax-video').forEach(element => {
      jarallax(element, {
        speed: 0.4,
        videoSrc: `mp4:${element.dataset.url}`
      });
    });
    window.onmousemove = (e)=>{
      $('.pointer-sombra').style.transform = `translate3d(${e.clientX}px, ${e.clientY}px, 0)`
    }
    

    $$('.menu-item a').forEach(el=>{
      el.dataset.text = el.textContent;
    })

    $('.open-menu').onclick =()=>{
      $('.open-menu').classList.toggle('active')
      $('.menu-desplegable').classList.toggle('active')
      $('nav').classList.toggle('open')
    }
  },  
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
