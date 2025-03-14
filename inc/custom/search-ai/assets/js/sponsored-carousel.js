document.addEventListener('DOMContentLoaded', function() {
  const { useState, useEffect } = React;

  const SponsoredCarousel = () => {
      const [properties] = useState(window.sponsoredCarouselConfig?.properties || []);
      const [currentIndex, setCurrentIndex] = useState(0);

      useEffect(() => {
          if (properties.length <= 3) return;

          const interval = setInterval(() => {
              setCurrentIndex(current => (current + 1) % Math.max(0, properties.length - 2));
          }, 5000);

          return () => clearInterval(interval);
      }, [properties.length]);

      if (properties.length === 0) return null;

      const getVisibleProperties = () => {
          const visibleItems = [];
          for (let i = 0; i < Math.min(3, properties.length); i++) {
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

      return React.createElement('div', {
          className: 'sponsored-carousel w-full max-w-7xl mx-auto px-4'
      }, [
          React.createElement('h2', {
              className: 'text-2xl font-bold mb-6 text-center'
          }, 'Imóveis em Destaque'),
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
          ))
      ]);
  };

  // Montagem do componente
  const container = document.getElementById('sponsored-carousel-root');
  if (container) {
      ReactDOM.render(React.createElement(SponsoredCarousel), container);
  }
});