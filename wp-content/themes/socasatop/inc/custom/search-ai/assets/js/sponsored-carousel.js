document.addEventListener('DOMContentLoaded', function() {
  const { useState, useEffect } = React;

  const SponsoredCarousel = () => {
      const [properties] = useState(window.sponsoredCarouselConfig?.properties || []);
      const [currentIndex, setCurrentIndex] = useState(0);

      useEffect(() => {
          if (properties.length <= 1) return;

          const interval = setInterval(() => {
              setCurrentIndex(current => (current + 1) % properties.length);
          }, 5000);

          return () => clearInterval(interval);
      }, [properties.length]);

      if (properties.length === 0) return null;

      console.log('Total de imóveis destacados:', properties.length);

      const getVisibleProperties = () => {
          if (properties.length <= 3) {
              return properties;
          }
          
          const visibleItems = [];
          for (let i = 0; i < 3; i++) {
              const index = (currentIndex + i) % properties.length;
              visibleItems.push(properties[index]);
          }
          return visibleItems;
      };

      const formatCurrency = (value) => {
          if (!value) return 'Sob consulta';
          return new Intl.NumberFormat('pt-BR', {
              style: 'currency',
              currency: 'BRL'
          }).format(value);
      };
      
      const goToPrevious = () => {
          setCurrentIndex(current => 
              current === 0 ? properties.length - 1 : current - 1
          );
      };
      
      const goToNext = () => {
          setCurrentIndex(current => 
              (current + 1) % properties.length
          );
      };

      return React.createElement('div', {
          className: 'sponsored-carousel w-full max-w-7xl mx-auto px-4 relative'
      }, [
          React.createElement('h2', {
              className: 'text-2xl font-bold mb-6 text-center'
          }, 'Imóveis em Destaque'),
          
          properties.length > 3 && React.createElement('button', {
              className: 'carousel-nav prev absolute left-0 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center z-10 hover:bg-blue-700 focus:outline-none',
              onClick: goToPrevious,
              'aria-label': 'Anterior'
          }, '←'),
          
          React.createElement('div', {
              className: 'grid grid-cols-1 md:grid-cols-3 gap-6 carrosel-patrocinados'
          }, getVisibleProperties().map((property, index) => 
              React.createElement('div', {
                  key: `${property.id}-${index}`,
                  className: 'imovel bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl'
              }, 
                  React.createElement('a', {
                      href: property.permalink,
                      className: 'block'
                  }, [
                      React.createElement('div', {
                          className: 'thumb-wrapper relative aspect-video'
                      }, [
                          React.createElement('img', {
                              src: property.thumbnail || '/wp-content/uploads/2025/02/no-image.png',
                              alt: property.title,
                              className: 'absolute inset-0 w-full h-full object-cover'
                          })
                      ]),
                      React.createElement('div', {
                          className: 'content-wrapper p-4'
                      }, [
                          React.createElement('h3', {
                              className: 'titulo text-lg font-semibold mb-2 line-clamp-2'
                          }, property.title),
                          React.createElement('p', {
                              className: 'text-gray-600 mb-2'
                          }, property.location),
                          React.createElement('p', {
                              className: 'text-xl font-bold text-blue-600'
                          }, formatCurrency(property.amount))
                      ])
                  ])
              )
          )),
          
          properties.length > 3 && React.createElement('button', {
              className: 'carousel-nav next absolute right-0 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white rounded-full w-10 h-10 flex items-center justify-center z-10 hover:bg-blue-700 focus:outline-none',
              onClick: goToNext,
              'aria-label': 'Próximo'
          }, '→'),
          
          properties.length > 3 && React.createElement('div', {
              className: 'pagination-dots flex justify-center mt-4 space-x-2'
          }, Array.from({ length: properties.length }).map((_, i) => 
              React.createElement('button', {
                  key: `dot-${i}`,
                  className: `pagination-dot w-3 h-3 rounded-full ${currentIndex === i ? 'bg-blue-600' : 'bg-gray-300'}`,
                  onClick: () => setCurrentIndex(i),
                  'aria-label': `Ir para slide ${i + 1}`
              })
          ))
      ]);
  };

  const container = document.getElementById('sponsored-carousel-root');
  if (container) {
      ReactDOM.render(React.createElement(SponsoredCarousel), container);
  }
  
  const style = document.createElement('style');
  style.textContent = `
      .sponsored-carousel {
          position: relative;
          padding: 0 40px;
      }
      .carousel-nav {
          opacity: 0.7;
          transition: opacity 0.3s;
      }
      .carousel-nav:hover {
          opacity: 1;
      }
      .carrosel-patrocinados {
          min-height: 300px;
      }
      @media (max-width: 768px) {
          .sponsored-carousel {
              padding: 0 20px;
          }
          .carousel-nav {
              width: 30px;
              height: 30px;
          }
      }
  `;
  document.head.appendChild(style);
});