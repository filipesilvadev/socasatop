import React, { useState, useEffect } from 'react';
import _ from 'lodash';

const MetricsDebug = () => {
  const [metricsData, setMetricsData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchMetrics = async () => {
      try {
        const response = await fetch(`${site.ajax_url}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `action=get_broker_metrics&nonce=${site.nonce}`
        });
        const data = await response.json();
        console.log('Dados brutos:', data);
        if (data.success) {
          const metrics = data.data.metrics;
          console.log('Métricas por dia:', metrics);
          
          const totals = {
            views: _.sumBy(metrics, 'views'),
            clicks: _.sumBy(metrics, 'clicks'),
            conversions: _.sumBy(metrics, 'conversions')
          };
          console.log('Totais:', totals);
          
          setMetricsData({ daily: metrics, totals });
        }
      } catch (error) {
        console.error('Erro ao buscar métricas:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchMetrics();
  }, []);

  if (loading) return <div>Carregando dados...</div>;
  if (!metricsData) return <div>Nenhum dado encontrado</div>;

  return (
    <div className="p-4">
      <h2 className="text-lg font-bold mb-4">Debug de Métricas</h2>
      <div className="grid grid-cols-3 gap-4 mb-4">
        <div className="p-4 bg-blue-100 rounded">
          <h3>Total de Exibições</h3>
          <p className="text-2xl font-bold">{metricsData.totals.views}</p>
        </div>
        <div className="p-4 bg-green-100 rounded">
          <h3>Total de Acessos</h3>
          <p className="text-2xl font-bold">{metricsData.totals.clicks}</p>
        </div>
        <div className="p-4 bg-purple-100 rounded">
          <h3>Total de Conversões</h3>
          <p className="text-2xl font-bold">{metricsData.totals.conversions}</p>
        </div>
      </div>
      
      <div className="mt-8">
        <h3 className="font-bold mb-2">Últimos 7 dias:</h3>
        <table className="w-full">
          <thead>
            <tr>
              <th className="text-left">Data</th>
              <th className="text-right">Exibições</th>
              <th className="text-right">Acessos</th>
              <th className="text-right">Conversões</th>
            </tr>
          </thead>
          <tbody>
            {metricsData.daily.slice(0, 7).map((day, index) => (
              <tr key={index}>
                <td>{day.date}</td>
                <td className="text-right">{day.views}</td>
                <td className="text-right">{day.clicks}</td>
                <td className="text-right">{day.conversions}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default MetricsDebug;