import React, { useState, useEffect } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const BrokerDashboard = () => {
  const [metrics, setMetrics] = useState([]);
  const [properties, setProperties] = useState([]);
  const [selectedProperties, setSelectedProperties] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchMetrics();
    fetchProperties();
  }, []);

  const fetchMetrics = async () => {
    try {
      const response = await fetch(`${window.site.ajax_url}?action=get_broker_metrics`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nonce=${window.site.nonce}`
      });
      const data = await response.json();
      setMetrics(data.metrics);
    } catch (error) {
      console.error('Erro ao carregar métricas:', error);
    }
  };

  const fetchProperties = async () => {
    try {
      const response = await fetch(`${window.site.ajax_url}?action=get_broker_properties`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nonce=${window.site.nonce}`
      });
      const data = await response.json();
      setProperties(data.properties);
      setLoading(false);
    } catch (error) {
      console.error('Erro ao carregar imóveis:', error);
      setLoading(false);
    }
  };

  const handlePropertySelect = (propertyId) => {
    setSelectedProperties(prev => 
      prev.includes(propertyId) 
        ? prev.filter(id => id !== propertyId)
        : [...prev, propertyId]
    );
  };

  const handleCheckout = () => {
    if (selectedProperties.length === 0) {
      alert('Selecione pelo menos um imóvel para patrocinar');
      return;
    }
    window.location.href = `/checkout?properties=${selectedProperties.join(',')}`;
  };

  if (loading) {
    return <div className="text-center p-8">Carregando...</div>;
  }

  return (
    <div className="w-full max-w-6xl mx-auto p-4">
      <div className="mb-8 bg-white rounded-lg shadow p-4">
        <h2 className="text-xl font-bold mb-4">Métricas de Desempenho</h2>
        <ResponsiveContainer width="100%" height={400}>
          <LineChart data={metrics}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Line type="monotone" dataKey="views" stroke="#8884d8" name="Exibições" />
            <Line type="monotone" dataKey="clicks" stroke="#82ca9d" name="Acessos" />
            <Line type="monotone" dataKey="conversions" stroke="#ffc658" name="Conversões" />
          </LineChart>
        </ResponsiveContainer>
      </div>

      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-bold">Meus Imóveis</h2>
          <button
            onClick={handleCheckout}
            disabled={selectedProperties.length === 0}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
          >
            Patrocinar Selecionados
          </button>
        </div>

        <div className="grid gap-4">
          {properties.map(property => (
            <div key={property.id} className="flex items-center justify-between p-4 border rounded-lg">
              <div className="flex-1">
                <h3 className="font-semibold">{property.title}</h3>
                <div className="grid grid-cols-3 gap-4 mt-2 text-sm text-gray-600">
                  <div>Exibições: {property.views}</div>
                  <div>Acessos: {property.clicks}</div>
                  <div>Conversões: {property.conversions}</div>
                </div>
              </div>
              <div className="flex items-center gap-4">
                {property.sponsored && (
                  <span className="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                    Patrocinado
                  </span>
                )}
                <input
                  type="checkbox"
                  checked={selectedProperties.includes(property.id)}
                  onChange={() => handlePropertySelect(property.id)}
                  className="w-5 h-5 text-blue-600"
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default BrokerDashboard;