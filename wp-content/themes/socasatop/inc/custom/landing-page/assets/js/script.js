(function($) {
  'use strict';
  
  function initAnimations() {
      const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
              if (entry.isIntersecting) {
                  entry.target.classList.add('sct-animate');
              }
          });
      }, {
          threshold: 0.1
      });

      document.querySelectorAll('.sct-stat-card, .sct-feature-card, .sct-step').forEach((el) => {
          observer.observe(el);
      });
  }

  function initSmoothScroll() {
      $('a[href^="#"]').on('click', function(e) {
          e.preventDefault();
          const target = $(this.getAttribute('href'));
          
          if(target.length) {
              $('html, body').animate({
                  scrollTop: target.offset().top - 100
              }, 800);
          }
      });
  }

  $(document).ready(function() {
      initAnimations();
      initSmoothScroll();
  });
})(jQuery);