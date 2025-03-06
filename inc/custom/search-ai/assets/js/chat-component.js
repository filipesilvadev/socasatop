const ChatMessages = ({ messages, currentQuery }) => {
  return React.createElement('div', {
    className: 'flex flex-col gap-4 mb-8'
  }, [
    ...messages.map((msg, index) => 
      React.createElement('div', {
        key: index,
        className: `p-4 rounded-lg ${
          msg.type === 'system' 
            ? 'bg-blue-50 text-blue-900' 
            : 'bg-gray-100 text-gray-900 ml-8'
        }`
      }, [
        React.createElement('div', {
          className: 'flex items-start gap-3'
        }, [
          msg.type === 'system' && React.createElement('div', {
            className: 'w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white flex-shrink-0'
          }, ''),
          React.createElement('div', {
            className: 'flex-1'
          }, msg.text)
        ])
      ])
    ),
    currentQuery && React.createElement('div', {
      className: 'p-4 rounded-lg bg-gray-100 text-gray-900 ml-8'
    }, currentQuery)
  ]);
};

const validateSearch = (query) => {
  const terms = query.toLowerCase();
  const required = {
    location: false,
    type: false,
    transaction: false
  };

  // Validar localização
  const locations = ['asa norte', 'asa sul', 'noroeste', 'sudoeste', 'octogonal', 'cruzeiro', 'lago norte', 'lago sul', 'vicente pires', 'águas claras', 'taguatinga', 'guará', 'ceilândia', 'samambaia', 'recanto das emas', 'riacho fundo', 'riacho fundo ii', 'núcleo bandeirante', 'candangolândia', 'park way', 'parkway', 'brasília', 'paranoá', 'itapoã', 'varjão', 'sobradinho', 'sobradinho ii', 'planaltina', 'santa maria', 'gama', 'brazlândia', 'estrutural', 'jardim botânico', 'são sebastião', 'fercal', 'sol nascente', 'pôr do sol'];
  required.location = locations.some(loc => terms.includes(loc));

  // Validar tipo de imóvel
  const types = ['casa', 'apartamento', 'terreno', 'lote', 'sobrado'];
  required.type = types.some(type => terms.includes(type));

  // Validar tipo de transação
  const transactions = ['compra', 'comprar', 'venda', 'vender', 'aluguel', 'alugar'];
  required.transaction = transactions.some(trans => terms.includes(trans));

  return {
    isValid: Object.values(required).every(v => v),
    missing: Object.entries(required)
      .filter(([_, value]) => !value)
      .map(([key]) => key)
  };
};

export { ChatMessages, validateSearch };