const SponsoredCarousel = () => {
  const [properties, setProperties] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);

  useEffect(() => {
    const fetchSponsoredProperties = async () => {
      try {
        const response = await fetch('/wp-json/smart-search/v1/sponsored-properties');
        const data = await response.json();
        setProperties(data);
      } catch (error) {
        console.error('Erro ao carregar imóveis patrocinados:', error);
      }
    };

    fetchSponsoredProperties();

    const interval = setInterval(() => {
      setCurrentIndex(current => 
        current === properties.length - 1 ? 0 : current + 1
      );
    }, 3000);

    return () => clearInterval(interval);
  }, [properties.length]);

  if (properties.length === 0) return null;

  const visibleProperties = [];
  for (let i = 0; i < 3; i++) {
    const index = (currentIndex + i) % properties.length;
    visibleProperties.push(properties[index]);
  }

  return (
    <div className="w-full overflow-hidden bg-white rounded-lg shadow-lg">
      <div className="flex transition-transform duration-500 ease-in-out">
        {visibleProperties.map((property, index) => (
          <div key={property.id} className="w-1/3 p-4 flex-shrink-0">
            <a href={property.permalink} className="block">
              <div className="relative pb-[56.25%]">
                <img
                  src={property.thumbnail || '/wp-content/uploads/2025/02/no-image.png'}
                  alt={property.title}
                  className="absolute inset-0 w-full h-full object-cover rounded-t-lg"
                />
              </div>
              <div className="p-4">
                <h3 className="text-lg font-semibold mb-2 truncate">{property.title}</h3>
                <p className="text-sm text-gray-600 mb-2">{property.location}</p>
                <p className="text-lg font-bold text-primary">
                  R$ {parseFloat(property.amount).toLocaleString('pt-BR')}
                </p>
              </div>
            </a>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SponsoredCarousel;