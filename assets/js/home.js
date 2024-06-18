import 'swiper/swiper-bundle.css';
import '../styles/page/home.scss';

document.addEventListener('DOMContentLoaded', () => {
    const swiper = new Swiper(".mySwiper", {
        effect: "coverflow",
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: "auto",

        coverflowEffect: {
            rotate: 50,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: false
        },
        pagination: {
            el: ".swiper-pagination"
        }
    });

});
document.querySelector('.arrow').addEventListener('click', function(event) {
    event.preventDefault();
    document.querySelector('#accueilInstructor').scrollIntoView({
        behavior: 'smooth'
    });
});
